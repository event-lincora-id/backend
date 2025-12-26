<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventParticipantController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AdminDashboardController; // Add this import
use App\Http\Controllers\Api\FeedbackSummaryController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\AdminWithdrawalController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Public event routes (specific routes only - wildcard moved to end)
Route::get('events', [EventController::class, 'index']);
Route::get('events/search', [EventController::class, 'search']);
Route::get('events/filter-options', [EventController::class, 'getFilterOptions']);
Route::get('events/popular-searches', [EventController::class, 'getPopularSearches']);
// Note: events/{event} wildcard route moved to end of file to avoid conflicts
Route::get('categories', [CategoryController::class, 'index']);

// Public payment routes
Route::post('payments/webhook', [PaymentController::class, 'handleWebhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('change-password', [ProfileController::class, 'changePassword']);
        Route::post('update-organizer-status', [ProfileController::class, 'updateOrganizerStatus']);

        // Logo and signature upload/delete
        Route::post('logo', [ProfileController::class, 'uploadLogo']);
        Route::post('signature', [ProfileController::class, 'uploadSignature']);
        Route::delete('logo', [ProfileController::class, 'deleteLogo']);
        Route::delete('signature', [ProfileController::class, 'deleteSignature']);
    });

    // Event routes
    Route::prefix('events')->group(function () {
        Route::post('/', [EventController::class, 'store']);
        Route::put('{event}', [EventController::class, 'update']);
        Route::delete('{event}', [EventController::class, 'destroy']);
        Route::get('my-events', [EventController::class, 'myEvents']);
        Route::get('participating', [EventController::class, 'participatingEvents']);
        Route::get('categories', [EventController::class, 'categories']);
        Route::get('{event}/participants', [EventParticipantController::class, 'getEventParticipants']);
    });

    // Event participation routes
    Route::prefix('participants')->group(function () {
        Route::post('join/{event}', [EventParticipantController::class, 'joinEvent']);
        Route::post('cancel/{event}', [EventParticipantController::class, 'cancelParticipation']);
        Route::put('{id}/cancel', [EventParticipantController::class, 'cancel']);
        Route::post('attendance', [EventParticipantController::class, 'markAttendance']);
        Route::get('my-participations', [EventParticipantController::class, 'myParticipations']);
        Route::get('event/{event}', [EventParticipantController::class, 'getEventParticipants']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('create', [PaymentController::class, 'createPayment']);
        Route::get('status/{participant}', [PaymentController::class, 'getPaymentStatus']);
        Route::post('cancel/{participant}', [PaymentController::class, 'cancelPayment']);
        Route::post('retry/{participant}', [PaymentController::class, 'retryPayment']);
        Route::get('methods', [PaymentController::class, 'getPaymentMethods']);
        Route::get('history', [PaymentController::class, 'getPaymentHistory']);
        Route::get('statistics', [PaymentController::class, 'getPaymentStatistics']);
    });

    // Withdrawal & Balance routes (Event Organizers)
    Route::prefix('withdrawals')->group(function () {
        Route::post('request', [WithdrawalController::class, 'requestWithdrawal']);
        Route::get('history', [WithdrawalController::class, 'getHistory']);
        Route::get('{withdrawal}', [WithdrawalController::class, 'show']);
    });

    Route::prefix('balance')->group(function () {
        Route::get('dashboard', [WithdrawalController::class, 'getBalanceDashboard']);
    });

    // Feedback routes
    Route::prefix('feedbacks')->group(function () {
        Route::post('{event}', [FeedbackController::class, 'store']);
        Route::get('my-feedbacks', [FeedbackController::class, 'myFeedbacks']);
        Route::get('event/{event}', [FeedbackController::class, 'getEventFeedbacks']);
        Route::get('certificate/{event}/download', [FeedbackController::class, 'downloadCertificate']);
        Route::get('certificate/{event}/url', [FeedbackController::class, 'getCertificateUrl']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
    });

    // Category management routes (Admin only)
    // ==================== CATEGORY MANAGEMENT (Admin & Super Admin only) ====================
    Route::prefix('categories')
        ->middleware('role:admin') 
        ->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('{category}', [CategoryController::class, 'update']);
            Route::delete('{category}', [CategoryController::class, 'destroy']);
        });
    // ==================== ADMIN DASHBOARD API ROUTES ====================
    Route::prefix('admin')
        ->middleware('role:admin')
        ->group(function () {
            // Dashboard Overview
            Route::get('dashboard', [AdminDashboardController::class, 'index']);
        });

    // ==================== ANALYTICS API ROUTES ====================
    Route::prefix('analytics')
        ->middleware('role:admin')
        ->group(function () {
            // Main Analytics Dashboard
            Route::get('/', [AnalyticsController::class, 'index']);
            
            // Export Analytics
            Route::post('export', [AnalyticsController::class, 'export']);
        });

    // Super Admin routes
    Route::prefix('super-admin')->middleware('role:super_admin')->group(function () {
        Route::get('users', [SuperAdminController::class, 'getAllUsers']);
        Route::post('users/{user}/suspend', [SuperAdminController::class, 'suspendUser']);
        Route::post('users/{user}/activate', [SuperAdminController::class, 'activateUser']);
        Route::delete('users/{user}', [SuperAdminController::class, 'deleteUser']);
        Route::get('organizers', [SuperAdminController::class, 'getOrganizers']);
        Route::get('organizers/{id}', [SuperAdminController::class, 'getOrganizerDetails']);
        Route::post('organizers/{id}/toggle-status', [SuperAdminController::class, 'toggleOrganizerStatus']);
        Route::get('events', [SuperAdminController::class, 'getAllEvents']);
        Route::get('statistics', [SuperAdminController::class, 'getStatistics']);
    });

    // Admin Withdrawal Management routes (Super Admin only)
    Route::prefix('admin/withdrawals')->middleware('role:super_admin')->group(function () {
        Route::get('/', [AdminWithdrawalController::class, 'index']);
        Route::get('{withdrawal}', [AdminWithdrawalController::class, 'show']);
        Route::post('{withdrawal}/approve', [AdminWithdrawalController::class, 'approve']);
        Route::post('{withdrawal}/reject', [AdminWithdrawalController::class, 'reject']);
    });
    
    // Notification routes
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    Route::delete('{notification}', [NotificationController::class, 'destroy']);
    
    // Event reminder routes (NEW)
    Route::post('send-reminder', [NotificationController::class, 'sendEventReminder']);
    Route::get('upcoming-reminders', [NotificationController::class, 'getUpcomingReminders']);
});

    // ==================== FEEDBACK SUMMARY ROUTES (EO only) ====================
    Route::prefix('events/{event}/feedback')->group(function () {
        Route::post('generate-summary', [FeedbackSummaryController::class, 'generateSummary']);
        Route::get('summary', [FeedbackSummaryController::class, 'getSummary']);
        Route::get('summary/detailed', [FeedbackSummaryController::class, 'getDetailedSummary']);
        Route::put('summary', [FeedbackSummaryController::class, 'updateSummary']); // NEW: Update summary
        Route::delete('summary', [FeedbackSummaryController::class, 'deleteSummary']); // NEW: Delete summary
    });

});

// ==================== PUBLIC WILDCARD ROUTES ====================
// These must be defined AFTER specific routes to avoid conflicts
// The wildcard {event} parameter would match specific routes like 'my-events' if defined earlier
Route::get('events/{event}', [EventController::class, 'show']);
