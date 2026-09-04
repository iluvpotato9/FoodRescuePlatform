<?php

/**
 * Module: User and Authentication Module
 * Author: Syed Raiz (Student ID: 2410921)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = trim((string) $value);

        if (! preg_match('/^\+?[0-9\s-]+$/', $phone)) {
            $fail('The :attribute may only contain numbers, spaces, hyphens, and an optional + at the beginning.');

            return;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $fail('The :attribute must contain between 7 and 15 digits.');
        }
    }
}