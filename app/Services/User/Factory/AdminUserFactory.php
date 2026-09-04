<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\User\Factory;

use App\Models\User;

class AdminUserFactory extends UserFactory
{
    public function create(array $data): User
    {
        return $this->createBaseUser($data, 'admin');
    }
}
