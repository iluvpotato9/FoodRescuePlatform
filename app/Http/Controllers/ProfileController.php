<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user()->load('beneficiaryProfile'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:25', new PhoneNumber],
            'address' => ['nullable', 'string', 'max:500'],
            'household_size' => ['required', 'integer', 'min:1', 'max:30'],
            'dietary_needs' => ['nullable', 'string', 'max:1000'],
            'income_level' => ['nullable', 'string', 'max:100'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name' => $validated['name'],
                'email' => Str::lower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $user->beneficiaryProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'household_size' => $validated['household_size'],
                    'dietary_needs' => $validated['dietary_needs'] ?? null,
                    'income_level' => $validated['income_level'] ?? null,
                    'emergency_contact' => $validated['emergency_contact'] ?? null,
                ]
            );
        });

        return back()->with('success', 'Your profile was updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        Auth::logout();

        DB::transaction(function () use ($user) {
            $user->notifications()->delete();
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Your account and personal data were deleted.');
    }
}
