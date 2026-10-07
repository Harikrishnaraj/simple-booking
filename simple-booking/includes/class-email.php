<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Email {

	public function send_customer_confirmation( int $booking_id ): bool {
		$data = $this->get_booking_data( $booking_id );
		if ( ! $data || empty( SB_Settings::get_settings()['customer_notification'] ) ) return false;

		$to      = $data['customer_email'];
		$subject = sprintf( __( 'Booking Received - %s', 'simple-booking' ), $data['booking_code'] );
		$message = sprintf(
			"<h2>Thank you for your booking, %s!</h2>
			<p>We have received your booking (<strong>%s</strong>) for <strong>%s</strong>. We will let you know once it is confirmed.</p>
			<p><strong>Date:</strong> %s<br><strong>Time:</strong> %s%s</p>",
			esc_html( $data['customer_name'] ),
			esc_html( $data['booking_code'] ),
			esc_html( $data['service_name'] ),
			esc_html( $data['booking_date'] ),
			esc_html( $data['booking_time'] ),
			$data['staff_name'] ? '<br><strong>' . esc_html__( 'With:', 'simple-booking' ) . '</strong> ' . esc_html( $data['staff_name'] ) : ''
		);

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		return wp_mail( $to, $subject, $message, $headers );
	}

	public function send_admin_notification( int $booking_id ): bool {
		$data     = $this->get_booking_data( $booking_id );
		$settings = SB_Settings::get_settings();
		if ( ! $data || empty( $settings['admin_notification'] ) || empty( $settings['admin_email'] ) ) return false;

		$to      = $settings['admin_email'];
		$subject = sprintf( __( 'New Booking Received: %s', 'simple-booking' ), $data['booking_code'] );
		$message = sprintf(
			"<h3>New Booking Alert</h3>
			<p>Customer: %s (%s)<br>Service: %s<br>Staff: %s<br>Date/Time: %s at %s</p>",
			esc_html( $data['customer_name'] ),
			esc_html( $data['customer_email'] ),
			esc_html( $data['service_name'] ),
			esc_html( $data['staff_name'] ?: __( 'Not assigned', 'simple-booking' ) ),
			esc_html( $data['booking_date'] ),
			esc_html( $data['booking_time'] )
		);

		return wp_mail( $to, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	public function send_status_update( int $booking_id, string $new_status ): bool {
		$data = $this->get_booking_data( $booking_id );
		if ( ! $data || empty( SB_Settings::get_settings()['customer_notification'] ) ) return false;

		$to      = $data['customer_email'];
		$subject = sprintf( __( 'Booking Status Update: %s', 'simple-booking' ), $data['booking_code'] );
		$message = sprintf(
			"<p>Hello %s, your booking status for <strong>%s</strong>%s on %s at %s has changed to <strong>%s</strong>.</p>",
			esc_html( $data['customer_name'] ),
			esc_html( $data['service_name'] ),
			$data['staff_name'] ? ' ' . esc_html( sprintf( __( 'with %s', 'simple-booking' ), $data['staff_name'] ) ) : '',
			esc_html( $data['booking_date'] ),
			esc_html( $data['booking_time'] ),
			esc_html( strtoupper( $new_status ) )
		);

		return wp_mail( $to, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	private function get_booking_data( int $booking_id ): ?array {
		global $wpdb;
		$b_table = $wpdb->prefix . 'sb_bookings';
		$s_table = $wpdb->prefix . 'sb_services';
		$c_table = $wpdb->prefix . 'sb_customers';
		$st_table = $wpdb->prefix . 'sb_staff';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, s.name as service_name, c.name as customer_name, c.email as customer_email, st.name as staff_name
				 FROM $b_table b
				 JOIN $s_table s ON b.service_id = s.id
				 JOIN $c_table c ON b.customer_id = c.id
				 LEFT JOIN $st_table st ON b.staff_id = st.id
				 WHERE b.id = %d",
				$booking_id
			),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}

		// Human-readable date/time for every email (stored as Y-m-d / H:i:s).
		$row['booking_date'] = mysql2date( get_option( 'date_format' ), $row['booking_date'] );
		$row['booking_time'] = substr( $row['booking_time'], 0, 5 );
		return $row;
	}
}
