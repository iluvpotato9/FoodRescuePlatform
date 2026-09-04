<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\User\Factory;

use App\Models\BeneficiaryProfile;
use App\Models\User;

class BeneficiaryUserFactory extends UserFactory
{
    public function create(array $data): User
    {
        $user = $this->createBaseUser($data, 'beneficiary');

        BeneficiaryProfile::create([
            'user_id' => $user->id,
            'household_size' => $data['household_size'] ?? 1,
            'dietary_needs' => $data['dietary_needs'] ?? null,
            'income_level' => $data['income_level'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
        ]);

        return $user->load('beneficiaryProfile');
    }
}
