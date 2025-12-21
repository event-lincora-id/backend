<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ParticipantDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ParticipantEventController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Api\FeedbackSummaryController;

Route::get('/', function () {
    if (Auth::check()) {
        // If user is logged in, redirect to appropriate dashboard
        if (Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('participant.dashboard');
        }
    }
    
    // If not logged in, show welcome page
    return view('welcome');
});

Route::get('/api-docs', function () {
    return view('api-docs');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Participant Dashboard
Route::middleware(['auth', 'role:participant'])->group(function () {
    Route::get('/participant/dashboard', [ParticipantDashboardController::class, 'index'])->name('participant.dashboard');
    Route::get('/attendance/scanner', [AttendanceController::class, 'scanner'])->name('attendance.scanner');
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark');
});

// Public Event Search & Browse (Available to all users)
Route::get('/events', [ParticipantEventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [ParticipantEventController::class, 'show'])->name('events.show');

// Payment pages
Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
Route::get('/payments/failure', [PaymentController::class, 'failure'])->name('payments.failure');
Route::get('/payments/status/{participant}', [PaymentController::class, 'status'])->name('payments.status');

        // Admin Dashboard Routes (Organizer-specific data)
        Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    // Users Management
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy'
    ]);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
    
    // Events Management
    Route::resource('events', EventController::class)->names([
        'index' => 'admin.events.index',
        'create' => 'admin.events.create',
        'store' => 'admin.events.store',
        'show' => 'admin.events.show',
        'edit' => 'admin.events.edit',
        'update' => 'admin.events.update',
        'destroy' => 'admin.events.destroy'
    ]);
    Route::post('/events/{event}/toggle-status', [EventController::class, 'toggleStatus'])->name('admin.events.toggle-status');
    Route::get('/events/{event}/participants', [EventController::class, 'participants'])->name('admin.events.participants');
    Route::get('/events/{event}/qr-code', [AttendanceController::class, 'showQRCode'])->name('attendance.qr-code');
    Route::get('/events/{event}/attendance/participants', [AttendanceController::class, 'getParticipants'])->name('attendance.participants');
    
    // Categories Management
    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit'])->names([
        'index' => 'admin.categories.index',
        'store' => 'admin.categories.store',
        'update' => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy'
    ]);
    Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('admin.categories.toggle-status');
    Route::get('/categories/{category}/statistics', [CategoryController::class, 'statistics'])->name('admin.categories.statistics');
    
    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('admin.analytics.export');
    Route::get('/analytics/realtime', [AnalyticsController::class, 'realtime'])->name('admin.analytics.realtime');

    // Finance
    Route::get('/finance', [FinanceController::class, 'index'])->name('admin.finance.index');
    Route::get('/events/{event}/finance', [FinanceController::class, 'show'])->name('admin.events.finance');
});
use App\Http\Controllers\Web\FeedbackController as WebFeedbackController;
Route::middleware(['auth', 'role:participant'])->group(function () {
    Route::get('/feedback/create/{event}', [WebFeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback/store/{event}', [WebFeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/feedback/my-feedbacks', [WebFeedbackController::class, 'myFeedbacks'])->name('feedback.my-feedbacks');
    Route::get('/feedback/certificate/{event}/download', [WebFeedbackController::class, 'downloadCertificate'])->name('feedback.certificate.download');
    Route::get('/feedback/certificate/{event}/view', [WebFeedbackController::class, 'viewCertificate'])->name('feedback.certificate.view');
});

// Certificate verification routes (public)
Route::get('/verify/{verificationCode}', [App\Http\Controllers\CertificateVerificationController::class, 'verify'])
    ->name('certificate.verify');
Route::get('/verify/{verificationCode}/download', [App\Http\Controllers\CertificateVerificationController::class, 'download'])
    ->name('certificate.download');

Route::get('/test-feedback-summary', function () {
    return view('test-feedback-summary');
});