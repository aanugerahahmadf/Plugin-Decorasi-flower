<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Get user profile with comprehensive details
     */
    public function show()
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Pengguna tidak terautentikasi'),
                ], 401);
            }

            // Prepare user data
            $data = $user->toArray();
            $data['is_admin'] = $data['is_super_admin'] = $user->hasRole('super_admin');
            $data['roles'] = $user->getRoleNames()->toArray();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil profil pengguna'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user profile with validation
     */
    public function update(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validatedData = $request->validate([
                'full_name' => 'required|string|max:255',
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'username' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'wedding_date' => 'nullable|date',
                'budget' => 'nullable|numeric|min:0',
                'theme_preference' => 'nullable|string|max:100',
                'color_preference' => 'nullable|string|max:100',
                'event_concept' => 'nullable|string|max:255',
                'dream_venue' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
            ]);

            $user->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => __('Profil berhasil diperbarui'),
                'data' => $user->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validasi gagal'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal memperbarui profil'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            ]);

            // Delete old avatar if exists
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Store new avatar with unique name
            $fileName = 'avatar_'.$user->id.'_'.time().'.'.$request->file('avatar')->getClientOriginalExtension();
            $path = $request->file('avatar')->storeAs('avatars', $fileName, 'public');

            $user->update(['avatar_url' => $path]);

            return response()->json([
                'status' => 'success',
                'message' => __('Avatar berhasil diperbarui'),
                'data' => [
                    'avatar_url' => $user->avatar_url,
                    'avatar_full_url' => $user->getFilamentAvatarUrl(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validasi gagal'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal memperbarui avatar'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password with enhanced validation
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Kata sandi saat ini salah'),
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Kata sandi berhasil diubah'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validasi gagal'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengubah kata sandi'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user dashboard statistics
     */
    public function dashboard()
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $data = [
                'user' => $user,
                'stats' => [
                    'total_orders' => $user->orders()->count(),
                    'completed_orders' => $user->orders()->where('status', 'completed')->count(),
                    'pending_orders' => $user->orders()->where('status', 'pending')->count(),
                    'confirmed_orders' => $user->orders()->where('status', 'confirmed')->count(),
                    'cancelled_orders' => $user->orders()->where('status', 'cancelled')->count(),
                    'pending_payments' => $user->orders()
                        ->where('payment_status', 'pending')
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->count(),
                    'paid_orders' => $user->orders()->where('payment_status', 'paid')->count(),
                    'wishlist_count' => $user->wishlists()->count(),
                    'unread_notifications' => $user->unreadNotifications()->count(),
                    'total_spent' => $user->orders()->sum('total_price'),
                ],
                'upcoming_events' => $user->orders()
                    ->with(['package.weddingOrganizer'])
                    ->where('booking_date', '>=', now())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->orderBy('booking_date')
                    ->limit(5)
                    ->get(['*']),
                'recent_orders' => $user->orders()
                    ->with(['package.weddingOrganizer'])
                    ->latest()
                    ->limit(5)
                    ->get(['*']),
                'recent_activity' => $user->notifications()
                    ->latest()
                    ->limit(5)
                    ->get(['*']),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil data dashboard'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's order history
     */
    public function getOrderHistory(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $query = $user->orders()->with(['package.weddingOrganizer', 'latestPayment']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereBetween('booking_date', [$request->from_date, $request->to_date]);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['created_at', 'booking_date', 'total_price', 'status'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $allowedDirections = ['asc', 'desc'];
            if (! in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'desc';
            }

            $query->orderBy($sortBy, $sortDirection);

            $orders = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'status' => 'success',
                'data' => $orders->products(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'has_more_pages' => $orders->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil riwayat pesanan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's wishlist products
     */
    public function getWishlist(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $query = $user->wishlists()->with(['package.weddingOrganizer', 'package.category', 'package.reviews']);

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['created_at', 'package.name', 'package.price'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $allowedDirections = ['asc', 'desc'];
            if (! in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'desc';
            }

            $query->orderBy($sortBy, $sortDirection);

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
}
