<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Order;
use Aanugerah\WeddingPro\Models\Package;
use Aanugerah\WeddingPro\Models\Review;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Store a new review
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'package_id' => 'required|exists:packages,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
                'title' => 'nullable|string|max:255',
            ]);

            // Check if user has ordered this package before
            $order = Order::where('user_id', Auth::id())
                ->where('package_id', $validatedData['package_id'])
                ->where('status', 'completed')
                ->first(['*']);

            if (! $order) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Anda hanya dapat memberikan ulasan untuk paket yang telah Anda pesan dan selesai'),
                ], 403);
            }

            // Check if user has already reviewed this package
            $existingReview = Review::where('user_id', Auth::id())
                ->where('package_id', $validatedData['package_id'])
                ->first(['*']);

            if ($existingReview) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Anda sudah memberikan ulasan untuk paket ini'),
                ], 409);
            }

            $review = Review::create([
                'user_id' => Auth::id(),
                'package_id' => $validatedData['package_id'],
                'rating' => $validatedData['rating'],
                'title' => $validatedData['title'] ?? null,
                'comment' => $validatedData['comment'] ?? null,
            ]);

            // Load relationships for the response
            $review->load(['user:id,full_name,avatar_url', 'package:id,name']);

            return response()->json([
                'status' => 'success',
                'message' => __('Ulasan berhasil dikirim'),
                'data' => $review,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validasi gagal'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengirim ulasan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reviews for a specific package
     */
    public function getPackageReviews($packageId, Request $request)
    {
        try {
            $query = Review::with(['user:id,full_name,avatar_url'])
                ->where('package_id', $packageId)
                ->orderByDesc('created_at');

            // Filter by rating if provided
            if ($request->filled('rating')) {
                $query->where('rating', $request->rating);
            }

            // Filter by min rating if provided
            if ($request->filled('min_rating')) {
                $query->where('rating', '>=', $request->min_rating);
            }

            $reviews = $query->paginate($request->get('per_page', 10));

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
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil ulasan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reviews for a specific organizer
     */
    public function getOrganizerReviews($organizerId, Request $request)
    {
        try {
            $query = Review::with(['user:id,full_name,avatar_url', 'package:id,name'])
                ->join('packages', 'reviews.package_id', '=', 'packages.id')
                ->where('packages.wedding_organizer_id', $organizerId)
                ->select('reviews.*')
                ->orderByDesc('reviews.created_at');

            // Filter by rating if provided
            if ($request->filled('rating')) {
                $query->where('reviews.rating', $request->rating);
            }

            // Filter by min rating if provided
            if ($request->filled('min_rating')) {
                $query->where('reviews.rating', '>=', $request->min_rating);
            }

            $reviews = $query->paginate($request->get('per_page', 10));

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
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil ulasan organizer'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's own reviews
     */
    public function getUserReviews(Request $request)
    {
        try {
            $query = Review::with(['package:id,name,price', 'package.weddingOrganizer:id,name'])
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at');

            $reviews = $query->paginate($request->get('per_page', 10));

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
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil ulasan Anda'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing review
     */
    public function update(Request $request, $id)
    {
        try {
            $review = Review::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail(['*']);

            $validatedData = $request->validate([
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'comment' => 'sometimes|nullable|string|max:1000',
                'title' => 'sometimes|nullable|string|max:255',
            ]);

            $review->update($validatedData);

            // Refresh the review with loaded relationships
            $review->load(['user:id,full_name,avatar_url', 'package:id,name']);

            return response()->json([
                'status' => 'success',
                'message' => __('Ulasan berhasil diperbarui'),
                'data' => $review,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validasi gagal'),
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Ulasan tidak ditemukan atau bukan milik Anda'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal memperbarui ulasan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy($id)
    {
        try {
            $review = Review::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail(['*']);

            $review->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('Ulasan berhasil dihapus'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Ulasan tidak ditemukan atau bukan milik Anda'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal menghapus ulasan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
