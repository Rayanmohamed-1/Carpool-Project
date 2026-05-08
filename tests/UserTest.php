<?php

namespace Carpool\Tests;

use Carpool\User;
use PHPUnit\Framework\TestCase;

/**
 * UserTest — TDD tests for User registration and login.
 *
 * Based on SEN5002 Workshop (Testing slides):
 *   - Arrange → Action → Assert structure (Slide 26 / Program 9.2)
 *   - Equivalence partitions: valid inputs, boundary cases, invalid inputs (Slide 8 / Table 9.3)
 *   - Tests written FIRST per TDD cycle (Slide 37 / Table 9.9)
 *
 * TDD Cycle for this class:
 *   RED   → Run these tests against stub src/User.php → they FAIL
 *   GREEN → Implement User.php logic → all tests PASS
 *   REFACTOR → Clean up code, re-run tests to confirm still green
 *
 * Equivalence Partitions (Table 9.3 style):
 *   EP1  Valid registration   — all fields correct
 *   EP2  Invalid name         — too short, too long, or empty
 *   EP3  Invalid email        — missing @, wrong format
 *   EP4  Duplicate email      — same email registered twice
 *   EP5  Weak password        — fewer than 8 chars / no digit
 *   EP6  Invalid role         — not 'driver' or 'passenger'
 *   EP7  Valid login          — correct credentials
 *   EP8  Wrong password login — correct email, wrong password
 *   EP9  Unknown email login  — email not in system
 */
class UserTest extends TestCase
{
    private User $user;

    /** Called before each test — fresh User instance (no shared state). */
    protected function setUp(): void
    {
        $this->user = new User();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP1 — VALID REGISTRATION
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_valid_driver_registration_succeeds(): void
    {
        // Arrange
        $name     = 'Rayan Mohamed';
        $email    = 'rayan@cardiffmet.ac.uk';
        $password = 'Secure99!';
        $role     = 'driver';

        // Action
        $result = $this->user->register($name, $email, $password, $role);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
    }

    /** @test */
    public function test_valid_passenger_registration_succeeds(): void
    {
        // Arrange
        $name     = 'Ali Hassan';
        $email    = 'ali@cardiffmet.ac.uk';
        $password = 'Pass1234!';
        $role     = 'passenger';

        // Action
        $result = $this->user->register($name, $email, $password, $role);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
    }

    /** @test */
    public function test_registered_user_can_be_found_by_id(): void
    {
        // Arrange
        $result = $this->user->register('Test User', 'test@cardiffmet.ac.uk', 'Test1234!', 'driver');

        // Action
        $found = $this->user->findById($result['user_id']);

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals('test@cardiffmet.ac.uk', $found['email']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP2 — INVALID NAME (boundary + incorrect partition)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_empty_name_fails_registration(): void
    {
        // Arrange
        $name = '';

        // Action
        $result = $this->user->register($name, 'a@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function test_single_character_name_fails_registration(): void
    {
        // Arrange — boundary edge: name length = 1 (below minimum of 2)
        $name = 'A';

        // Action
        $result = $this->user->register($name, 'a@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_name_over_50_characters_fails_registration(): void
    {
        // Arrange — boundary edge: name too long
        $name = str_repeat('A', 51);

        // Action
        $result = $this->user->register($name, 'a@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP3 — INVALID EMAIL FORMAT
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_email_without_at_symbol_fails_registration(): void
    {
        // Arrange
        $email = 'rayanATcardiffmet.ac.uk';

        // Action
        $result = $this->user->register('Rayan', $email, 'Pass1234!', 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_email_without_domain_fails_registration(): void
    {
        // Arrange
        $email = 'rayan@';

        // Action
        $result = $this->user->register('Rayan', $email, 'Pass1234!', 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP4 — DUPLICATE EMAIL
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_registering_same_email_twice_fails(): void
    {
        // Arrange — register first time
        $this->user->register('Rayan Mohamed', 'rayan@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Action — attempt second registration with same email
        $result = $this->user->register('Another Name', 'rayan@cardiffmet.ac.uk', 'Pass5678!', 'passenger');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already', strtolower($result['error']));
    }

    /** @test */
    public function test_duplicate_email_check_is_case_insensitive(): void
    {
        // Arrange
        $this->user->register('Rayan', 'Rayan@CardiffMet.ac.uk', 'Pass1234!', 'driver');

        // Action
        $result = $this->user->register('Rayan2', 'rayan@cardiffmet.ac.uk', 'Pass5678!', 'passenger');

        // Assert
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP5 — WEAK PASSWORD
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_password_shorter_than_8_chars_fails(): void
    {
        // Arrange — boundary edge: 7 characters
        $password = 'Ab1!xyz';

        // Action
        $result = $this->user->register('Rayan', 'r@cardiffmet.ac.uk', $password, 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_password_with_no_digit_fails(): void
    {
        // Arrange
        $password = 'OnlyLetters!';

        // Action
        $result = $this->user->register('Rayan', 'r@cardiffmet.ac.uk', $password, 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP6 — INVALID ROLE
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_invalid_role_fails_registration(): void
    {
        // Arrange
        $role = 'admin'; // Not an accepted role

        // Action
        $result = $this->user->register('Rayan', 'r@cardiffmet.ac.uk', 'Pass1234!', $role);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('role', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP7 — VALID LOGIN
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_valid_login_with_correct_credentials_succeeds(): void
    {
        // Arrange
        $this->user->register('Rayan', 'r@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Action
        $result = $this->user->login('r@cardiffmet.ac.uk', 'Pass1234!');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('driver', $result['role']);
    }

    /** @test */
    public function test_login_returns_correct_user_id(): void
    {
        // Arrange
        $reg = $this->user->register('Rayan', 'r@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Action
        $login = $this->user->login('r@cardiffmet.ac.uk', 'Pass1234!');

        // Assert
        $this->assertEquals($reg['user_id'], $login['user_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP8 — WRONG PASSWORD
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_login_with_wrong_password_fails(): void
    {
        // Arrange
        $this->user->register('Rayan', 'r@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Action
        $result = $this->user->login('r@cardiffmet.ac.uk', 'WrongPassword9!');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP9 — UNKNOWN EMAIL
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_login_with_unregistered_email_fails(): void
    {
        // Arrange — no users registered

        // Action
        $result = $this->user->login('nobody@cardiffmet.ac.uk', 'Pass1234!');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UNIT TESTING GUIDELINES (Workshop Slide 12-13)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test — "Don't forget null and zero" (Table 9.4) */
    public function test_null_values_in_registration_are_handled(): void
    {
        // Action
        $result = $this->user->register('', '', '', '');

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test — "One is different" — single user registered, count is 1 */
    public function test_registering_one_user_gives_count_of_one(): void
    {
        // Arrange + Action
        $this->user->register('Solo User', 'solo@cardiffmet.ac.uk', 'Solo1234!', 'passenger');

        // Assert
        $this->assertEquals(1, $this->user->count());
    }

    /** @test — "Keep count" (Table 9.4) — two users, count must be 2 */
    public function test_two_registrations_gives_count_of_two(): void
    {
        // Arrange + Action
        $this->user->register('User One', 'one@cardiffmet.ac.uk', 'Pass1234!', 'driver');
        $this->user->register('User Two', 'two@cardiffmet.ac.uk', 'Pass5678!', 'passenger');

        // Assert
        $this->assertEquals(2, $this->user->count());
    }
}
