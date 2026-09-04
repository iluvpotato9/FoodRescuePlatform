<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers;

use App\Rules\NotCommonPassword;
use App\Rules\PhoneNumber;
use App\Services\User\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $key = 'login:'.hash('sha256', $credentials['email'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'The email address or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, '.Auth::user()->name.'.');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                'max:128',
                Password::min(15),
                new NotCommonPassword,
            ],
            'role' => ['required', 'in:beneficiary,donor,driver'],
            'phone' => ['nullable', 'string', 'max:25', new PhoneNumber],
            'address' => ['nullable', 'string', 'max:500'],
            'household_size' => ['required_if:role,beneficiary', 'nullable', 'integer', 'min:1', 'max:30'],
            'dietary_needs' => ['nullable', 'string', 'max:500'],
            'income_level' => ['nullable', 'in:low,moderate,prefer_not_to_say'],
            'emergency_contact' => ['nullable', 'string', 'max:25', new PhoneNumber],
            'terms' => ['accepted'],
        ]);

        $validated['email'] = Str::lower(trim($validated['email']));
        $user = $this->authService->register($validated);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Your account has been created successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }
}
