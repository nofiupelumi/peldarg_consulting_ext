<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserAccountController extends Controller
{
    public function profile(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');
        $user = User::findOrFail($userId);

        $data = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->company_name = $data['company_name'] ?? null;
        $user->email = $data['email'];
        $user->save();

        return response()->json([
            'ok' => true,
            'user_name' => (string) ($user->company_name ?: $user->name ?: 'User'),
            'company_name' => (string) ($user->company_name ?: ''),
            'email' => (string) ($user->email ?: ''),
        ]);
    }

    public function password(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');
        $user = User::findOrFail($userId);

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:255',
        ]);

        if (!Hash::check($data['current_password'], (string) $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['ok' => true]);
    }
}
