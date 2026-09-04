<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\User\Factory;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

abstract class UserFactory
{
    abstract public function create(array $data): User;

    protected function createBaseUser(array $data, string $role): User
    {
        return User::forceCreate([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }
}
