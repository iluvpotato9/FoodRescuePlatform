<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\User\Factory;

use InvalidArgumentException;

class UserFactoryResolver
{
    public static function resolve(string $role): UserFactory
    {
        return match ($role) {
            'beneficiary' => new BeneficiaryUserFactory,
            'donor' => new DonorUserFactory,
            'driver' => new DriverUserFactory,
            'admin' => new AdminUserFactory,
            default => throw new InvalidArgumentException("Invalid user role: {$role}"),
        };
    }
}
