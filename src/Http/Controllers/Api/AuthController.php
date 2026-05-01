<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users|alpha_dash',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
            'profile_photo' => 'nullable|image|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal validasi'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $userData = [
            'full_name' => $request->full_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
        ];

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('avatars', 'public');
            $userData['avatar_url'] = $path;
        }

        $user = (config('auth.providers.users.model'))::create($userData);

        $user->assignRole('user');

        /** @var User $user */
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // can be email or username
            'password' => 'required',
        ]);

        $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$fieldType => $request->login, 'password' => $request->password])) {
            return response()->json([
                'status' => 'error',
                'message' => __('Data login tidak valid'),
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Check if user is active
        if ($user->active_status === false) {
            Auth::logout();

            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda telah dinonaktifkan oleh admin.',
            ], 403);
        }

        // Ensure user is active on login
        if (! $user->active_status) {
            $user->update(['active_status' => true]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Berhasil keluar'),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = (config('auth.providers.users.model'))::where('email', $request->email)->first(['*']);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Email tidak terdaftar.'], 404);
        }

        // TODO: kirim OTP ke email (notification/mail)
        return response()->json([
            'status' => 'success',
            'message' => 'Instruksi reset password akan dikirim ke email Anda.',
            'email' => $user->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = (config('auth.providers.users.model'))::where('email', $request->email)->first(['*']);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => __('Pengguna tidak ditemukan')], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'status' => 'success',
            'message' => __('Reset password berhasil'),
        ]);
    }

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'token' => 'required|string',
        ]);

        // In a real app, verify the token with the provider (Google/Facebook)
        // For now, we simulate success or find user by email if token contains it
        return response()->json([
            'status' => 'success',
            'message' => __('Login sosial berhasil (simulasi)'),
            'data' => [
                'token' => 'SOCIAL-TOKEN-'.Str::random(40),
                'user' => Auth::user() ?: User::first(['*']), // Fallback for simulation
            ],
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|string',
        ]);

        // Simulating OTP sending
        return response()->json([
            'status' => 'success',
            'message' => __('OTP berhasil dikirim ke ').$request->email,
            'otp' => '123456', // Simulated OTP
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'purpose' => 'required|string',
        ]);

        // Simulating OTP verification
        if ($request->otp === '123456') {
            return response()->json([
                'status' => 'success',
                'message' => __('OTP berhasil diverifikasi'),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => __('OTP tidak valid'),
        ], 422);
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'budget' => 'nullable|numeric',
            'wedding_date' => 'nullable|date',
            'theme_preference' => 'nullable|string',
            'color_preference' => 'nullable|string',
            'event_concept' => 'nullable|string',
            'dream_venue' => 'nullable|string',
        ]);

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $data['avatar_url'] = $path;
        }

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function deleteAccount(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete user
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Akun berhasil dihapus'),
        ]);
    }
}
