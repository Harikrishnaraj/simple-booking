<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only numbers for the Dashboard and Calendar pages.
 * Dates are "Y-m-d" in the site timezone, inclusive on both ends.
 */
class SB_Reports {

	// Bookings that take up time. Cancelled ones don't.
	private const ACTIVE = "'pending', 'confirmed', 'completed'";

	/**
	 * Totals for a date range.
	 *
	 * Revenue counts confirmed and completed bookings at the service's current price,
	 * because bookings don't store what was charged.
	 */
	public function summary( string $from, string $to ): array {
		global $wpdb;
		$p = $wpdb->prefix;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.customer_id, b.status, b.booking_time, b.end_time, COALESCE(b.total, s.price) AS price
				 FROM {$p}sb_bookings b
				 LEFT JOIN {$p}sb_services s ON s.id = b.service_id
				 WHERE b.booking_date BETWEEN %s AND %s AND b.status IN (" . self::ACTIVE . ')',
				$from,
				$to
			),
			ARRAY_A
		);

		$revenue = 0.0;
		$minutes = 0;
		foreach ( $rows as $r ) {
			if ( in_array( $r['status'], [ 'confirmed', 'completed' ], true ) ) {
				$revenue += (float) $r['price'];
			}
			$minutes += max( 0, ( strtotime( "1970-01-01 {$r['end_time']} UTC" ) - strtotime( "1970-01-01 {$r['booking_time']} UTC" ) ) / 60 );
		}

		$customer_ids = array_unique( array_map( 'intval', array_column( $rows, 'customer_id' ) ) );
		$new          = 0;
		if ( $customer_ids ) {
			// A customer is new if their first booking ever falls inside the range.
			$new = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM (
						SELECT customer_id, MIN(booking_date) AS first_date
						FROM {$p}sb_bookings
						WHERE status IN (" . self::ACTIVE . ')
						GROUP BY customer_id
					 ) f WHERE f.first_date BETWEEN %s AND %s',
					$from,
					$to
				)
			);
		}

		$capacity = $this->capacity_minutes( $from, $to );

		$received = (float) $wpdb->get_var(
			$wpdb->prepare( "SELECT SUM(amount) FROM {$p}sb_payments WHERE paid_at BETWEEN %s AND %s", $from . ' 00:00:00', $to . ' 23:59:59' )
		);

		return [
			'received'          => $received,
			'appointments'      => count( $rows ),
			'revenue'           => $revenue,
			'customers'         => count( $customer_ids ),
			'new_customers'     => min( $new, count( $customer_ids ) ),
			'booked_minutes'    => (int) $minutes,
			'available_minutes' => $capacity,
			'occupancy'         => $capacity ? min( 100, round( $minutes / $capacity * 100 ) ) : 0,
		];
	}

	/**
	 * Bookable minutes in the range: each active staff member's working hours (their own
	 * schedule and days off, or the business hours), or the business hours when there's no staff.
	 */
	public function capacity_minutes( string $from, string $to ): int {
		$staff_mgr = new SB_Staff();
		$staff     = $staff_mgr->get_all();
		$length    = static fn( ?array $r ) => $r ? max( 0, (int) ( ( strtotime( "1970-01-01 {$r[1]} UTC" ) - strtotime( "1970-01-01 {$r[0]} UTC" ) ) / 60 ) ) : 0;

		$minutes = 0;
		foreach ( $this->dates( $from, $to ) as $day ) {
			if ( ! $staff ) {
				$minutes += $length( SB_Staff::business_hours_on( $day->format( 'l' ) ) );
				continue;
			}
			foreach ( $staff as $member ) {
				$minutes += $length( $staff_mgr->hours_on( $member, $day->format( 'Y-m-d' ) ) );
			}
		}
		return $minutes;
	}

	/**
	 * Active bookings per day, with every day of the range present.
	 *
	 * @return array<string, int> "Y-m-d" => count
	 */
	public function daily_counts( string $from, string $to ): array {
		global $wpdb;
		$counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT booking_date, COUNT(*) AS n FROM {$wpdb->prefix}sb_bookings
				 WHERE booking_date BETWEEN %s AND %s AND status IN (" . self::ACTIVE . ')
				 GROUP BY booking_date',
				$from,
				$to
			),
			ARRAY_A
		);
		$counts = array_column( $counts, 'n', 'booking_date' );

		$out = [];
		foreach ( $this->dates( $from, $to ) as $day ) {
			$key         = $day->format( 'Y-m-d' );
			$out[ $key ] = (int) ( $counts[ $key ] ?? 0 );
		}
		return $out;
	}

	/**
	 * Next pending or confirmed bookings from now on.
	 */
	public function upcoming( int $limit = 8 ): array {
		global $wpdb;
		$p = $wpdb->prefix;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, s.name AS service_name, st.name AS staff_name, c.name AS customer_name, c.email AS customer_email
				 FROM {$p}sb_bookings b
				 LEFT JOIN {$p}sb_services s ON s.id = b.service_id
				 LEFT JOIN {$p}sb_staff st ON st.id = b.staff_id
				 LEFT JOIN {$p}sb_customers c ON c.id = b.customer_id
				 WHERE b.status IN ('pending', 'confirmed')
				   AND ( b.booking_date > %s OR ( b.booking_date = %s AND b.end_time > %s ) )
				 ORDER BY b.booking_date ASC, b.booking_time ASC
				 LIMIT %d",
				wp_date( 'Y-m-d' ),
				wp_date( 'Y-m-d' ),
				wp_date( 'H:i:s' ),
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * All bookings in a date range for the calendar, grouped by day.
	 *
	 * @return array<string, array[]> "Y-m-d" => bookings ordered by start time
	 */
	public function bookings_by_day( string $from, string $to, ?int $staff_id = null ): array {
		global $wpdb;
		$p     = $wpdb->prefix;
		$where = $wpdb->prepare( 'b.booking_date BETWEEN %s AND %s', $from, $to );
		if ( $staff_id ) {
			$where .= $wpdb->prepare( ' AND b.staff_id = %d', $staff_id );
		}

		$rows = $wpdb->get_results(
			"SELECT b.id, b.booking_code, b.booking_date, b.booking_time, b.end_time, b.status,
				s.name AS service_name, st.name AS staff_name, c.name AS customer_name
			 FROM {$p}sb_bookings b
			 LEFT JOIN {$p}sb_services s ON s.id = b.service_id
			 LEFT JOIN {$p}sb_staff st ON st.id = b.staff_id
			 LEFT JOIN {$p}sb_customers c ON c.id = b.customer_id
			 WHERE $where
			 ORDER BY b.booking_date ASC, b.booking_time ASC",
			ARRAY_A
		);

		$out = [];
		foreach ( $rows as $row ) {
			$out[ $row['booking_date'] ][] = $row;
		}
		return $out;
	}

	/**
	 * The range of equal length that ends the day before $from.
	 *
	 * @return string[] [ from, to ]
	 */
	public static function previous_range( string $from, string $to ): array {
		$tz     = wp_timezone();
		$start  = new DateTimeImmutable( $from, $tz );
		$length = $start->diff( new DateTimeImmutable( $to, $tz ) )->days + 1;
		return [
			$start->modify( "-$length days" )->format( 'Y-m-d' ),
			$start->modify( '-1 day' )->format( 'Y-m-d' ),
		];
	}

	/**
	 * Percentage change from $before to $now, or null when there is nothing to compare with.
	 */
	public static function change( float $now, float $before ): ?int {
		return $before > 0 ? (int) round( ( $now - $before ) / $before * 100 ) : null;
	}

	/**
	 * @return DateTimeImmutable[]
	 */
	private function dates( string $from, string $to ): array {
		$tz  = wp_timezone();
		$day = new DateTimeImmutable( $from, $tz );
		$end = new DateTimeImmutable( $to, $tz );
		$out = [];
		for ( $i = 0; $day <= $end && $i < 1000; $i++, $day = $day->modify( '+1 day' ) ) {
			$out[] = $day;
		}
		return $out;
	}
}
