<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookmarkController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PollController;
use App\Http\Controllers\Api\V1\ReactionController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| NewsPulse API (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.public');

    /* ------------------------------ auth ------------------------------ */

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

        // Signed endpoint used to generate verification links; the SPA calls
        // the POST /verify endpoint below with the same signature parameters.
        Route::get('/email/verify/{id}/{hash}', fn (Request $request) => response()->json([
            'message' => 'Use the SPA to complete email verification.',
        ]))->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/email/verification-notification', [AuthController::class, 'sendVerification'])
                ->middleware('throttle:5,1');
            Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
        });
    });

    /* --------------------------- public content ------------------------ */

    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/trending', [ArticleController::class, 'trending'])->name('articles.trending');
    Route::get('/articles/breaking', [ArticleController::class, 'breaking'])->name('articles.breaking');
    Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('/articles/{article}/comments', [CommentController::class, 'index'])->name('articles.comments');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('/tags/{slug}', [TagController::class, 'show'])->name('tags.show');

    Route::get('/authors/{id}', [AuthorController::class, 'show'])->whereNumber('id')->name('authors.show');

    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

    Route::get('/polls/active', [PollController::class, 'active'])->name('polls.active');
    Route::get('/polls', [PollController::class, 'active']);

    /* ---------------------------- newsletter --------------------------- */

    Route::prefix('newsletter')->middleware('throttle:10,1')->group(function (): void {
        Route::post('/subscribe', [NewsletterController::class, 'subscribe']);
        Route::post('/confirm/{token}', [NewsletterController::class, 'confirm']);
        Route::post('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe']);
    });

    /* ------------------------- reader engagement ----------------------- */

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/comments', [CommentController::class, 'store'])->middleware('throttle:30,1');
        Route::post('/reactions/toggle', [ReactionController::class, 'toggle'])->middleware('throttle:60,1');

        Route::get('/bookmarks', [BookmarkController::class, 'index']);
        Route::post('/bookmarks/toggle', [BookmarkController::class, 'toggle']);

        Route::post('/polls/{poll}/vote', [PollController::class, 'vote']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    });

    /* --------------------------- admin workspace ----------------------- */

    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'role:super_admin,editor,author'])
        ->group(function (): void {
            Route::get('/dashboard', Admin\DashboardController::class)->name('admin.dashboard');

            Route::get('/articles', [Admin\ArticleController::class, 'index']);
            Route::post('/articles', [Admin\ArticleController::class, 'store']);
            Route::get('/articles/{article}', [Admin\ArticleController::class, 'show']);
            Route::put('/articles/{article}', [Admin\ArticleController::class, 'update']);
            Route::delete('/articles/{article}', [Admin\ArticleController::class, 'destroy']);
            Route::post('/articles/{article}/status', [Admin\ArticleController::class, 'changeStatus']);

            Route::get('/categories', [Admin\CategoryController::class, 'index']);
            Route::post('/categories', [Admin\CategoryController::class, 'store']);
            Route::put('/categories/{category}', [Admin\CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy']);

            Route::get('/tags', [Admin\TagController::class, 'index']);
            Route::post('/tags', [Admin\TagController::class, 'store']);
            Route::put('/tags/{tag}', [Admin\TagController::class, 'update']);
            Route::delete('/tags/{tag}', [Admin\TagController::class, 'destroy']);

            Route::get('/media', [Admin\MediaController::class, 'index']);
            Route::post('/media', [Admin\MediaController::class, 'store']);
            Route::put('/media/{media}', [Admin\MediaController::class, 'update']);
            Route::delete('/media/{media}', [Admin\MediaController::class, 'destroy']);

            Route::get('/pages', [Admin\PageController::class, 'index']);
            Route::post('/pages', [Admin\PageController::class, 'store']);
            Route::put('/pages/{page}', [Admin\PageController::class, 'update']);
            Route::delete('/pages/{page}', [Admin\PageController::class, 'destroy']);

            Route::get('/comments', [Admin\CommentController::class, 'index']);
            Route::post('/comments/{comment}/status', [Admin\CommentController::class, 'updateStatus']);
            Route::delete('/comments/{comment}', [Admin\CommentController::class, 'destroy']);

            Route::get('/polls', [Admin\PollController::class, 'index']);
            Route::post('/polls', [Admin\PollController::class, 'store']);
            Route::put('/polls/{poll}', [Admin\PollController::class, 'update']);
            Route::delete('/polls/{poll}', [Admin\PollController::class, 'destroy']);

            Route::get('/activity', Admin\ActivityController::class)->middleware('role:super_admin,editor');

            Route::get('/newsletter/subscribers', [Admin\SubscriberController::class, 'index'])
                ->middleware('role:super_admin,editor');
            Route::get('/newsletter/subscribers/export', [Admin\SubscriberController::class, 'export'])
                ->middleware('role:super_admin,editor');
            Route::delete('/newsletter/subscribers/{subscriber}', [Admin\SubscriberController::class, 'destroy'])
                ->middleware('role:super_admin,editor');

            Route::get('/users', [Admin\UserController::class, 'index'])->middleware('role:super_admin');
            Route::post('/users', [Admin\UserController::class, 'store'])->middleware('role:super_admin');
            Route::put('/users/{user}', [Admin\UserController::class, 'update'])->middleware('role:super_admin');
            Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->middleware('role:super_admin');
            Route::post('/users/{user}/ban', [Admin\UserController::class, 'ban'])->middleware('role:super_admin');
            Route::post('/users/{user}/unban', [Admin\UserController::class, 'unban'])->middleware('role:super_admin');

            Route::get('/settings', [Admin\SettingController::class, 'index'])->middleware('role:super_admin');
            Route::put('/settings', [Admin\SettingController::class, 'update'])->middleware('role:super_admin');
        });
});
