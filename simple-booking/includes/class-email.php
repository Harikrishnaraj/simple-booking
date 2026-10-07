<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends the emails defined in SB_Notifications.
 */
class SB_Email {

	public const REMINDER_HOOK = 'sb_send_reminders';

	// Which customer email a status change sends. Pending has none.
	private const STATUS_TEMPLATES = [
		'confirmed' => 'customer_confirmed',
		'cancelled' => 'customer_cancelled',
		'completed' => 'customer_completed',
	];

	/**
	 * New booking: customer, assigned staff member and admin. A booking the admin made
	 * skips the admin email, and one created as confirmed gets the confirmation email.
	 */
	public function booking_created( int $booking_id, bool $by_admin = false, bool $notify_customer = true ): void {
		$data = $this->get_booking_data( $booking_id );
		if ( ! $data ) {
			return;
		}
		if ( $notify_customer ) {
			$this->send( 'confirmed' === $data['status'] ? 'customer_confirmed' : 'customer_received', $data['customer_email'], $data );
		}
		$this->send( 'staff_new', (string) $data['staff_email'], $data );
		if ( ! $by_admin ) {
			$this->send( 'admin_new', (string) SB_Settings::get_settings()['admin_email'], $data );
		}
	}

	/**
	 * Tell the admin a customer cancelled or moved a booking from their link.
	 */
	public function customer_changed( int $booking_id, string $what ): void {
		$data = $this->get_booking_data( $booking_id );
		if ( $data ) {
			$data['change'] = $what;
			$this->send( 'admin_customer_change', (string) SB_Settings::get_settings()['admin_email'], $data );
		}
	}

	public function rescheduled( int $booking_id ): void {
		$data = $this->get_booking_data( $booking_id );
		if ( $data ) {
			$this->send( 'customer_rescheduled', $data['customer_email'], $data );
		}
	}

	public function status_changed( int $booking_id, string $status ): void {
		$data = isset( self::STATUS_TEMPLATES[ $status ] ) ? $this->get_booking_data( $booking_id ) : null;
		if ( $data ) {
			$this->send( self::STATUS_TEMPLATES[ $status ], $data['customer_email'], $data );
		}
	}

	/**
	 * Hourly cron: one reminder per pending/confirmed booking that starts within the reminder window.
	 *
	 * @return int reminders sent
	 */
	public function send_reminders(): int {
		global $wpdb;
		$template = SB_Notifications::get( 'customer_reminder' );
		if ( empty( $template['enabled'] ) ) {
			return 0;
		}

		$now   = time();
		$until = $now + (int) $template['hours'] * HOUR_IN_SECONDS;
		$table = $wpdb->prefix . 'sb_bookings';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, booking_date, booking_time FROM $table
				 WHERE reminder_sent IS NULL AND status IN ('pending', 'confirmed') AND booking_date BETWEEN %s AND %s",
				wp_date( 'Y-m-d', $now ),
				wp_date( 'Y-m-d', $until )
			),
			ARRAY_A
		);

		$sent = 0;
		foreach ( $rows as $row ) {
			$start = self::start_timestamp( $row['booking_date'], $row['booking_time'] );
			if ( $start <= $now || $start > $until ) {
				continue;
			}
			// Claim the booking first so an overlapping cron run can't send it twice.
			$claimed = $wpdb->query(
				$wpdb->prepare( "UPDATE $table SET reminder_sent = %s WHERE id = %d AND reminder_sent IS NULL", current_time( 'mysql', true ), $row['id'] )
			);
			if ( 1 !== (int) $claimed ) {
				continue;
			}
			$data = $this->get_booking_data( (int) $row['id'] );
			if ( $data && $this->send( 'customer_reminder', $data['customer_email'], $data ) ) {
				$sent++;
			} else {
				// Try again next hour.
				$wpdb->update( $table, [ 'reminder_sent' => null ], [ 'id' => $row['id'] ], null, [ '%d' ] );
			}
		}
		return $sent;
	}

	/**
	 * Whether a booking starting at this local date/time is already inside the reminder window,
	 * so it needs no reminder (the customer has just had the booking email).
	 */
	public static function inside_reminder_window( string $date, string $time ): bool {
		$hours = (int) SB_Notifications::get( 'customer_reminder' )['hours'];
		return self::start_timestamp( $date, $time ) - time() <= $hours * HOUR_IN_SECONDS;
	}

	/**
	 * Send one template to $to with sample or real booking data, ignoring its on/off switch.
	 */
	public function send_test( string $key, string $to, ?int $booking_id = null ): bool {
		$data = $booking_id ? $this->get_booking_data( $booking_id ) : null;
		$data = $data ?: $this->sample_data();
		return $this->send( $key, $to, $data, true );
	}

	private function send( string $key, string $to, array $data, bool $force = false ): bool {
		$template = SB_Notifications::get( $key );
		if ( ( ! $force && empty( $template['enabled'] ) ) || ! is_email( $to ) ) {
			return false;
		}
		$email = SB_Notifications::render( $template, $this->placeholder_values( $data ) );
		return wp_mail( $to, $email['subject'], $this->layout( $email['html'] ), [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	private function layout( string $html ): string {
		return '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1d2330;max-width:560px">' . $html . '</div>';
	}

	private function placeholder_values( array $d ): array {
		$statuses = [
			'pending'   => __( 'Pending', 'simple-booking' ),
			'confirmed' => __( 'Confirmed', 'simple-booking' ),
			'cancelled' => __( 'Cancelled', 'simple-booking' ),
			'completed' => __( 'Completed', 'simple-booking' ),
		];
		return [
			'{customer_name}'  => (string) $d['customer_name'],
			'{customer_email}' => (string) $d['customer_email'],
			'{customer_phone}' => (string) $d['customer_phone'],
			'{service_name}'   => (string) $d['service_name'],
			'{staff_name}'     => (string) $d['staff_name'],
			'{booking_date}'   => mysql2date( get_option( 'date_format' ), $d['booking_date'] ),
			'{booking_time}'   => substr( (string) $d['booking_time'], 0, 5 ),
			'{end_time}'       => substr( (string) $d['end_time'], 0, 5 ),
			'{price}'          => sb_price( $d['price'] ),
			'{booking_code}'   => (string) $d['booking_code'],
			'{status}'         => $statuses[ $d['status'] ] ?? (string) $d['status'],
			'{notes}'          => (string) $d['notes'],
			'{custom_fields}'  => SB_Custom_Fields::as_text( $d['custom_fields'] ?? null ),
			'{manage_link}'    => isset( $d['id'] ) ? SB_Manage::url( $d ) : home_url( '/' ),
			'{change}'         => (string) ( $d['change'] ?? '' ),
			'{business_name}'  => (string) SB_Settings::get_settings()['business_name'],
			'{location_name}'  => (string) ( $d['location_name'] ?? '' ),
			'{location_address}' => preg_replace( '/\s*\R\s*/', ', ', trim( (string) ( $d['location_address'] ?? '' ) ) ),
		];
	}

	private function sample_data(): array {
		$start = time() + DAY_IN_SECONDS;
		return [
			'customer_name'  => __( 'Sample Customer', 'simple-booking' ),
			'customer_email' => 'customer@example.com',
			'customer_phone' => '+1 555 0100',
			'service_name'   => __( 'Sample Service', 'simple-booking' ),
			'staff_name'     => __( 'Sample Staff Member', 'simple-booking' ),
			'staff_email'    => '',
			'booking_date'   => wp_date( 'Y-m-d', $start ),
			'booking_time'   => '10:00:00',
			'end_time'       => '11:00:00',
			'price'          => 50,
			'booking_code'   => 'SB-SAMPLE',
			'status'         => 'confirmed',
			'notes'          => __( 'This is a test email.', 'simple-booking' ),
		];
	}

	private static function start_timestamp( string $date, string $time ): int {
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $date . ' ' . substr( $time, 0, 8 ), wp_timezone() );
		return $start ? $start->getTimestamp() : 0;
	}

	private function get_booking_data( int $booking_id ): ?array {
		global $wpdb;
		$p   = $wpdb->prefix;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, s.name AS service_name, s.price, c.name AS customer_name, c.email AS customer_email,
					c.phone AS customer_phone, st.name AS staff_name, st.email AS staff_email,
					l.name AS location_name, l.address AS location_address
				 FROM {$p}sb_bookings b
				 LEFT JOIN {$p}sb_locations l ON l.id = b.location_id
				 JOIN {$p}sb_services s ON b.service_id = s.id
				 JOIN {$p}sb_customers c ON b.customer_id = c.id
				 LEFT JOIN {$p}sb_staff st ON b.staff_id = st.id
				 WHERE b.id = %d",
				$booking_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}
}
