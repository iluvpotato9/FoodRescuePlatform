<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotCommonPassword implements ValidationRule
{
    private const COMMON_PASSWORDS = [
        'password',
        'password123',
        'password1234',
        'qwerty123',
        '123456789',
        '1234567890',
        'letmein',
        'welcome123',
        'admin123',
        'iloveyou',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = mb_strtolower(trim((string) $value));

        if (in_array($normalized, self::COMMON_PASSWORDS, true)) {
            $fail('Choose a password that is not commonly used or easily guessed.');
        }
    }
}
