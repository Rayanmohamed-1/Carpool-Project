<?php

namespace Carpool;

/**
 * RideMatcher class
 *
 * Implements the ride-matching algorithm described in the assessment:
 * "Server: Handles ride-matching algorithms and route optimization."
 *
 * Uses a simple scoring system based on:
 *   - Location proximity (substring/keyword match as simplified geo-match)
 *   - Seat availability
 *   - Fare acceptability
 */
class RideMatcher
{
    /**
     * Find rides that match a passenger's request.
     *
     * @param array $availableRides  Array of ride arrays (from Ride::getActiveRides())
     * @param string $origin         Passenger's pickup location keyword
     * @param string $destination    Passenger's destination keyword
     * @param int    $seatsNeeded    How many seats the passenger needs
     * @param float  $maxPrice       Maximum price per seat the passenger will pay
     *
     * @return array Matched rides sorted by price ascending
     */
    public function findMatches(
        array  $availableRides,
        string $origin,
        string $destination,
        int    $seatsNeeded  = 1,
        float  $maxPrice     = PHP_FLOAT_MAX
    ): array {
        if (empty($origin) || empty($destination)) {
            return [];
        }

        if ($seatsNeeded < 1) {
            return [];
        }

        $matches = [];

        foreach ($availableRides as $ride) {
            // Location match (case-insensitive keyword match)
            $originMatch      = stripos($ride['origin'],      $origin)      !== false;
            $destinationMatch = stripos($ride['destination'], $destination) !== false;

            if (!$originMatch || !$destinationMatch) {
                continue;
            }

            // Enough seats?
            if ($ride['seats_left'] < $seatsNeeded) {
                continue;
            }

            // Price acceptable?
            if ($ride['price_per_seat'] > $maxPrice) {
                continue;
            }

            $matches[] = $ride;
        }

        // Sort by price ascending (cheapest first)
        usort($matches, fn($a, $b) => $a['price_per_seat'] <=> $b['price_per_seat']);

        return $matches;
    }

    /**
     * Calculate estimated total fare for a journey.
     *
     * @param float $pricePerSeat   Price per seat
     * @param int   $seats          Number of seats being booked
     *
     * @return float
     */
    public function calculateFare(float $pricePerSeat, int $seats): float
    {
        if ($pricePerSeat < 0 || $seats < 1) {
            return 0.0;
        }
        return round($pricePerSeat * $seats, 2);
    }
}
