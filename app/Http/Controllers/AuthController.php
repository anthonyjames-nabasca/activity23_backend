<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        try {
            $username = $req->username;
            $fullname = $req->fullname;
            $email = $req->email;
            $password = $req->password;

            if (!$username || !$fullname || !$email || !$password) {
                return response()->json([
                    'message' => 'username, fullname, email, and password are required.'
                ], 400);
            }

            $existing = User::where('username', $username)
                ->orWhere('email', $email)
                ->exists();

            if ($existing) {
                return response()->json([
                    'message' => 'Username or email already exists.'
                ], 409);
            }

            $verificationToken = Str::random(64);

            $user = User::create([
                'username' => $username,
                'fullname' => $fullname,
                'email' => $email,
                'password' => Hash::make($password),
                'profile_image' => null,
                'token' => '',
                'is_verified' => 0,
                'verification_token' => $verificationToken,
                'reset_token' => null,
                'reset_token_expires' => null,
            ]);

            Mail::to($user->email)->send(new VerifyEmailMail($user));

            return response()->json([
                'message' => 'Registration successful. Please check your email to verify your account.'
            ], 201);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to register user.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function verifyEmail(Request $req)
    {
        try {
            $token = $req->query('token');

            if (!$token) {
                return response($this->verificationErrorHtml(), 400)
                    ->header('Content-Type', 'text/html');
            }

            $user = User::where('verification_token', $token)->first();

            if (!$user) {
                return response($this->verificationErrorHtml(), 400)
                    ->header('Content-Type', 'text/html');
            }

            $user->is_verified = 1;
            $user->verification_token = null;
            $user->save();

            return response($this->verificationSuccessHtml(), 200)
                ->header('Content-Type', 'text/html');
        } catch (\Throwable $error) {
            return response($this->verificationErrorHtml(), 500)
                ->header('Content-Type', 'text/html');
        }
    }

    public function login(Request $req)
    {
        try {
            $identifier = $req->identifier ?: ($req->username ?: $req->email);
            $password = $req->password;

            if (!$identifier || !$password) {
                return response()->json([
                    'message' => 'username/email and password are required.'
                ], 400);
            }

            $user = User::where('username', $identifier)
                ->orWhere('email', $identifier)
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Invalid login credentials.'
                ], 401);
            }

            if (!$user->is_verified) {
                return response()->json([
                    'message' => 'Your account is not verified. Please verify your email first.'
                ], 403);
            }

            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid login credentials.'
                ], 401);
            }

            $user->tokens()->delete();

            $token = $user->createToken('token')->plainTextToken;

            $user->token = $token;
            $user->save();

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'profile_image' => $this->publicFileUrl($req, $user->profile_image),
                ]
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Login failed.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function logout(Request $req)
    {
        try {
            $user = $req->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Invalid or expired token.'
                ], 401);
            }

            $user->tokens()->delete();
            $user->token = '';
            $user->save();

            return response()->json([
                'message' => 'Logout successful.'
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Logout failed.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function forgotPassword(Request $req)
    {
        try {
            $email = $req->email;

            if (!$email) {
                return response()->json([
                    'message' => 'Email is required.'
                ], 400);
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                $user->reset_token = Str::random(64);
                $user->reset_token_expires = now()->addHours(24);
                $user->save();

                Mail::to($user->email)->send(new ResetPasswordMail($user));
            }

            return response()->json([
                'message' => 'If the email exists, a password reset link has been sent.'
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Forgot password failed.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $req)
    {
        try {
            $token = $req->token;
            $newPassword = $req->newPassword;

            if (!$token || !$newPassword) {
                return response()->json([
                    'message' => 'token and newPassword are required.'
                ], 400);
            }

            $user = User::where('reset_token', $token)
                ->where('reset_token_expires', '>', now())
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Reset token is invalid or expired.'
                ], 400);
            }

            $user->password = Hash::make($newPassword);
            $user->reset_token = null;
            $user->reset_token_expires = null;
            $user->token = '';
            $user->save();

            return response()->json([
                'message' => 'Password has been reset successfully.'
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Reset password failed.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    private function publicFileUrl(Request $req, ?string $relativePath): ?string
    {
        if (!$relativePath) return null;
        if (str_starts_with($relativePath, 'http')) return $relativePath;
        return $req->getSchemeAndHttpHost() . $relativePath;
    }

    private function verificationSuccessHtml(): string
    {
        $loginUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/login';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Email Verified</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif;">
<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f7fb; min-height:100vh;">
<tr>
<td align="center" valign="middle">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg, #16a34a, #15803d); padding:26px 30px; text-align:center;">
<h1 style="margin:0; color:#ffffff; font-size:24px;">Email Verified</h1>
</td></tr>
<tr><td style="padding:40px 30px; text-align:center;">
<div style="font-size:52px; margin-bottom:14px;">✅</div>
<p style="margin:0 0 24px; color:#374151; font-size:16px; line-height:1.7;">
Thank you. Your email address has been successfully verified. You may now continue to the login page and access your account.
</p>
<a href="{$loginUrl}" style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; font-size:16px; font-weight:700; padding:14px 28px; border-radius:10px;">Go to Login</a>
</td></tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
    }

    private function verificationErrorHtml(): string
    {
        $loginUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/login';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Verification Failed</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif;">
<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f7fb; min-height:100vh;">
<tr>
<td align="center" valign="middle">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg, #dc2626, #b91c1c); padding:26px 30px; text-align:center;">
<h1 style="margin:0; color:#ffffff; font-size:24px;">Verification Failed</h1>
</td></tr>
<tr><td style="padding:40px 30px; text-align:center;">
<div style="font-size:52px; margin-bottom:14px;">⚠️</div>
<p style="margin:0 0 24px; color:#374151; font-size:16px; line-height:1.7;">
This verification link is invalid, expired, or has already been used. Please try registering again or request a new verification email.
</p>
<a href="{$loginUrl}" style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; font-size:16px; font-weight:700; padding:14px 28px; border-radius:10px;">Go to Login</a>
</td></tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
    }
}