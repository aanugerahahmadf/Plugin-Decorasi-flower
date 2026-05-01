<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Package;
use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WeddingOrganizerController extends Controller
{
    /**
     * Get all wedding organizers with pagination and advanced filtering
     */
    public function index(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'location' => 'nullable|string|max:255',
                'min_rating' => 'nullable|numeric|min:0|max:5',
                'max_rating' => 'nullable|numeric|min:0|max:5',
                'search' => 'nullable|string|max:255',
                'category_id' => 'nullable|integer|exists:categories,id',
                'price_range' => 'nullable|string|in:low,mid,high,premium',
                'sort_by' => [
                    'nullable',
                    'string',
                    Rule::in(['name', 'rating', 'price', 'created_at', 'distance']),
                ],
                'sort_direction' => [
                    'nullable',
                    'string',
                    Rule::in(['asc', 'desc']),
                ],
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = WeddingOrganizer::with(['media', 'packages', 'reviews'])
                ->withCount(['reviews', 'packages'])
                ->where('id', 1); // Enforce single-vendor: ID 1 only

            // Filter by location
            if (! empty($validatedData['location'])) {
                $query->where('address', 'like', '%'.$validatedData['location'].'%');
            }

            // Filter by minimum rating
            if (! empty($validatedData['min_rating'])) {
                $query->where('rating', '>=', $validatedData['min_rating']);
            }

            // Filter by maximum rating
            if (! empty($validatedData['max_rating'])) {
                $query->where('rating', '<=', $validatedData['max_rating']);
            }

            // Search by name or description
            if (! empty($validatedData['search'])) {
                $query->where(function ($q) use ($validatedData): void {
                    $q->where('name', 'like', '%'.$validatedData['search'].'%')
                        ->orWhere('description', 'like', '%'.$validatedData['search'].'%');
                });
            }

            // Filter by category (through packages)
            if (! empty($validatedData['category_id'])) {
                $query->whereHas('packages', function ($q) use ($validatedData): void {
                    $q->where('category_id', $validatedData['category_id']);
                });
            }

            // Apply sorting
            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortDirection = $validatedData['sort_direction'] ?? 'desc';

            switch ($sortBy) {
                case 'rating':
                    $query->orderBy('rating', $sortDirection);
                    break;
                case 'name':
                    $query->orderBy('name', $sortDirection);
                    break;
                case 'distance': // This would require actual coordinates
                    // In a real implementation, you'd calculate distance from user's location
                    // For now, we'll default to created_at
                    $query->orderBy('created_at', $sortDirection);
                    break;
                default:
                    $query->orderBy('created_at', $sortDirection);
                    break;
            }

            $organizers = $query->paginate($validatedData['per_page'] ?? 10, ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $organizers->products(),
                'pagination' => [
                    'current_page' => $organizers->currentPage(),
                    'last_page' => $organizers->lastPage(),
                    'per_page' => $organizers->perPage(),
                    'total' => $organizers->total(),
                    'has_more_pages' => $organizers->hasMorePages(),
                ],
                'filters_applied' => $validatedData,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil data wedding organizer'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wedding organizer details
     */
    public function show($id)
    {
        try {
            $organizer = WeddingOrganizer::with([
                'media',
                'packages' => function ($query): void {
                    $query->with(['category', 'reviews'])->limit(10);
                },
                'reviews' => function ($query): void {
                    $query->with('user:id,full_name,avatar_url')->latest()->limit(5);
                },
            ])
                ->withCount(['reviews', 'packages'])
                ->where('id', 1) // Always ensure we only show our own brand
                ->findOrFail($id, ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $organizer,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wedding organizer not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil detail wedding organizer'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get organizer packages with filtering and pagination
     */
    public function packages($id, Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'nullable|integer|exists:categories,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'is_featured' => 'nullable|boolean',
                'theme' => 'nullable|string|max:255',
                'sort_by' => [
                    'nullable',
                    'string',
                    Rule::in(['name', 'price', 'created_at', 'rating']),
                ],
                'sort_direction' => [
                    'nullable',
                    'string',
                    Rule::in(['asc', 'desc']),
                ],
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $organizer = WeddingOrganizer::findOrFail($id, ['*']);

            $query = Package::where('wedding_organizer_id', $id)
                ->with(['category', 'reviews']);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->filled('is_featured')) {
                $query->where('is_featured', $request->is_featured);
            }

            if ($request->filled('theme')) {
                $query->where('theme', 'like', '%'.$request->theme.'%');
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['name', 'price', 'created_at', 'rating'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $allowedDirections = ['asc', 'desc'];
            if (! in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'desc';
            }

            $query->orderBy($sortBy, $sortDirection);

            $packages = $query->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $packages->products(),
                'pagination' => [
                    'current_page' => $packages->currentPage(),
                    'last_page' => $packages->lastPage(),
                    'per_page' => $packages->perPage(),
                    'total' => $packages->total(),
                    'has_more_pages' => $packages->hasMorePages(),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wedding organizer not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil paket organizer'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get organizer reviews with pagination
     */
    public function reviews($id, Request $request)
    {
        try {
            $request->validate([
                'rating' => 'nullable|integer|min:1|max:5',
                'min_rating' => 'nullable|integer|min:1|max:5',
                'sort_by' => [
                    'nullable',
                    'string',
                    Rule::in(['created_at', 'rating']),
                ],
                'sort_direction' => [
                    'nullable',
                    'string',
                    Rule::in(['asc', 'desc']),
                ],
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $organizer = WeddingOrganizer::findOrFail($id, ['*']);

            $query = $organizer->reviews()
                ->with('user:id,full_name,avatar_url')
                ->select(['reviews.*']); // Explicitly select from reviews table to avoid conflicts

            // Apply filters
            if ($request->filled('rating')) {
                $query->where('rating', $request->rating);
            }

            if ($request->filled('min_rating')) {
                $query->where('rating', '>=', $request->min_rating);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['created_at', 'rating'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $allowedDirections = ['asc', 'desc'];
            if (! in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'desc';
            }

            $query->orderBy('reviews.'.$sortBy, $sortDirection);

            $reviews = $query->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $reviews->products(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'has_more_pages' => $reviews->hasMorePages(),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wedding organizer not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil ulasan organizer'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured wedding organizers
     */
    public function featured(Request $request)
    {
        try {
            $organizers = WeddingOrganizer::with(['media'])
                ->withCount(['reviews', 'packages'])
                ->where('id', 1) // Only featured Dekorasi Bunga Pernikahan

                ->orderBy('rating', 'desc')
                ->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $organizers->products(),
                'pagination' => [
                    'current_page' => $organizers->currentPage(),
                    'last_page' => $organizers->lastPage(),
                    'per_page' => $organizers->perPage(),
                    'total' => $organizers->total(),
                    'has_more_pages' => $organizers->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil data wedding organizer unggulan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get top-rated wedding organizers
     */
    public function topRated(Request $request)
    {
        try {
            $organizers = WeddingOrganizer::with(['media'])
                ->withCount(['reviews', 'packages'])
                ->where('is_verified', true)
                ->where('rating', '>=', 4.0) // Top rated = 4 stars and above
                ->orderBy('rating', 'desc')
                ->orderBy('reviews_count', 'desc') // Secondary sort by number of reviews
                ->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $organizers->products(),
                'pagination' => [
                    'current_page' => $organizers->currentPage(),
                    'last_page' => $organizers->lastPage(),
                    'per_page' => $organizers->perPage(),
                    'total' => $organizers->total(),
                    'has_more_pages' => $organizers->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil data wedding organizer terbaik'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wedding organizers near user location (placeholder implementation)
     */
    public function nearby(Request $request)
    {
        try {
            $request->validate([
                'latitude' => 'required_with:longitude|numeric|between:-90,90',
                'longitude' => 'required_with:latitude|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:0.1|max:100', // in kilometers
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // Placeholder implementation - in a real app, you'd calculate actual distances
            // This example just returns all verified organizers sorted by a "proximity" score
            $organizers = WeddingOrganizer::with(['media'])
                ->withCount(['reviews', 'packages'])
                ->where('is_verified', true)
                ->orderBy('created_at', 'desc') // Sort by newest as a placeholder
                ->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $organizers->products(),
                'pagination' => [
                    'current_page' => $organizers->currentPage(),
                    'last_page' => $organizers->lastPage(),
                    'per_page' => $organizers->perPage(),
                    'total' => $organizers->total(),
                    'has_more_pages' => $organizers->hasMorePages(),
                ],
                'message' => __('Ini adalah fitur simulasi. Perhitungan jarak sebenarnya membutuhkan izin lokasi GPS.'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil data wedding organizer terdekat'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
