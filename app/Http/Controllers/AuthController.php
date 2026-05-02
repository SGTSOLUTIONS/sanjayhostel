<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatusEnum;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /** Show Login Page */
    public function showLogin()
    {
        // if (Auth::check()) {
        //     return redirect()->route('admin.dashboard'); // or route('dashboards')
        // }

        return view('auth.login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.register');
    }

    /** Handle Login (AJAX) */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No account found with this email.'
            ], 404);
        }

        // Ensure user is active
        if ($user->status !== ActiveStatusEnum::ACTIVE->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your account is not active. Please contact the administrator.'
            ], 403);
        }

        if (Auth::attempt($validated)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Convert string → Enum
            $role = RoleEnum::from($user->role);

            // Role → Route mapping
            $redirectRoutes = [
                RoleEnum::ADMIN->value => 'admin.dashboard',
                RoleEnum::SUPERADMIN->value => 'admin.dashboard',
            ];

            $redirect = route($redirectRoutes[$role->value] ?? 'home');

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful!',
                'redirect' => $redirect,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid password.'
        ], 401);
    }



    /** Show Register Page */


    /** Handle Register (AJAX) */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'city' => 'required|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle file upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = $request->email . $file->getClientOriginalName();

            // Store in public/profile directory
            $filePath = $file->move('profile', $filename, 'public');
            $validated['profile_picture'] = $filePath;
        }

        // Hash password
        $validated['profile'] = $filePath ?? null;
        $validated['password'] = Hash::make($validated['password']);

        // Create user
        $user = User::create($validated);

        // You can add login logic here if needed
        // Auth::login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful! Welcome to our community.',
            'redirect' => route('login') // or admin.dashboard
        ]);
    }


    /** Dashboard (after login) */

    public function dashboards()
    {
        return view('admin.dashboard');
    }

    /** Logout */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }


    // Show Forgot Password Page
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // Send Password Reset Link

    // Show Reset Password Page
    public function showResetPassword(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    // Reset Password
   public function sendResetLink(Request $request)
{
    $request->validate(['email' => 'required|email|exists:users,email']);

    try {
        $user = User::where('email', $request->email)->first();

        // Generate reset token
        $token = Password::createToken($user);

        // Build reset URL (mobile-friendly)
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email
        ]));

        // Send email
        Mail::send('emails.password-reset', [
            'resetUrl' => $resetUrl,
            'user' => $user
        ], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Reset Your Password - ' . config('app.name'))
                    ->from(config('mail.from.address'), config('mail.from.name'));
        });

        return response()->json([
            'status' => 'success',
            'message' => 'We have emailed your password reset link! Please check your inbox.'
        ]);

    } catch (\Exception $e) {
        Log::error('Password reset error: ' . $e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to send reset link. Please try again later.'
        ], 500);
    }
}

/**
 * Reset Password (Enhanced)
 */
public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email|exists:users,email',
        'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
    ], [
        'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));

            $user->save();

            // Log the password reset
            Log::info('Password reset for user: ' . $user->email);

            event(new PasswordReset($user));
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'status' => 'success',
            'message' => 'Your password has been reset successfully! Please login with your new password.',
            'redirect' => route('login')
        ]);
    }

    return response()->json([
        'status' => 'error',
        'message' => __($status)
    ], 400);
}
}
