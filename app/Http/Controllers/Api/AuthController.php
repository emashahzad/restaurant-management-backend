<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Step 1: Validate registration data
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        // Step 2: Hash password
        $data['password'] = Hash::make($data['password']);

        // Step 3: Create user
        $user = User::create($data);

        // Step 4: Assign default role
        $user->assignRole('user');

        // Step 5: Send email verification notification
        $user->sendEmailVerificationNotification();

        // Step 6: Return response
        return response()->json([
            'message' => 'Registration successful! Please verify your email before logging in.',
            'email_verification_required' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid Email or Password'
            ], 401);
        }

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address before logging in.',
                'email_verification_required' => true,
            ], 403);
        }

        $token = $user->createToken('user-api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login Successfull',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout Successfull',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        // Step 1: Validate email
        $request->validate([
            'email' => 'required|email',
        ]);

        // Step 2: Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Step 3: Check result
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 200);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }

    public function resetPassword(Request $request)
    {
        // Step 1: Validate request
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Step 2: Prepare credentials
        $credentials = $request->only(
            'email',
            'password',
            'password_confirmation',
            'token'
        );

        // Step 3: Reset password
        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        // Step 4: Return response
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.',
            ], 200);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }

    public function resendVerificationEmail(Request $request)
    {
        // Step 1: Validate email
        $request->validate([
            'email' => 'required|email',
        ]);

        // Step 2: Find user
        $user = User::where('email', $request->email)->first();

        // Step 3: Send verification email if user exists
        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Step 4: Return response
        return response()->json([
            'message' => 'If your account exists and is not verified, a verification email has been sent.'
        ], 200);
    }

    // ==========================================
    // GOOGLE LOGIN
    // ==========================================

    public function googleRedirect()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $googleProvider */
        $googleProvider = Socialite::driver('google');

        return $googleProvider->stateless()->redirect();
    }

    public function googleCallback()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $googleProvider */
        $googleProvider = Socialite::driver('google');

        $googleUser = $googleProvider->stateless()->user();

        $email = $googleUser->getEmail();

        $emailVerified = $googleUser->user['email_verified']
            ?? $googleUser->user['verified_email']
            ?? false;

        if (!$email || !filter_var($emailVerified, FILTER_VALIDATE_BOOLEAN)) {
            return redirect(config('services.frontend_url') . '/login?error=google_email_not_verified');
        }

        // Find existing Google account
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {

            // Check existing email account
            $user = User::where('email', $email)->first();

            if ($user) {

                // Prevent linking a different Google account
                if ($user->google_id && $user->google_id !== $googleUser->getId()) {
                    return redirect(config('services.frontend_url') . '/login?error=google_account_conflict');
                }

                $user->google_id = $googleUser->getId();
                $user->email_verified_at = $user->email_verified_at ?? now();
                $user->save();

            } else {

                // Create new Google user
                $user = new User();
                $user->name = $googleUser->getName() ?? 'Google User';
                $user->email = $email;
                $user->password = Hash::make(Str::random(40));
                $user->google_id = $googleUser->getId();
                $user->email_verified_at = Carbon::now();
                $user->save();

                $user->assignRole('user');
            }
        }

        // Generate one-time exchange code
        $code = Str::random(64);

        Cache::put(
            'google_login:' . $code,
            $user->id,
            Carbon::now()->addMinutes(2)
        );

        // Redirect to React with temporary code
        return redirect(
            config('services.frontend_url') . '/google-callback?code=' . urlencode($code)
        );
    }

    public function googleExchange(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        // Retrieve and delete one-time code
        $userId = Cache::pull('google_login:' . $request->code);

        if (!$userId) {
            return response()->json([
                'message' => 'Invalid or expired Google login code.',
            ], 401);
        }

        // Find user
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Get roles in the same format as email login
        $roles = $user->getRoleNames();

        return response()->json([
            'message' => 'Google Login Successful',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
            ],

            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    // ==========================================
    // FACEBOOK LOGIN
    // ==========================================

    public function facebookRedirect()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $facebookProvider */
        $facebookProvider = Socialite::driver('facebook');

        return $facebookProvider->stateless()->redirect();
    }



    
public function facebookCallback(Request $request)
{
    // Handle Facebook login cancellation
    if ($request->has('error')) {

        if ($request->query('error') === 'access_denied') {
            return redirect(
                config('services.frontend_url') . '/login'
            );
        }

        return redirect(
            config('services.frontend_url') . '/login?error=facebook_login_failed'
        );
    }

    /** @var \Laravel\Socialite\Two\AbstractProvider $facebookProvider */
    $facebookProvider = Socialite::driver('facebook');

    $facebookUser = $facebookProvider->stateless()->user();

    $facebookId = $facebookUser->getId();
    $email = $facebookUser->getEmail();

    // Facebook must provide an email
    if (!$email) {
        return redirect(
            config('services.frontend_url') . '/login?error=facebook_email_not_available'
        );
    }

    // Find account already linked with this Facebook ID
    $user = User::where('facebook_id', $facebookId)->first();

    if (!$user) {

        // Check whether this email already belongs to another account
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            return redirect(
                config('services.frontend_url') . '/login?error=facebook_account_conflict'
            );
        }

        // Create new Facebook user
        $user = new User();

        $user->name = $facebookUser->getName() ?? 'Facebook User';
        $user->email = $email;
        $user->password = Hash::make(Str::random(40));
        $user->facebook_id = $facebookId;

        $user->save();

        // Assign default role
        $user->assignRole('user');
    }

    // Mark email verified for Facebook social login only
    if (!$user->hasVerifiedEmail()) {
        $user->email_verified_at = Carbon::now();
        $user->save();
    }

    // Generate one-time exchange code
    $code = Str::random(64);

    Cache::put(
        'facebook_login:' . $code,
        $user->id,
        Carbon::now()->addMinutes(2)
    );

    // Redirect to React with temporary code
    return redirect(
        config('services.frontend_url') . '/facebook-callback?code=' . urlencode($code)
    );
}





    public function facebookExchange(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        // Retrieve and delete one-time code
        $userId = Cache::pull('facebook_login:' . $request->code);

        if (!$userId) {
            return response()->json([
                'message' => 'Invalid or expired Facebook login code.',
            ], 401);
        }

        // Find user
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Get roles
        $roles = $user->getRoleNames();

        return response()->json([
            'message' => 'Facebook Login Successful',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
            ],

            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

}
