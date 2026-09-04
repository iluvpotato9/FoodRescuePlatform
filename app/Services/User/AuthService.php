<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\User;

use App\Models\User;
use App\Services\User\Factory\UserFactoryResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $factory = UserFactoryResolver::resolve($data['role']);

            return $factory->create($data);
        });
    }

    public function login(array $credentials): User
    {
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        request()->session()->regenerate();

        return Auth::user();
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    public function updateProfile(User $user, array $data): User
    {
        $updateData = collect($data)->only(['name', 'email', 'phone', 'address'])->filter()->toArray();

        if (isset($updateData['email'])) {
            $updateData['email'] = Str::lower(trim($updateData['email']));
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        if ($user->isBeneficiary() && isset($data['household_size'])) {
            $user->beneficiaryProfile?->update(
                collect($data)->only(['household_size', 'dietary_needs', 'income_level', 'emergency_contact'])->filter()->toArray()
            );
        }

        return $user->fresh()->load('beneficiaryProfile');
    }

    public function updateRole(User $user, string $role): User
    {
        $user->forceFill(['role' => $role])->save();

        if ($role === 'beneficiary' && ! $user->beneficiaryProfile()->exists()) {
            $user->beneficiaryProfile()->create([
                'household_size' => 1,
                'income_level' => 'prefer_not_to_say',
            ]);
        }

        return $user->fresh();
    }
}
