<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;


Route::post('/google/exchange', [AuthController::class, 'googleExchange'])
    ->middleware('throttle:10,1');

Route::post('/facebook/exchange', [AuthController::class, 'facebookExchange'])
    ->middleware('throttle:10,1');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // Step 1: Find user
    $user = User::findOrFail($id);

    // Step 2: Verify email hash
    if (
        !hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )
    ) {
        abort(403, 'Invalid verification link.');
    }

    // Step 3: Mark email as verified
    if ($user->markEmailAsVerified()) {

        Mail::to($user->email)->send(
            new WelcomeMail($user->name)
        );

    }

    // Step 4: Redirect to React frontend
    return redirect()->away(
        rtrim(config('app.frontend_url'), '/') .
        '/email-verified?status=success'
    );
})->middleware([
            'signed',
            'throttle:6,1'
        ])->name('verification.verify');


Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
    ->middleware('throttle:6,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum', 'verified')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/{menu}', [MenuController::class, 'show']);

    Route::middleware('role:user')->group(function () {
        Route::get('/user-test', function () {
            return response()->json([
                'message' => 'Welcome user'
            ]);
        });
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/menus', [MenuController::class, 'store']);
        Route::put('/menus/{menu}', [MenuController::class, 'update']);
        Route::patch('/menus/{menu}', [MenuController::class, 'patch']);
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);
        Route::get('/menus-trash', [MenuController::class, 'trash']);
        Route::post('/menus/{menu}/restore', [MenuController::class, 'restore']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}', [UserController::class, 'patch']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::get('/users-trash', [UserController::class, 'trash']);
        Route::post('/users/{user}/restore', [UserController::class, 'restore']);
        Route::delete('/users/{id}/force', [UserController::class, 'forceDelete']);
        Route::delete('/menus/{id}/force', [MenuController::class, 'forceDelete']);

    });
});