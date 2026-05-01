<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Package;
use Aanugerah\WeddingPro\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist products
     */
    public function index(Request $request)
    {
        try {
            $query = Wishlist::with(['package' => function ($query): void {
                $query->with(['weddingOrganizer', 'category', 'reviews']);
            }])
                ->where('user_id', Auth::id());

            // Apply filters
            if ($request->filled('organizer_id')) {
                $query->whereHas('package', function ($q) use ($request): void {
                    $q->where('wedding_organizer_id', $request->organizer_id);
                });
            }

            if ($request->filled('category_id')) {
                $query->whereHas('package', function ($q) use ($request): void {
                    $q->where('category_id', $request->category_id);
                });
            }

            $wishlistItems = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'status' => 'success',
                'data' => $wishlistItems->products(),
                'pagination' => [
                    'current_page' => $wishlistItems->currentPage(),
                    'last_page' => $wishlistItems->lastPage(),
                    'per_page' => $wishlistItems->perPage(),
                    'total' => $wishlistItems->total(),
                    'has_more_pages' => $wishlistItems->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil wishlist'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle wishlist product (add/remove)
     */
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'package_id' => 'required|exists:packages,id',
            ]);

            $existingWishlist = Wishlist::where('user_id', Auth::id())
                ->where('package_id', $request->package_id)
                ->first(['*']);

            if ($existingWishlist) {
                // Remove from wishlist
                $existingWishlist->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => __('Dihapus dari wishlist'),
                    'in_wishlist' => false,
                ]);
            } else {
                // Add to wishlist
                $package = Package::findOrFail($request->package_id, ['*']);

                $wishlist = Wishlist::create([
                    'user_id' => Auth::id(),
                    'package_id' => $package->id,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => __('Ditambahkan ke wishlist'),
                    'in_wishlist' => true,
                    'data' => $wishlist,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal memperbarui wishlist'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a package is in user's wishlist
     */
    public function isInWishlist($packageId)
    {
        try {
            $exists = Wishlist::where('user_id', Auth::id())
                ->where('package_id', $packageId)
                ->exists();

            return response()->json([
                'status' => 'success',
                'in_wishlist' => $exists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal memeriksa status wishlist'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk add packages to wishlist
     */
    public function bulkAdd(Request $request)
    {
        try {
            $request->validate([
                'package_ids' => 'required|array',
                'package_ids.*' => 'exists:packages,id',
            ]);

            $addedCount = 0;
            $skippedIds = [];

            foreach ($request->package_ids as $packageId) {
                $existing = Wishlist::where('user_id', Auth::id())
                    ->where('package_id', $packageId)
                    ->first(['*']);

                if (! $existing) {
                    Wishlist::create([
                        'user_id' => Auth::id(),
                        'package_id' => $packageId,
                    ]);
                    $addedCount++;
                } else {
                    $skippedIds[] = $packageId;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => $addedCount.' '.__('product ditambahkan ke wishlist'),
                'added_count' => $addedCount,
                'skipped_count' => count($skippedIds),
                'skipped_ids' => $skippedIds,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal menambahkan product ke wishlist'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove specific product from wishlist
     */
    public function removeFromWishlist($packageId)
    {
        try {
            $wishlist = Wishlist::where('user_id', Auth::id())
                ->where('package_id', $packageId)
                ->first(['*']);

            if (! $wishlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Product tidak ditemukan dalam wishlist'),
                ], 404);
            }

            $wishlist->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('Dihapus dari wishlist'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal menghapus dari wishlist'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
