<?php

namespace Carpool\Tests;

use Carpool\RideMatcher;
use PHPUnit\Framework\TestCase;

/**
 * RideMatcherTest — TDD tests for the ride-matching and fare calculation.
 *
 * Assessment requirement:
 *   "Server: Handles ride-matching algorithms and route optimization."
 *
 * Workshop references:
 *   - Arrange → Action → Assert (Slide 26)
 *   - Equivalence partitions (Slide 8)
 *   - Overflow / underflow / zero / null checks (Table 9.4)
 *
 * Equivalence Partitions:
 *   EP1  Exact origin + destination match  — ride returned
 *   EP2  No matching origin                — empty result
 *   EP3  No matching destination           — empty result
 *   EP4  Insufficient seats                — excluded from results
 *   EP5  Price above passenger max         — excluded from results
 *   EP6  Multiple matches                  — sorted cheapest first
 *   EP7  Empty available rides             — empty result
 *   EP8  Fare calculation — valid          — correct total returned
 *   EP9  Fare calculation — zero/negative  — returns 0
 */
class RideMatcherTest extends TestCase
{
    private RideMatcher $matcher;
    private array       $sampleRides;

    protected function setUp(): void
    {
        $this->matcher = new RideMatcher();

        // Shared sample rides dataset
        $this->sampleRides = [
            [
                'id'             => 1,
                'driver_id'      => 10,
                'origin'         => 'Cardiff Central Station',
                'destination'    => 'Cardiff Met University',
                'seats_total'    => 3,
                'seats_left'     => 3,
                'departure'      => date('Y-m-d H:i:s', strtotime('+1 day')),
                'price_per_seat' => 2.00,
                'status'         => 'active',
            ],
            [
                'id'             => 2,
                'driver_id'      => 11,
                'origin'         => 'Cardiff Bay',
                'destination'    => 'Cardiff Met University',
                'seats_total'    => 2,
                'seats_left'     => 2,
                'departure'      => date('Y-m-d H:i:s', strtotime('+2 days')),
                'price_per_seat' => 1.50,
                'status'         => 'active',
            ],
            [
                'id'             => 3,
                'driver_id'      => 12,
                'origin'         => 'Canton',
                'destination'    => 'Cardiff Met University',
                'seats_total'    => 1,
                'seats_left'     => 0,   // FULL
                'departure'      => date('Y-m-d H:i:s', strtotime('+3 days')),
                'price_per_seat' => 1.00,
                'status'         => 'active',
            ],
            [
                'id'             => 4,
                'driver_id'      => 13,
                'origin'         => 'Roath',
                'destination'    => 'Cardiff Met University',
                'seats_total'    => 2,
                'seats_left'     => 2,
                'departure'      => date('Y-m-d H:i:s', strtotime('+1 day')),
                'price_per_seat' => 5.00, // Expensive
                'status'         => 'active',
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP1 — EXACT ORIGIN + DESTINATION MATCH
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_matching_ride_is_returned_when_origin_and_destination_match(): void
    {
        // Arrange
        $origin      = 'Cardiff Central';
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination);

        // Assert — Ride 1 matches (Cardiff Central Station contains "Cardiff Central")
        $this->assertNotEmpty($results);
        $this->assertEquals(1, $results[0]['id']);
    }

    /** @test */
    public function test_match_is_case_insensitive(): void
    {
        // Arrange
        $origin      = 'cardiff central';
        $destination = 'CARDIFF MET';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination);

        // Assert
        $this->assertNotEmpty($results);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP2 — NO MATCHING ORIGIN
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_no_results_when_origin_does_not_match(): void
    {
        // Arrange
        $origin      = 'Swansea';  // Not in any ride
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination);

        // Assert
        $this->assertEmpty($results);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP3 — NO MATCHING DESTINATION
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_no_results_when_destination_does_not_match(): void
    {
        // Arrange
        $origin      = 'Cardiff Central';
        $destination = 'Swansea University'; // Not in any ride

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination);

        // Assert
        $this->assertEmpty($results);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP4 — INSUFFICIENT SEATS
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_full_ride_is_excluded_from_results(): void
    {
        // Arrange — Ride 3 (Canton) has 0 seats left
        $origin      = 'Canton';
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination, 1);

        // Assert — should return empty because seats_left = 0
        $this->assertEmpty($results);
    }

    /** @test */
    public function test_ride_with_insufficient_seats_for_group_is_excluded(): void
    {
        // Arrange — need 3 seats; Ride 2 (Cardiff Bay) only has 2
        $origin      = 'Cardiff Bay';
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination, 3);

        // Assert
        $this->assertEmpty($results);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP5 — PRICE ABOVE PASSENGER MAXIMUM
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_ride_above_max_price_is_excluded(): void
    {
        // Arrange — Ride 4 (Roath) costs £5.00, passenger max is £3.00
        $origin      = 'Roath';
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination, 1, 3.00);

        // Assert
        $this->assertEmpty($results);
    }

    /** @test */
    public function test_ride_at_exact_max_price_is_included(): void
    {
        // Arrange — boundary edge: price equals maxPrice exactly
        $origin      = 'Cardiff Bay';
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination, 1, 1.50);

        // Assert — £1.50 == maxPrice £1.50 should be included
        $this->assertNotEmpty($results);
        $this->assertEquals(2, $results[0]['id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP6 — MULTIPLE MATCHES, SORTED CHEAPEST FIRST
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_multiple_matches_are_sorted_cheapest_first(): void
    {
        // Arrange — both Cardiff Central (£2.00) and Cardiff Bay (£1.50) go to Cardiff Met
        $origin      = 'Cardiff';    // matches both "Cardiff Central" and "Cardiff Bay"
        $destination = 'Cardiff Met';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, $destination);

        // Assert — Cardiff Bay (£1.50) should come before Cardiff Central (£2.00)
        $this->assertGreaterThan(1, count($results));
        $this->assertLessThanOrEqual(
            $results[1]['price_per_seat'],
            $results[0]['price_per_seat']
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP7 — EMPTY RIDES LIST
    // ─────────────────────────────────────────────────────────────────────────

    /** @test — "Don't forget null and zero" (Table 9.4) */
    public function test_empty_rides_list_returns_no_matches(): void
    {
        // Arrange
        $emptyRides = [];

        // Action
        $results = $this->matcher->findMatches($emptyRides, 'Cardiff', 'Cardiff Met');

        // Assert
        $this->assertEmpty($results);
    }

    /** @test */
    public function test_empty_origin_returns_no_matches(): void
    {
        // Arrange
        $origin = '';

        // Action
        $results = $this->matcher->findMatches($this->sampleRides, $origin, 'Cardiff Met');

        // Assert
        $this->assertEmpty($results);
    }

    /** @test */
    public function test_zero_seats_needed_returns_no_matches(): void
    {
        // Arrange — "Overflow and underflow" (Table 9.4)
        $result = $this->matcher->findMatches($this->sampleRides, 'Cardiff', 'Cardiff Met', 0);

        // Assert
        $this->assertEmpty($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP8 — FARE CALCULATION (valid inputs)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_fare_calculation_for_one_seat(): void
    {
        // Arrange
        $pricePerSeat = 2.50;
        $seats        = 1;
        $expected     = 2.50;

        // Action
        $fare = $this->matcher->calculateFare($pricePerSeat, $seats);

        // Assert
        $this->assertEquals($expected, $fare);
    }

    /** @test */
    public function test_fare_calculation_for_multiple_seats(): void
    {
        // Arrange
        $pricePerSeat = 1.50;
        $seats        = 3;
        $expected     = 4.50;

        // Action
        $fare = $this->matcher->calculateFare($pricePerSeat, $seats);

        // Assert
        $this->assertEquals($expected, $fare);
    }

    /** @test */
    public function test_fare_rounds_to_two_decimal_places(): void
    {
        // Arrange
        $pricePerSeat = 1.005;
        $seats        = 2;

        // Action
        $fare = $this->matcher->calculateFare($pricePerSeat, $seats);

        // Assert
        $this->assertEquals(round(1.005 * 2, 2), $fare);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP9 — FARE CALCULATION (zero / negative / edge cases)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test — "Don't forget null and zero" (Table 9.4) */
    public function test_zero_price_per_seat_gives_zero_fare(): void
    {
        // Arrange
        $pricePerSeat = 0.00;
        $seats        = 3;

        // Action
        $fare = $this->matcher->calculateFare($pricePerSeat, $seats);

        // Assert
        $this->assertEquals(0.00, $fare);
    }

    /** @test — "Overflow and underflow" (Table 9.4) */
    public function test_negative_price_gives_zero_fare(): void
    {
        // Arrange
        $pricePerSeat = -5.00;
        $seats        = 2;

        // Action
        $fare = $this->matcher->calculateFare($pricePerSeat, $seats);

        // Assert — negative price should return 0 (guard against negative fare)
        $this->assertEquals(0.00, $fare);
    }

    /** @test */
    public function test_zero_seats_gives_zero_fare(): void
    {
        // Arrange
        $pricePerSeat = 3.00;
        $seats        = 0;

        // Action
        $fare = $this->matcher->calculateFare($pricePerSeat, $seats);

        // Assert
        $this->assertEquals(0.00, $fare);
    }
}
