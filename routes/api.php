<?php

use App\Http\Controllers\API\AdminAuthController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookmarkController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\PostLikeController;
use App\Http\Controllers\API\TagController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::middleware('is.admin')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::post('/me/update', [AdminAuthController::class, 'updateProfileAdmin']);
        Route::post('/me/changePassword', [AdminAuthController::class, 'changePasswordAdmin']);
        //Author
        Route::post('author/register', [AdminAuthController::class, 'registerAuthor']);
        Route::get('/authors', [AdminAuthController::class, 'listAuthors']);
        Route::get('/author/{id}', [AdminAuthController::class, 'showAuthor']);
        Route::delete('author/{id}', [AdminAuthController::class, 'destroyAuthor']);
        //Category
        Route::post('categories/add', [CategoryController::class, 'store']);
        Route::get('categories/{id}', [CategoryController::class, 'show']);
        Route::post('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
        //Tag
        Route::post('tags/add', [TagController::class, 'store']);
        Route::get('tags/{id}', [TagController::class, 'show']);
        Route::post('tags/{id}', [TagController::class, 'update']);
        Route::delete('tags/{id}', [TagController::class, 'destroy']);
        //Post
        Route::post('posts/add', [PostController::class, 'store']);
        Route::put('posts/{id}', [PostController::class, 'update']);
        Route::delete('posts/{id}', [PostController::class, 'destroy']);
        //Comments
        Route::delete('admin/comments/{id}', [CommentController::class, 'destroyByAdmin']);
        //Users
        Route::post('users', [AuthController::class, 'index']);
        Route::post('users/{id}', [AuthController::class, 'show']);
        Route::delete('users/{id}', [AuthController::class, 'destroy']);
    });
});

Route::prefix('author')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'loginAuthor']);
    Route::middleware('is.author')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'meAuthor']);
        Route::post('/me/changePassword', [AdminAuthController::class, 'changePasswordAuthor']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::post('/update', [AdminAuthController::class, 'updateProfileAuthor']);
        Route::post('posts/add', [PostController::class, 'store']);
        Route::post('posts/{id}', [PostController::class, 'update']);
        Route::delete('posts/{id}', [PostController::class, 'destroy']);
        Route::post('admin/comments/{commentId}/reply', [CommentController::class, 'replyToComment']);
    });
});

Route::post('register', [AuthController::class, 'register']);

Route::middleware('is.user')->group(function () {
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link verifikasi telah dikirim']);
    });

    // Verifikasi email
    Route::get('/verify-email/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return response()->json(['message' => 'Email berhasil diverifikasi']);
    })->name('verification.verify');

    Route::middleware('is.verified')->get('/users-only-access', function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
        Route::post('/reset-password', [AuthController::class, 'reset']);

        Route::post('update', [AuthController::class, 'update']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::post('posts/{postId}/comments', [CommentController::class, 'store']);
        Route::delete('comments/{id}', [CommentController::class, 'destroy']);

        Route::post('comments/{id}/like', [CommentController::class, 'like']);
        Route::delete('comments/{id}/like', [CommentController::class, 'unlike']);

        Route::get('bookmarks', [BookmarkController::class, 'index']);
        Route::post('bookmarks/{postId}', [BookmarkController::class, 'store']);
        Route::delete('bookmarks/{postId}', [BookmarkController::class, 'destroy']);

        Route::post('posts/{postId}/like', [PostLikeController::class, 'like']);
        Route::delete('posts/{postId}/like', [PostLikeController::class, 'unlike']);
    });
});

Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{slug}/posts', [CategoryController::class, 'postBySlug']);

Route::get('tags', [TagController::class, 'index']);
Route::get('tags/{slug}/posts', [TagController::class, 'postBySlug']);

Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{id}', [PostController::class, 'show']);

Route::get('posts/{postId}/comments', [CommentController::class, 'index']);
Route::get('likes', [PostLikeController::class, 'index']);

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (! URL::hasValidSignature($request)) {
        return response()->json(['message' => 'Link tidak valid atau sudah kedaluwarsa.'], 403);
    }

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Hash tidak cocok.'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email sudah diverifikasi.'], 200);
    }

    $user->markEmailAsVerified();
    event(new Verified($user));

    return response()->json(['message' => 'Email berhasil diverifikasi!']);
})->name('verification.verify');
