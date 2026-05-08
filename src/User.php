<?php

namespace Carpool;

/**
 * User class - Stub version (TDD Red Phase)
 *
 * Per the TDD cycle (Workshop Slide 37):
 *   Step 3 — Write a code stub that will FAIL the test.
 *   These stubs are intentionally incomplete.
 *   Run tests → they fail → then implement (Green Phase below).
 */
class User
{
    private array $users = []; // In-memory store (replace with DB calls in real app)

    /**
     * TDD RED: Stub — returns false always. Tests will fail.
     * TDD GREEN: Implement registration logic to pass tests.
     */
    public function register(string $name, string $email, string $password, string $role): array
    {
        // ── GREEN PHASE IMPLEMENTATION ──────────────────────────────────────
        // Validate name
        if (empty(trim($name)) || strlen($name) < 2 || strlen($name) > 50) {
            return ['success' => false, 'error' => 'Invalid name'];
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        // Check for duplicate email
        foreach ($this->users as $user) {
            if ($user['email'] === strtolower($email)) {
                return ['success' => false, 'error' => 'Email already registered'];
            }
        }

        // Validate password strength (min 8 chars, must have letter + number)
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return ['success' => false, 'error' => 'Password too weak'];
        }

        // Validate role
        if (!in_array($role, ['driver', 'passenger'])) {
            return ['success' => false, 'error' => 'Invalid role'];
        }

        // Store user (password hashed — security requirement from assessment)
        $id = count($this->users) + 1;
        $this->users[$id] = [
            'id'       => $id,
            'name'     => htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8'),
            'email'    => strtolower(trim($email)),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role'     => $role,
        ];

        return ['success' => true, 'user_id' => $id];
        // ── END GREEN PHASE ─────────────────────────────────────────────────
    }

    /**
     * TDD GREEN: Login — verify credentials.
     */
    public function login(string $email, string $password): array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === strtolower($email)) {
                if (password_verify($password, $user['password'])) {
                    return ['success' => true, 'user_id' => $user['id'], 'role' => $user['role']];
                }
                return ['success' => false, 'error' => 'Incorrect password'];
            }
        }
        return ['success' => false, 'error' => 'User not found'];
    }

    /**
     * TDD GREEN: Find user by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    /**
     * Returns total count of registered users.
     */
    public function count(): int
    {
        return count($this->users);
    }
}
