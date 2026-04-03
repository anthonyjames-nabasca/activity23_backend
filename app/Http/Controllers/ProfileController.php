<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AccountItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $req)
    {
        try {
            $user = User::select(
                'user_id',
                'username',
                'fullname',
                'email',
                'profile_image',
                'is_verified',
                'created_at',
                'updated_at'
            )
            ->where('user_id', $req->user()->user_id)
            ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            $user->profile_image = $this->publicFileUrl($req, $user->profile_image);

            $accountCount = AccountItem::where('user_id', $user->user_id)->count();

            return response()->json([
                'user' => $user,
                'account_count' => $accountCount
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to fetch profile.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function update(Request $req)
    {
        try {
            $user = User::where('user_id', $req->user()->user_id)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            $username = $req->username ?: $user->username;
            $fullname = $req->fullname ?: $user->fullname;
            $email = $req->email ?: $user->email;

            $usernameExists = User::where('username', $username)
                ->where('user_id', '!=', $user->user_id)
                ->exists();

            if ($usernameExists) {
                return response()->json([
                    'message' => 'Username is already taken.'
                ], 409);
            }

            $emailExists = User::where('email', $email)
                ->where('user_id', '!=', $user->user_id)
                ->exists();

            if ($emailExists) {
                return response()->json([
                    'message' => 'Email is already taken.'
                ], 409);
            }

            $user->username = $username;
            $user->fullname = $fullname;
            $user->email = $email;

            if ($req->password) {
                $user->password = Hash::make($req->password);
            }

            if ($req->hasFile('profile_image')) {
                if ($user->profile_image) {
                    $oldPath = str_replace('/storage/', '', $user->profile_image);
                    Storage::disk('public')->delete($oldPath);
                }

                $storedPath = $req->file('profile_image')->store('uploads/profile', 'public');
                $user->profile_image = '/storage/' . $storedPath;
            }

            $user->save();

            $user->profile_image = $this->publicFileUrl($req, $user->profile_image);
            $accountCount = AccountItem::where('user_id', $user->user_id)->count();

            return response()->json([
                'message' => 'Profile updated successfully.',
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image,
                    'is_verified' => $user->is_verified,
                ],
                'account_count' => $accountCount
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to update profile.',
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
}