<?php

namespace Carpool;

/**
 * InputValidator class
 *
 * Handles all user-input sanitisation and security checks.
 *
 * Assessment requirement (Phase 4):
 *   "Security Testing: Identifying vulnerabilities and implementing defences."
 *   "Software Security Check: Authentication, authorization, encryption, and privacy measures."
 *
 * Defends against:
 *   - SQL Injection
 *   - XSS (Cross-Site Scripting)
 *   - Weak passwords
 *   - Malformed input
 */
class InputValidator
{
    /**
     * Sanitise a plain text string (XSS prevention).
     */
    public function sanitiseText(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check whether a string contains SQL injection patterns.
     *
     * @return bool true if suspicious patterns found (input is UNSAFE)
     */
    public function containsSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bOR\b|\bAND\b)\s+[\'\d]/i',   // OR 1=1 / AND '1'='1'
            '/--/',                              // comment terminator
            '/;/',                               // statement terminator
            '/DROP\s+TABLE/i',
            '/INSERT\s+INTO/i',
            '/SELECT\s+.*FROM/i',
            '/UNION\s+SELECT/i',
            '/\bEXEC\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether a string contains XSS patterns.
     *
     * @return bool true if suspicious patterns found (input is UNSAFE)
     */
    public function containsXss(string $input): bool
    {
        $patterns = [
            '/<script[\s\S]*?>[\s\S]*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',        // onclick=, onerror=, etc.
            '/<iframe/i',
            '/<object/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate password strength.
     *
     * Rules (security requirement):
     *   - Minimum 8 characters
     *   - At least one uppercase letter
     *   - At least one lowercase letter
     *   - At least one digit
     *   - At least one special character
     *
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain an uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain a lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain a digit';
        }
        if (!preg_match('/[\W_]/', $password)) {
            $errors[] = 'Password must contain a special character';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Validate an email address.
     */
    public function validateEmail(string $email): bool
    {
        return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate a UK postcode (simplified, for Cardiff campus routes).
     */
    public function validateUkPostcode(string $postcode): bool
    {
        $cleaned = strtoupper(preg_replace('/\s+/', '', $postcode));
        return (bool) preg_match('/^[A-Z]{1,2}[0-9][0-9A-Z]?[0-9][A-Z]{2}$/', $cleaned);
    }
}
