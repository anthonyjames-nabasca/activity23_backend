<?php

namespace App\Http\Controllers;

use App\Models\AccountItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function store(Request $req)
    {
        try {
            $site = $req->site;
            $accountUsername = $req->account_username ?: $req->username;
            $accountPassword = $req->account_password ?: $req->password;

            if (!$site || !$accountUsername || !$accountPassword) {
                return response()->json([
                    'message' => 'site, username, and password are required.'
                ], 400);
            }

            $duplicate = AccountItem::where('user_id', $req->user()->user_id)
                ->where('site', $site)
                ->where('account_username', $accountUsername)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'This account item already exists for this user.'
                ], 409);
            }

            $imagePath = null;

            if ($req->hasFile('account_image')) {
                $storedPath = $req->file('account_image')->store('uploads/account', 'public');
                $imagePath = '/storage/' . $storedPath;
            }

            $item = AccountItem::create([
                'user_id' => $req->user()->user_id,
                'site' => $site,
                'account_username' => $accountUsername,
                'account_password' => $accountPassword,
                'account_image' => $imagePath,
            ]);

            return response()->json([
                'message' => 'Account item created successfully.',
                'account_id' => $item->account_id
            ], 201);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to create account item.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function index(Request $req)
    {
        try {
            $rows = AccountItem::select(
                'account_id',
                'user_id',
                'site',
                'account_username',
                'account_password',
                'account_image',
                'created_at',
                'updated_at'
            )
            ->where('user_id', $req->user()->user_id)
            ->orderByDesc('account_id')
            ->get()
            ->map(function ($item) use ($req) {
                $item->account_image = $this->publicFileUrl($req, $item->account_image);
                return $item;
            });

            return response()->json($rows, 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to fetch account items.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function show(Request $req, $id)
    {
        try {
            $item = AccountItem::select(
                'account_id',
                'user_id',
                'site',
                'account_username',
                'account_password',
                'account_image',
                'created_at',
                'updated_at'
            )
            ->where('account_id', $id)
            ->where('user_id', $req->user()->user_id)
            ->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Account item not found.'
                ], 404);
            }

            $item->account_image = $this->publicFileUrl($req, $item->account_image);

            return response()->json($item, 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to fetch account item.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function update(Request $req, $id)
    {
        try {
            $item = AccountItem::where('account_id', $id)
                ->where('user_id', $req->user()->user_id)
                ->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Account item not found.'
                ], 404);
            }

            $site = $req->site ?: $item->site;
            $accountUsername = ($req->account_username ?: $req->username) ?: $item->account_username;
            $accountPassword = ($req->account_password ?: $req->password) ?: $item->account_password;

            $duplicate = AccountItem::where('user_id', $req->user()->user_id)
                ->where('site', $site)
                ->where('account_username', $accountUsername)
                ->where('account_id', '!=', $id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'This account item already exists for this user.'
                ], 409);
            }

            $item->site = $site;
            $item->account_username = $accountUsername;
            $item->account_password = $accountPassword;

            if ($req->hasFile('account_image')) {
                if ($item->account_image) {
                    $oldPath = str_replace('/storage/', '', $item->account_image);
                    Storage::disk('public')->delete($oldPath);
                }

                $storedPath = $req->file('account_image')->store('uploads/account', 'public');
                $item->account_image = '/storage/' . $storedPath;
            }

            $item->save();

            return response()->json([
                'message' => 'Account item updated successfully.'
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to update account item.',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $req, $id)
    {
        try {
            $item = AccountItem::where('account_id', $id)
                ->where('user_id', $req->user()->user_id)
                ->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Account item not found.'
                ], 404);
            }

            if ($item->account_image) {
                $oldPath = str_replace('/storage/', '', $item->account_image);
                Storage::disk('public')->delete($oldPath);
            }

            $item->delete();

            return response()->json([
                'message' => 'Account item deleted successfully.'
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Failed to delete account item.',
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