<?php

namespace Carpool\Tests;

use Carpool\InputValidator;
use Carpool\User;
use PHPUnit\Framework\TestCase;

/**
 * SecurityTest — TDD tests for security validation.
 *
 * Assessment requirement (Phase 4):
 *   "Security Testing: Identifying vulnerabilities and implementing defences."
 *   "Software Security Check: Authentication, authorization, encryption, and privacy."
 *
 * Workshop references:
 *   - Security testing should be risk-driven (Slides 41-44)
 *   - Table 9.11: Examples of security risks
 *
 * Security Risks Tested (from Table 9.11):
 *   RISK1  SQL injection attacks
 *   RISK2  XSS / script injection
 *   RISK3  Weak passwords (brute-force susceptibility)
 *   RISK4  Unauthorised access (login bypass)
 *   RISK5  Authorisation — user accessing another user's resources
 *   RISK6  Input field overflow / buffer overrun
 *   RISK7  Privacy — passwords stored as plain text (must be hashed)
 *   RISK8  Malformed / null inputs
 */
class SecurityTest extends TestCase
{
    private InputValidator $validator;
    private User           $user;

    protected function setUp(): void
    {
        $this->validator = new InputValidator();
        $this->user      = new User();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK1 — SQL INJECTION
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_sql_injection_or_1_equals_1_is_detected(): void
    {
        // Arrange — classic OR 1=1 attack
        $malicious = "' OR '1'='1";

        // Action
        $detected = $this->validator->containsSqlInjection($malicious);

        // Assert
        $this->assertTrue($detected, 'SQL injection pattern OR 1=1 should be detected');
    }

    /** @test */
    public function test_sql_drop_table_injection_is_detected(): void
    {
        // Arrange
        $malicious = "'; DROP TABLE users; --";

        // Action
        $detected = $this->validator->containsSqlInjection($malicious);

        // Assert
        $this->assertTrue($detected);
    }

    /** @test */
    public function test_sql_union_select_injection_is_detected(): void
    {
        // Arrange
        $malicious = "1 UNION SELECT * FROM users";

        // Action
        $detected = $this->validator->containsSqlInjection($malicious);

        // Assert
        $this->assertTrue($detected);
    }

    /** @test */
    public function test_clean_name_input_is_not_flagged_as_sql_injection(): void
    {
        // Arrange
        $clean = 'Rayan Mohamed';

        // Action
        $detected = $this->validator->containsSqlInjection($clean);

        // Assert
        $this->assertFalse($detected);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK2 — XSS / SCRIPT INJECTION
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_script_tag_injection_is_detected(): void
    {
        // Arrange
        $malicious = '<script>alert("XSS")</script>';

        // Action
        $detected = $this->validator->containsXss($malicious);

        // Assert
        $this->assertTrue($detected);
    }

    /** @test */
    public function test_javascript_protocol_injection_is_detected(): void
    {
        // Arrange
        $malicious = 'javascript:alert(1)';

        // Action
        $detected = $this->validator->containsXss($malicious);

        // Assert
        $this->assertTrue($detected);
    }

    /** @test */
    public function test_onclick_event_handler_injection_is_detected(): void
    {
        // Arrange
        $malicious = '<img src=x onerror=alert(1)>';

        // Action
        $detected = $this->validator->containsXss($malicious);

        // Assert
        $this->assertTrue($detected);
    }

    /** @test */
    public function test_sanitise_text_escapes_html_characters(): void
    {
        // Arrange
        $raw      = '<h1>Hello & "World"</h1>';
        $expected = '&lt;h1&gt;Hello &amp; &quot;World&quot;&lt;/h1&gt;';

        // Action
        $sanitised = $this->validator->sanitiseText($raw);

        // Assert
        $this->assertEquals($expected, $sanitised);
    }

    /** @test */
    public function test_plain_text_is_not_flagged_as_xss(): void
    {
        // Arrange
        $clean = 'Cardiff Met University Carpool';

        // Action
        $detected = $this->validator->containsXss($clean);

        // Assert
        $this->assertFalse($detected);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK3 — WEAK PASSWORDS
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_strong_password_passes_validation(): void
    {
        // Arrange
        $password = 'Cardiff@Met9!';

        // Action
        $result = $this->validator->validatePasswordStrength($password);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function test_password_123456_is_rejected_as_too_weak(): void
    {
        // Arrange
        $password = '123456';

        // Action
        $result = $this->validator->validatePasswordStrength($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function test_password_without_uppercase_fails(): void
    {
        // Arrange
        $password = 'cardiffmet9!';

        // Action
        $result = $this->validator->validatePasswordStrength($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain an uppercase letter', $result['errors']);
    }

    /** @test */
    public function test_password_without_special_character_fails(): void
    {
        // Arrange
        $password = 'CardiffMet9';

        // Action
        $result = $this->validator->validatePasswordStrength($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain a special character', $result['errors']);
    }

    /** @test — boundary edge: exactly 7 characters (1 below minimum) */
    public function test_7_char_password_fails_minimum_length(): void
    {
        // Arrange
        $password = 'Ab1!xyz'; // 7 chars

        // Action
        $result = $this->validator->validatePasswordStrength($password);

        // Assert
        $this->assertFalse($result['valid']);
    }

    /** @test — boundary edge: exactly 8 characters (minimum) */
    public function test_8_char_strong_password_passes_minimum_length(): void
    {
        // Arrange
        $password = 'Ab1!xyzQ'; // 8 chars — meets all criteria

        // Action
        $result = $this->validator->validatePasswordStrength($password);

        // Assert
        $this->assertTrue($result['valid']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK4 — UNAUTHORISED ACCESS / LOGIN BYPASS
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_sql_injected_email_does_not_bypass_login(): void
    {
        // Arrange — register a real user
        $this->user->register('Rayan', 'rayan@cardiffmet.ac.uk', 'Safe1234!', 'driver');

        // Action — attacker tries SQL injection in email field
        $result = $this->user->login("' OR '1'='1' --", 'anything');

        // Assert — must NOT succeed
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_empty_credentials_do_not_grant_access(): void
    {
        // Action
        $result = $this->user->login('', '');

        // Assert
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK5 — AUTHORISATION (user can't access another user's data)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_user_id_returned_on_login_matches_registered_id(): void
    {
        // Arrange
        $reg   = $this->user->register('Rayan', 'rayan@cardiffmet.ac.uk', 'Safe1234!', 'driver');
        $this->user->register('Other', 'other@cardiffmet.ac.uk', 'Other1234!', 'passenger');

        // Action
        $login = $this->user->login('rayan@cardiffmet.ac.uk', 'Safe1234!');

        // Assert — gets own user_id, not someone else's
        $this->assertEquals($reg['user_id'], $login['user_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK6 — INPUT FIELD OVERFLOW / BUFFER OVERRUN
    // ─────────────────────────────────────────────────────────────────────────

    /** @test — "Fill buffers" (Table 9.4) */
    public function test_extremely_long_name_is_rejected(): void
    {
        // Arrange — 10,000 character name
        $longName = str_repeat('A', 10000);

        // Action
        $result = $this->user->register($longName, 'r@cardiffmet.ac.uk', 'Pass1234!', 'driver');

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_extremely_long_email_is_rejected(): void
    {
        // Arrange
        $longEmail = str_repeat('a', 1000) . '@cardiffmet.ac.uk';

        // Action
        $result = $this->user->register('Rayan', $longEmail, 'Pass1234!', 'driver');

        // Assert — invalid email format (too long)
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK7 — PRIVACY: PASSWORDS MUST NOT BE STORED IN PLAIN TEXT
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_stored_password_is_not_plain_text(): void
    {
        // Arrange
        $plainPassword = 'Pass1234!';
        $reg           = $this->user->register('Rayan', 'r@cardiffmet.ac.uk', $plainPassword, 'driver');

        // Action
        $storedUser = $this->user->findById($reg['user_id']);

        // Assert — stored password must NOT equal the plain-text password
        $this->assertNotEquals($plainPassword, $storedUser['password']);
    }

    /** @test */
    public function test_stored_password_is_a_bcrypt_hash(): void
    {
        // Arrange
        $plainPassword = 'Pass1234!';
        $reg           = $this->user->register('Rayan', 'r@cardiffmet.ac.uk', $plainPassword, 'driver');
        $storedUser    = $this->user->findById($reg['user_id']);

        // Action — verify using PHP's password_verify (confirms bcrypt hash)
        $isHash = password_verify($plainPassword, $storedUser['password']);

        // Assert
        $this->assertTrue($isHash, 'Password should be stored as a bcrypt hash');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RISK8 — MALFORMED / NULL INPUTS
    // ─────────────────────────────────────────────────────────────────────────

    /** @test — "Don't forget null and zero" (Table 9.4) */
    public function test_all_empty_registration_fields_are_rejected(): void
    {
        // Action
        $result = $this->user->register('', '', '', '');

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_email_validation_rejects_null_like_empty_string(): void
    {
        // Arrange
        $empty = '';

        // Action
        $valid = $this->validator->validateEmail($empty);

        // Assert
        $this->assertFalse($valid);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UK POSTCODE VALIDATION (Cardiff routes)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_valid_cardiff_postcode_passes(): void
    {
        // Arrange
        $postcode = 'CF24 0DE'; // Cardiff Met area

        // Action
        $valid = $this->validator->validateUkPostcode($postcode);

        // Assert
        $this->assertTrue($valid);
    }

    /** @test */
    public function test_invalid_postcode_fails(): void
    {
        // Arrange
        $postcode = 'NOT A POSTCODE';

        // Action
        $valid = $this->validator->validateUkPostcode($postcode);

        // Assert
        $this->assertFalse($valid);
    }

    /** @test */
    public function test_postcode_without_space_is_also_valid(): void
    {
        // Arrange — normalised without space
        $postcode = 'CF240DE';

        // Action
        $valid = $this->validator->validateUkPostcode($postcode);

        // Assert
        $this->assertTrue($valid);
    }
}
