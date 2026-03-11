<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function index()
    {
        return User::query()
            ->select(['id', 'company_name', 'name', 'email', 'is_admin', 'status', 'credit_balance', 'credit_cap', 'must_change_password', 'created_at'])
            ->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'company_name' => 'required|string|max:255|unique:users,company_name',
            'email' => 'required|email|max:255|unique:users,email',
            'is_admin' => 'nullable|boolean',
            'credit_cap' => 'nullable|integer|min:0',
            'credit_balance' => 'nullable|integer|min:0',
        ]);

        $tempPassword = Str::password(12);

        $user = User::create([
            'name' => $payload['company_name'],
            'company_name' => $payload['company_name'],
            'email' => $payload['email'],
            'password' => Hash::make($tempPassword),
            'is_admin' => (bool) ($payload['is_admin'] ?? false),
            'status' => 'active',
            'credit_cap' => (int) ($payload['credit_cap'] ?? 0),
            'credit_balance' => (int) ($payload['credit_balance'] ?? 0),
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        Mail::raw(
            "Your Peldarg Extraction account is ready.\n\nCompany: {$user->company_name}\nLogin: https://extraction.peldargconsulting.com/login\nEmail: {$user->email}\nTemporary Password: {$tempPassword}\n\nPlease sign in and change your password immediately.",
            fn ($message) => $message->to($user->email)->subject('Peldarg Extractor Login Credentials')
        );

        return response()->json([
            'id' => $user->id,
            'company_name' => $user->company_name,
            'email' => $user->email,
            'status' => $user->status,
        ], 201);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'force_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('force_active')) {
            $user->status = 'active';
        }

        $tempPassword = Str::password(12);
        $user->password = Hash::make($tempPassword);
        $user->must_change_password = true;
        $user->save();

        Mail::raw(
            "Your password has been reset by Peldarg admin.\n\nCompany: {$user->company_name}\nLogin: https://extraction.peldargconsulting.com/login\nEmail: {$user->email}\nTemporary Password: {$tempPassword}\n\nPlease sign in and change your password immediately.",
            fn ($message) => $message->to($user->email)->subject('Peldarg Extractor Password Reset')
        );

        return response()->json(['ok' => true]);
    }
}
