<?php

namespace Carpool;

/**
 * Ride class
 *
 * Represents a carpool ride offer.
 * TDD cycle: stubs written first → tests written → stubs fail → implementation added.
 */
class Ride
{
    private array $rides        = [];
    private array $bookings     = [];
    private int   $nextRideId   = 1;

    /**
     * Post a carpool ride offer.
     *
     * @param int    $driverId      ID of the driver posting the ride
     * @param string $origin        Pickup location
     * @param string $destination   Drop-off location
     * @param int    $seats         Available seats (1–7)
     * @param string $departureTime ISO-8601 datetime string
     * @param float  $pricePerSeat  Cost per passenger (>= 0)
     *
     * @return array ['success' => bool, 'ride_id' => int] or ['success' => false, 'error' => string]
     */
    public function postRide(
        int    $driverId,
        string $origin,
        string $destination,
        int    $seats,
        string $departureTime,
        float  $pricePerSeat
    ): array {
        // Validate origin / destination
        if (empty(trim($origin)) || empty(trim($destination))) {
            return ['success' => false, 'error' => 'Origin and destination are required'];
        }

        if (strtolower(trim($origin)) === strtolower(trim($destination))) {
            return ['success' => false, 'error' => 'Origin and destination cannot be the same'];
        }

        // Validate seats  (equivalence partition: 1–7 valid, <1 or >7 invalid)
        if ($seats < 1 || $seats > 7) {
            return ['success' => false, 'error' => 'Seats must be between 1 and 7'];
        }

        // Validate departure time (must be in the future)
        $departure = strtotime($departureTime);
        if ($departure === false || $departure <= time()) {
            return ['success' => false, 'error' => 'Departure time must be in the future'];
        }

        // Validate price (cannot be negative)
        if ($pricePerSeat < 0) {
            return ['success' => false, 'error' => 'Price cannot be negative'];
        }

        $id = $this->nextRideId++;
        $this->rides[$id] = [
            'id'            => $id,
            'driver_id'     => $driverId,
            'origin'        => htmlspecialchars(trim($origin),      ENT_QUOTES, 'UTF-8'),
            'destination'   => htmlspecialchars(trim($destination), ENT_QUOTES, 'UTF-8'),
            'seats_total'   => $seats,
            'seats_left'    => $seats,
            'departure'     => $departureTime,
            'price_per_seat'=> $pricePerSeat,
            'status'        => 'active',
        ];

        return ['success' => true, 'ride_id' => $id];
    }

    /**
     * Book a seat on an existing ride.
     *
     * @param int $passengerId  User ID of the passenger
     * @param int $rideId       Ride to join
     *
     * @return array
     */
    public function bookSeat(int $passengerId, int $rideId): array
    {
        if (!isset($this->rides[$rideId])) {
            return ['success' => false, 'error' => 'Ride not found'];
        }

        $ride = &$this->rides[$rideId];

        if ($ride['status'] !== 'active') {
            return ['success' => false, 'error' => 'Ride is no longer available'];
        }

        if ($ride['driver_id'] === $passengerId) {
            return ['success' => false, 'error' => 'Driver cannot book their own ride'];
        }

        // Check passenger hasn't already booked this ride
        foreach ($this->bookings as $booking) {
            if ($booking['ride_id'] === $rideId && $booking['passenger_id'] === $passengerId) {
                return ['success' => false, 'error' => 'Already booked this ride'];
            }
        }

        if ($ride['seats_left'] < 1) {
            return ['success' => false, 'error' => 'No seats available'];
        }

        $ride['seats_left']--;
        $bookingId = count($this->bookings) + 1;
        $this->bookings[$bookingId] = [
            'id'           => $bookingId,
            'ride_id'      => $rideId,
            'passenger_id' => $passengerId,
        ];

        return ['success' => true, 'booking_id' => $bookingId];
    }

    /**
     * Cancel a ride (driver only).
     */
    public function cancelRide(int $rideId, int $requesterId): array
    {
        if (!isset($this->rides[$rideId])) {
            return ['success' => false, 'error' => 'Ride not found'];
        }

        if ($this->rides[$rideId]['driver_id'] !== $requesterId) {
            return ['success' => false, 'error' => 'Only the driver can cancel this ride'];
        }

        $this->rides[$rideId]['status'] = 'cancelled';
        return ['success' => true];
    }

    /**
     * Get a ride by ID.
     */
    public function getRide(int $rideId): ?array
    {
        return $this->rides[$rideId] ?? null;
    }

    /**
     * List all active rides.
     */
    public function getActiveRides(): array
    {
        return array_values(array_filter($this->rides, fn($r) => $r['status'] === 'active'));
    }
}
