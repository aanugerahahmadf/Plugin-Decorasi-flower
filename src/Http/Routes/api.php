<?php

use Aanugerah\WeddingPro\Http\Controllers\Api\AppSettingsController;
use Aanugerah\WeddingPro\Http\Controllers\Api\ArticleController;
use Aanugerah\WeddingPro\Http\Controllers\Api\AuthController;
use Aanugerah\WeddingPro\Http\Controllers\Api\CategoryController;
use Aanugerah\WeddingPro\Http\Controllers\Api\CBIRController;
use Aanugerah\WeddingPro\Http\Controllers\Api\ChatController;
use Aanugerah\WeddingPro\Http\Controllers\Api\HomeController;
use Aanugerah\WeddingPro\Http\Controllers\Api\OrderController;
use Aanugerah\WeddingPro\Http\Controllers\Api\PackageController;
use Aanugerah\WeddingPro\Http\Controllers\Api\PaymentController;
use Aanugerah\WeddingPro\Http\Controllers\Api\PaymentWebhookController;
use Aanugerah\WeddingPro\Http\Controllers\Api\ProfileController;
use Aanugerah\WeddingPro\Http\Controllers\Api\ReviewController;
use Aanugerah\WeddingPro\Http\Controllers\Api\SearchController;
use Aanugerah\WeddingPro\Http\Controllers\Api\VoucherController;
use Aanugerah\WeddingPro\Http\Controllers\Api\WalletController;
use Aanugerah\WeddingPro\Http\Controllers\Api\WeddingOrganizerController;
use Aanugerah\WeddingPro\Http\Controllers\Api\WishlistController;
use Aanugerah\WeddingPro\Http\Controllers\DatabaseProxyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public: app config
Route::get('/settings', [AppSettingsController::class, 'index']);

// ── DIAGNOSTIC ENDPOINT
Route::get('/ping', function () {
    $isMobile = \Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile();
    $hostIp   = \Aanugerah\WeddingPro\NativeServiceProvider::mobileHostIp();

    $dbStatus  = 'unknown';
    $userCount = 0;
    $dbError   = null;

    try {
        $userCount = (config('auth.providers.users.model'))::count();
        $dbStatus  = 'connected';
    } catch (\Throwable $e) {
        $dbStatus = 'error';
        $dbError  = $e->getMessage();
    }

    return response()->json([
        'status'      => 'ok',
        'timestamp'   => now()->toIso8601String(),
        'is_mobile'   => $isMobile,
        'host_ip'     => $hostIp,
        'os'          => PHP_OS_FAMILY,
        'db_driver'   => config('database.default'),
        'db_status'   => $dbStatus,
        'user_count'  => $userCount,
        'db_error'    => $dbError,
        'app_url'     => config('app.url'),
        'locale'      => app()->getLocale(),
        'php_version' => PHP_VERSION,
    ]);
});

// NativePHP Mobile DB Proxy
Route::post('/db-proxy', [DatabaseProxyController::class, 'proxy']);

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

// Public endpoints
Route::get('/organizers/public', [WeddingOrganizerController::class, 'index']);
Route::get('/organizers/public/{id}', [WeddingOrganizerController::class, 'show']);
Route::get('/packages/public', [PackageController::class, 'index']);

// Webhooks
Route::post('/webhooks/midtrans', [PaymentWebhookController::class, 'handleMidtransNotification']);
Route::post('/v1.0/payment/notify', [PaymentWebhookController::class, 'handleSnapBiNotification']);

// CBIR Stats
Route::get('/cbir/stats', [CBIRController::class, 'getStats']);
Route::get('/cbir/health', [CBIRController::class, 'healthCheck']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/user/account', [AuthController::class, 'deleteAccount']);
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    });

    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::get('/profile/dashboard', [ProfileController::class, 'dashboard']);

    // Wedding Organizers
    Route::get('/organizers', [WeddingOrganizerController::class, 'index']);
    Route::get('/organizers/{id}', [WeddingOrganizerController::class, 'show']);
    Route::get('/organizers/{id}/packages', [WeddingOrganizerController::class, 'packages']);
    Route::get('/organizers/{id}/reviews', [WeddingOrganizerController::class, 'reviews']);
    Route::get('/organizers/featured', [WeddingOrganizerController::class, 'featured']);
    Route::get('/organizers/top-rated', [WeddingOrganizerController::class, 'topRated']);
    Route::get('/organizers/nearby', [WeddingOrganizerController::class, 'nearby']);

    // Home
    Route::get('/home', [HomeController::class, 'index']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/categories-with-packages', [CategoryController::class, 'withTopPackages']);

    // Vouchers
    Route::get('/vouchers', [VoucherController::class, 'index']);

    // Articles
    Route::get('/articles', [ArticleController::class, 'index']);

    // Packages
    Route::get('/packages', [PackageController::class, 'index']);
    Route::get('/packages/{id}', [PackageController::class, 'show']);
    Route::get('/packages/featured', [PackageController::class, 'featured']);
    Route::get('/packages/on-sale', [PackageController::class, 'onSale']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::get('/wishlist/{packageId}/check', [WishlistController::class, 'isInWishlist']);
    Route::post('/wishlist/bulk-add', [WishlistController::class, 'bulkAdd']);
    Route::delete('/wishlist/{packageId}', [WishlistController::class, 'removeFromWishlist']);

    // Search
    Route::get('/search', [SearchController::class, 'byText']);
    Route::post('/search/image', [SearchController::class, 'byImage']);

    // Chat
    Route::get('/messages/conversations', [ChatController::class, 'getConversations']);
    Route::get('/messages/conversations/{inboxId}', [ChatController::class, 'getMessages']);
    Route::get('/messages/unread-count', [ChatController::class, 'getUnreadCount']);
    Route::get('/messages/customers', [ChatController::class, 'getCustomersForChat']);
    Route::post('/messages/send', [ChatController::class, 'sendMessage']);
    Route::post('/messages/start', [ChatController::class, 'startConversation']);

    // Bookings / Orders
    Route::get('/bookings', [OrderController::class, 'getOrders']);
    Route::post('/bookings', [OrderController::class, 'createOrder']);
    Route::post('/bookings/{id}/pay', [OrderController::class, 'processPayment']);
    Route::get('/bookings/track/{orderNumber}', [OrderController::class, 'trackOrder']);
    Route::get('/bookings/{id}', [OrderController::class, 'show']);
    Route::post('/bookings/{id}/cancel', [OrderController::class, 'cancelOrder']);

    Route::get('/orders', [OrderController::class, 'getOrders']);
    Route::post('/orders', [OrderController::class, 'createOrder']);
    Route::post('/orders/{id}/pay', [OrderController::class, 'processPayment']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);

    // Payments
    Route::get('/payments/methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('/payments', [PaymentController::class, 'createPayment']);
    Route::get('/payments', [PaymentController::class, 'getUserPayments']);
    Route::get('/payments/{paymentNumber}', [PaymentController::class, 'getPayment'])->name('payment.show');
    Route::post('/payments/{paymentNumber}/upload-proof', [PaymentController::class, 'uploadPaymentProof']);
    Route::post('/payments/{paymentNumber}/cancel', [PaymentController::class, 'cancelPayment']);
    Route::get('/payments/{paymentNumber}/status', [PaymentController::class, 'getPaymentStatus']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::get('/reviews/user', [ReviewController::class, 'getUserReviews']);
    Route::get('/reviews/package/{packageId}', [ReviewController::class, 'getPackageReviews']);
    Route::get('/reviews/organizer/{id}', [ReviewController::class, 'getOrganizerReviews']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'getWalletData']);
    Route::get('/wallet/history', [WalletController::class, 'getHistory']);
    Route::post('/wallet/topup', [WalletController::class, 'requestTopup']);
    Route::post('/wallet/topup/{id}/proof', [WalletController::class, 'uploadProof']);
    Route::get('/wallet/withdrawal', [WalletController::class, 'getWithdrawalHistory']);
    Route::post('/wallet/withdrawal', [WalletController::class, 'requestWithdrawal']);
    Route::get('/wallet/withdrawal/history', [WalletController::class, 'getWithdrawalHistory']);

    // CBIR - AI Visual Search
    Route::post('/cbir/search', [CBIRController::class, 'searchSimilar']);
    Route::post('/cbir/index/product', [CBIRController::class, 'indexItem']);
    Route::post('/cbir/index/build', [CBIRController::class, 'buildIndex']);
    Route::get('/cbir/stats', [CBIRController::class, 'getStats']);
    Route::get('/cbir/health', [CBIRController::class, 'healthCheck']);
});
