<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories with pagination
     */
    public function index(Request $request)
    {
        try {
            $query = Category::withCount('packages');

            // Apply search filter if provided
            if ($request->filled('search')) {
                $query->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortDirection = $request->get('sort_direction', 'asc');

            // Validate sort parameters to prevent injection
            $allowedSortFields = ['name', 'created_at', 'packages_count'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'name';
            }

            $allowedDirections = ['asc', 'desc'];
            if (! in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'asc';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginate results
            $categories = $query->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $categories->products(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'has_more_pages' => $categories->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil kategori'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific category by ID
     */
    public function show($id)
    {
        try {
            $category = Category::with(['packages' => function ($query): void {
                $query->with(['weddingOrganizer', 'reviews'])->limit(10);
            }, 'packagesCount'])->findOrFail($id, ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $category,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Kategori tidak ditemukan'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil detail kategori'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories with their top packages
     */
    public function withTopPackages(Request $request)
    {
        try {
            $categories = Category::with(['packages' => function ($query) use ($request): void {
                $query->with(['weddingOrganizer', 'reviews'])
                    ->orderBy('price', 'asc')
                    ->limit($request->get('packages_per_category', 5));
            }, 'packagesCount'])->get(['*']);

            return response()->json([
                'status' => 'success',
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil kategori dan paket'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
