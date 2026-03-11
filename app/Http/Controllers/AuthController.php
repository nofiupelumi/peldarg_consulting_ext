<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Session::has('authenticated') && Session::get('authenticated') === true) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // Database authentication
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            if ($user->status !== 'active') {
                return back()->withErrors(['email' => 'Account is suspended. Please contact admin.'])->withInput();
            }

            Session::put('authenticated', true);
            Session::put('user_id', $user->id);
            Session::put('user_email', $user->email);
            Session::put('user_name', $user->company_name ?: $user->name);
            Session::put('is_admin', (bool) $user->is_admin);
            
            return redirect()->route('dashboard')->with('success', 'Welcome back, ' . ($user->company_name ?: $user->name) . '!');
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        Session::flush();
        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}
