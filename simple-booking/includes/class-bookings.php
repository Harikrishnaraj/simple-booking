<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Bookings {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sb_bookings';
	}

	public function generate_booking_code(): string {
		return 'SB-' . strtoupper( wp_generate_password( 6, false ) );
	}

	public function create_booking( array $data ): int|false {
		global $wpdb;

		$service_id  = absint( $data['service_id'] ?? 0 );
		$staff_id    = ! empty( $data['staff_id'] ) ? absint( $data['staff_id'] ) : null;
		$customer_id = absint( $data['customer_id'] ?? 0 );
		$date        = sanitize_text_field( $data['booking_date'] ?? '' );
		$time        = substr( sanitize_text_field( $data['booking_time'] ?? '' ), 0, 5 );

		$service = ( new SB_Services() )->get_by_id( $service_id );
		if ( ! $service || ! $customer_id ) {
			return false;
		}

		// Serialize bookings per date so two requests can't both take the same slot.
		// ponytail: one lock per date, switch to per-staff locks if busy sites see lock waits.
		$lock = 'sb_book_' . md5( $wpdb->prefix . $date );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
			return false;
		}

		try {
			// Availability is the single source of truth for a valid date/time (work day,
			// business hours, not in the past, active service, no overlap). For "any
			// available" it also picks which staff member gets the booking.
			$free = $this->free_slots( $service_id, $staff_id, $date );
			if ( ! array_key_exists( $time, $free ) ) {
				return false;
			}
			$staff_id = $free[ $time ];

			$start    = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', "$date $time", wp_timezone() );
			$end_time = wp_date( 'H:i:s', $start->getTimestamp() + absint( $service['duration'] ) * MINUTE_IN_SECONDS );

			$row = [
				'booking_code' => '',
				'service_id'   => $service_id,
				'staff_id'     => $staff_id,
				'customer_id'  => $customer_id,
				'booking_date' => $date,
				'booking_time' => "$time:00",
				'end_time'     => $end_time,
				'status'       => 'pending',
				'notes'        => sanitize_textarea_field( $data['notes'] ?? '' ),
			];

			// booking_code is UNIQUE; retry with a fresh code on the rare collision.
			for ( $attempt = 0, $inserted = false; ! $inserted && $attempt < 3; $attempt++ ) {
				$row['booking_code'] = $this->generate_booking_code();
				$inserted = $wpdb->insert( $this->table_name, $row, [ '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ] );
			}

			if ( ! $inserted ) {
				return false;
			}

			$booking_id = $wpdb->insert_id;
		} finally {
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}

		// Dispatch Email Notifications (outside the lock)
		$mailer = new SB_Email();
		$mailer->send_customer_confirmation( $booking_id );
		$mailer->send_admin_notification( $booking_id );

		return $booking_id;
	}

	/**
	 * Start times ("H:i") that can still be booked.
	 *
	 * @param int|null $staff_id A specific staff member, or null for "any available".
	 */
	public function get_available_slots( int $service_id, ?int $staff_id, string $date ): array {
		return array_map( 'strval', array_keys( $this->free_slots( $service_id, $staff_id, $date ) ) );
	}

	/**
	 * Free start times mapped to the staff id the booking should go to.
	 *
	 * Who can take a booking:
	 * - A chosen staff member: only them.
	 * - "Any available": every active staff member who offers the service. A time is free if
	 *   at least one of them is free; the least-busy one that day gets it.
	 * - No staff set up for the service: the business itself (staff id null), so any booking
	 *   that day blocks the time.
	 * Bookings with no staff member always block everyone.
	 *
	 * @return array<string, int|null> "H:i" => staff id (or null).
	 */
	private function free_slots( int $service_id, ?int $staff_id, string $date ): array {
		global $wpdb;

		$service = ( new SB_Services() )->get_by_id( $service_id );
		if ( ! $service || 'active' !== $service['status'] || absint( $service['duration'] ) < 1 ) {
			return [];
		}

		$staff_mgr = new SB_Staff();
		if ( $staff_id ) {
			if ( ! $staff_mgr->can_perform( $staff_id, $service_id ) ) {
				return [];
			}
			$candidates = [ $staff_id ];
		} else {
			$candidates = $staff_mgr->qualified_ids( $service_id ) ?: [ null ];
		}

		$settings = SB_Settings::get_settings();
		$tz       = wp_timezone();

		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $tz );
		if ( ! $day || $day->format( 'Y-m-d' ) !== $date ) {
			return [];
		}
		if ( ! in_array( $day->format( 'l' ), (array) $settings['work_days'], true ) ) {
			return [];
		}

		$open  = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $settings['business_hours_start'], $tz );
		$close = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $settings['business_hours_end'], $tz );
		if ( ! $open || ! $close ) {
			return [];
		}

		$start_ts = $open->getTimestamp();
		$end_ts   = $close->getTimestamp();
		$interval = max( 5, absint( $settings['slot_duration'] ) ) * MINUTE_IN_SECONDS; // never 0: would loop forever
		$duration = absint( $service['duration'] ) * MINUTE_IN_SECONDS;
		$now      = time();

		$existing = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT booking_time, end_time, staff_id FROM {$this->table_name} WHERE booking_date = %s AND status IN ('pending', 'confirmed')",
				$date
			),
			ARRAY_A
		);

		// Spread "any available" bookings: least-busy staff member that day is tried first.
		$load = array_count_values( array_map( 'strval', array_filter( array_column( $existing, 'staff_id' ) ) ) );
		usort( $candidates, fn( $a, $b ) => ( $load[ (string) $a ] ?? 0 ) <=> ( $load[ (string) $b ] ?? 0 ) );

		$slots = [];

		for ( $current = $start_ts; $current + $duration <= $end_ts; $current += $interval ) {
			if ( $current <= $now ) {
				continue;
			}

			$slot_start = wp_date( 'H:i:s', $current );
			$slot_end   = wp_date( 'H:i:s', $current + $duration );

			foreach ( $candidates as $candidate ) {
				if ( ! $this->is_busy( $existing, $candidate, $slot_start, $slot_end ) ) {
					$slots[ substr( $slot_start, 0, 5 ) ] = $candidate;
					break;
				}
			}
		}

		return $slots;
	}

	/**
	 * Whether $staff_id (null = the business as a whole) has an overlapping booking.
	 */
	private function is_busy( array $existing, ?int $staff_id, string $start, string $end ): bool {
		foreach ( $existing as $b ) {
			$blocks = null === $staff_id || null === $b['staff_id'] || (int) $b['staff_id'] === $staff_id;
			if ( $blocks && $start < $b['end_time'] && $end > $b['booking_time'] ) {
				return true;
			}
		}
		return false;
	}

	public function update_status( int $id, string $status ): bool {
		global $wpdb;
		$allowed = [ 'pending', 'confirmed', 'cancelled', 'completed' ];
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$updated = $wpdb->update(
			$this->table_name,
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		if ( $updated ) {
			( new SB_Email() )->send_status_update( $id, $status );
		}

		return false !== $updated;
	}

	public function get_code( int $id ): string {
		global $wpdb;
		return (string) $wpdb->get_var(
			$wpdb->prepare( "SELECT booking_code FROM {$this->table_name} WHERE id = %d", $id )
		);
	}

	/**
	 * Newest-first page of bookings with service, staff and customer names for the admin list.
	 *
	 * @param array $filters Optional: search, status, staff_id, date_from, date_to ("Y-m-d").
	 */
	public function get_list( int $page, int $per_page, array $filters = [] ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, s.name AS service_name, st.name AS staff_name,
					c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
				 FROM {$this->table_name} b
				 LEFT JOIN {$wpdb->prefix}sb_services s ON s.id = b.service_id
				 LEFT JOIN {$wpdb->prefix}sb_staff st ON st.id = b.staff_id
				 LEFT JOIN {$wpdb->prefix}sb_customers c ON c.id = b.customer_id
				 WHERE {$this->filter_sql( $filters )}
				 ORDER BY b.booking_date DESC, b.booking_time DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				( max( 1, $page ) - 1 ) * $per_page
			),
			ARRAY_A
		);
	}

	public function count( array $filters = [] ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} b
			 LEFT JOIN {$wpdb->prefix}sb_customers c ON c.id = b.customer_id
			 WHERE {$this->filter_sql( $filters )}"
		);
	}

	private function filter_sql( array $filters ): string {
		global $wpdb;
		$where = [ '1=1' ];
		if ( ! empty( $filters['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where[] = $wpdb->prepare( '(b.booking_code LIKE %s OR c.name LIKE %s OR c.email LIKE %s)', $like, $like, $like );
		}
		if ( ! empty( $filters['status'] ) ) {
			$where[] = $wpdb->prepare( 'b.status = %s', $filters['status'] );
		}
		if ( ! empty( $filters['staff_id'] ) ) {
			$where[] = $wpdb->prepare( 'b.staff_id = %d', $filters['staff_id'] );
		}
		if ( ! empty( $filters['date_from'] ) ) {
			$where[] = $wpdb->prepare( 'b.booking_date >= %s', $filters['date_from'] );
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$where[] = $wpdb->prepare( 'b.booking_date <= %s', $filters['date_to'] );
		}
		return implode( ' AND ', $where );
	}
}
