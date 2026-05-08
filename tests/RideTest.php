<?php

namespace Carpool\Tests;

use Carpool\Ride;
use PHPUnit\Framework\TestCase;

/**
 * RideTest — TDD tests for carpool ride posting, booking, and cancellation.
 *
 * Workshop references:
 *   - Arrange → Action → Assert (Slide 26)
 *   - Equivalence partitions with boundary values (Slide 8, 11-12)
 *   - TDD cycle: write tests → stubs fail → implement → all pass (Slides 37-38)
 *
 * Equivalence Partitions:
 *   EP1  Valid ride post       — all fields correct, future departure
 *   EP2  Invalid seats         — 0, negative, > 7
 *   EP3  Invalid price         — negative price
 *   EP4  Same origin/dest      — origin equals destination
 *   EP5  Past departure time   — departure in the past
 *   EP6  Valid booking         — passenger joins available ride
 *   EP7  Full ride booking     — no seats left
 *   EP8  Self-booking          — driver books own ride
 *   EP9  Duplicate booking     — same passenger books twice
 *   EP10 Cancel by driver      — driver cancels their ride
 *   EP11 Cancel by non-driver  — unauthorised cancellation attempt
 */
class RideTest extends TestCase
{
    private Ride $ride;
    private string $futureTime;

    protected function setUp(): void
    {
        $this->ride       = new Ride();
        $this->futureTime = date('Y-m-d H:i:s', strtotime('+2 days'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP1 — VALID RIDE POST
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_valid_ride_post_succeeds(): void
    {
        // Arrange
        $driverId    = 1;
        $origin      = 'Cardiff Central Station';
        $destination = 'Cardiff Met University';
        $seats       = 3;
        $price       = 2.50;

        // Action
        $result = $this->ride->postRide($driverId, $origin, $destination, $seats, $this->futureTime, $price);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ride_id', $result);
    }

    /** @test */
    public function test_posted_ride_is_retrievable(): void
    {
        // Arrange
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 2, $this->futureTime, 1.50);

        // Action
        $ride = $this->ride->getRide($result['ride_id']);

        // Assert
        $this->assertNotNull($ride);
        $this->assertEquals('active', $ride['status']);
        $this->assertEquals(2, $ride['seats_left']);
    }

    /** @test */
    public function test_free_ride_with_zero_price_succeeds(): void
    {
        // Arrange — boundary edge: price = 0 (should be allowed)
        $result = $this->ride->postRide(1, 'Roath', 'Cardiff Met', 1, $this->futureTime, 0.00);

        // Assert
        $this->assertTrue($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP2 — INVALID SEAT COUNT (boundary values)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_zero_seats_fails_ride_post(): void
    {
        // Arrange — boundary edge: 0 (below minimum of 1)
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 0, $this->futureTime, 2.00);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('seats', strtolower($result['error']));
    }

    /** @test */
    public function test_negative_seats_fails_ride_post(): void
    {
        // Arrange
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', -1, $this->futureTime, 2.00);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_eight_seats_fails_ride_post(): void
    {
        // Arrange — boundary edge: 8 (above maximum of 7)
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 8, $this->futureTime, 2.00);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_maximum_valid_seats_of_seven_succeeds(): void
    {
        // Arrange — boundary edge: exactly 7 (maximum valid value)
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 7, $this->futureTime, 2.00);

        // Assert
        $this->assertTrue($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP3 — INVALID PRICE
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_negative_price_fails_ride_post(): void
    {
        // Arrange
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 2, $this->futureTime, -1.00);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('price', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP4 — SAME ORIGIN AND DESTINATION
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_same_origin_and_destination_fails(): void
    {
        // Arrange
        $result = $this->ride->postRide(1, 'Cardiff Met', 'Cardiff Met', 2, $this->futureTime, 2.00);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_empty_origin_fails_ride_post(): void
    {
        // Arrange — "Don't forget null and zero" (Table 9.4)
        $result = $this->ride->postRide(1, '', 'Cardiff Met', 2, $this->futureTime, 2.00);

        // Assert
        $this->assertFalse($result['success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP5 — PAST DEPARTURE TIME
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_past_departure_time_fails_ride_post(): void
    {
        // Arrange — "Test edge cases" (Table 9.4)
        $pastTime = date('Y-m-d H:i:s', strtotime('-1 hour'));

        // Action
        $result = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 2, $pastTime, 2.00);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('future', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP6 — VALID SEAT BOOKING
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_passenger_can_book_available_seat(): void
    {
        // Arrange
        $rideResult  = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);
        $rideId      = $rideResult['ride_id'];
        $passengerId = 2;

        // Action
        $booking = $this->ride->bookSeat($passengerId, $rideId);

        // Assert
        $this->assertTrue($booking['success']);
        $this->assertArrayHasKey('booking_id', $booking);
    }

    /** @test */
    public function test_booking_decrements_available_seats(): void
    {
        // Arrange
        $rideResult = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);
        $rideId     = $rideResult['ride_id'];

        // Action
        $this->ride->bookSeat(2, $rideId);
        $this->ride->bookSeat(3, $rideId);

        // Assert — "Keep count" (Table 9.4)
        $ride = $this->ride->getRide($rideId);
        $this->assertEquals(1, $ride['seats_left']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP7 — FULLY BOOKED RIDE
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_booking_when_no_seats_left_fails(): void
    {
        // Arrange — post ride with only 1 seat, then fill it
        $rideResult = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 1, $this->futureTime, 2.00);
        $rideId     = $rideResult['ride_id'];
        $this->ride->bookSeat(2, $rideId);

        // Action — third person tries to book
        $result = $this->ride->bookSeat(3, $rideId);

        // Assert — boundary: seats_left = 0
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no seats', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP8 — DRIVER BOOKING OWN RIDE
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_driver_cannot_book_own_ride(): void
    {
        // Arrange
        $driverId   = 1;
        $rideResult = $this->ride->postRide($driverId, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);

        // Action — driver tries to book own ride
        $result = $this->ride->bookSeat($driverId, $rideResult['ride_id']);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('driver', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP9 — DUPLICATE BOOKING
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_passenger_cannot_book_same_ride_twice(): void
    {
        // Arrange
        $rideResult  = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);
        $rideId      = $rideResult['ride_id'];
        $passengerId = 2;

        $this->ride->bookSeat($passengerId, $rideId); // First booking

        // Action — second booking attempt
        $result = $this->ride->bookSeat($passengerId, $rideId);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already booked', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP10 — DRIVER CANCELS OWN RIDE
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_driver_can_cancel_own_ride(): void
    {
        // Arrange
        $driverId   = 1;
        $rideResult = $this->ride->postRide($driverId, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);
        $rideId     = $rideResult['ride_id'];

        // Action
        $result = $this->ride->cancelRide($rideId, $driverId);

        // Assert
        $this->assertTrue($result['success']);
        $ride = $this->ride->getRide($rideId);
        $this->assertEquals('cancelled', $ride['status']);
    }

    /** @test */
    public function test_cancelled_ride_cannot_be_booked(): void
    {
        // Arrange
        $rideResult = $this->ride->postRide(1, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);
        $rideId     = $rideResult['ride_id'];
        $this->ride->cancelRide($rideId, 1);

        // Action
        $result = $this->ride->bookSeat(2, $rideId);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no longer available', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EP11 — NON-DRIVER CANCELLATION
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function test_passenger_cannot_cancel_someone_elses_ride(): void
    {
        // Arrange
        $driverId   = 1;
        $otherId    = 99;
        $rideResult = $this->ride->postRide($driverId, 'Canton', 'Cardiff Met', 3, $this->futureTime, 2.00);

        // Action
        $result = $this->ride->cancelRide($rideResult['ride_id'], $otherId);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('only the driver', strtolower($result['error']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTIVE RIDE LISTING
    // ─────────────────────────────────────────────────────────────────────────

    /** @test — "One is different" (Table 9.4) */
    public function test_single_active_ride_appears_in_listing(): void
    {
        // Arrange + Action
        $this->ride->postRide(1, 'Canton', 'Cardiff Met', 2, $this->futureTime, 2.00);
        $active = $this->ride->getActiveRides();

        // Assert
        $this->assertCount(1, $active);
    }

    /** @test */
    public function test_cancelled_rides_are_excluded_from_active_listing(): void
    {
        // Arrange
        $r1 = $this->ride->postRide(1, 'Canton',  'Cardiff Met', 2, $this->futureTime, 2.00);
        $r2 = $this->ride->postRide(2, 'Roath',   'Cardiff Met', 1, $this->futureTime, 1.00);
        $this->ride->cancelRide($r1['ride_id'], 1);

        // Action
        $active = $this->ride->getActiveRides();

        // Assert — only the second ride remains active
        $this->assertCount(1, $active);
        $this->assertEquals($r2['ride_id'], $active[0]['id']);
    }
}
