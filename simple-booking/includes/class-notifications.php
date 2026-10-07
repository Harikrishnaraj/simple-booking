<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email templates: which emails exist, their saved text and on/off state, and filling in placeholders.
 * Sending lives in SB_Email.
 */
class SB_Notifications {

	private const OPTION_KEY = 'sb_email_templates';

	public const REMINDER_HOURS_DEFAULT = 24;

	/**
	 * Every email the plugin can send, in display order.
	 *
	 * @return array<string, array{audience: string, label: string, when: string}>
	 */
	public static function types(): array {
		return [
			'customer_received'  => [ 'audience' => 'customer', 'label' => __( 'Booking received', 'simple-booking' ), 'when' => __( 'Sent when a customer books.', 'simple-booking' ) ],
			'customer_confirmed' => [ 'audience' => 'customer', 'label' => __( 'Confirmed', 'simple-booking' ), 'when' => __( 'Sent when you change a booking to Confirmed.', 'simple-booking' ) ],
			'customer_cancelled' => [ 'audience' => 'customer', 'label' => __( 'Cancelled', 'simple-booking' ), 'when' => __( 'Sent when you change a booking to Cancelled.', 'simple-booking' ) ],
			'customer_rescheduled' => [ 'audience' => 'customer', 'label' => __( 'Rescheduled', 'simple-booking' ), 'when' => __( 'Sent when you move a booking to another time, unless you untick "Email the customer".', 'simple-booking' ) ],
			'customer_completed' => [ 'audience' => 'customer', 'label' => __( 'Completed (follow-up)', 'simple-booking' ), 'when' => __( 'Sent when you change a booking to Completed.', 'simple-booking' ) ],
			'customer_reminder'  => [ 'audience' => 'customer', 'label' => __( 'Reminder', 'simple-booking' ), 'when' => __( 'Sent once before a pending or confirmed appointment.', 'simple-booking' ) ],
			'staff_new'          => [ 'audience' => 'staff', 'label' => __( 'New booking (staff member)', 'simple-booking' ), 'when' => __( 'Sent to the staff member a new booking is assigned to.', 'simple-booking' ) ],
			'admin_customer_change' => [ 'audience' => 'staff', 'label' => __( 'Customer cancelled or moved', 'simple-booking' ), 'when' => __( 'Sent to the notification email in Settings when a customer cancels or reschedules from their link.', 'simple-booking' ) ],
			'admin_new'          => [ 'audience' => 'staff', 'label' => __( 'New booking (admin)', 'simple-booking' ), 'when' => __( 'Sent to the notification email in Settings when a customer books.', 'simple-booking' ) ],
		];
	}

	/**
	 * Placeholder => description, for the editor.
	 */
	public static function placeholders(): array {
		return [
			'{customer_name}'  => __( 'Customer name', 'simple-booking' ),
			'{customer_email}' => __( 'Customer email', 'simple-booking' ),
			'{customer_phone}' => __( 'Customer phone', 'simple-booking' ),
			'{service_name}'   => __( 'Service', 'simple-booking' ),
			'{staff_name}'     => __( 'Staff member', 'simple-booking' ),
			'{booking_date}'   => __( 'Date', 'simple-booking' ),
			'{booking_time}'   => __( 'Start time', 'simple-booking' ),
			'{end_time}'       => __( 'End time', 'simple-booking' ),
			'{price}'          => __( 'Price', 'simple-booking' ),
			'{booking_code}'   => __( 'Booking code', 'simple-booking' ),
			'{status}'         => __( 'Status', 'simple-booking' ),
			'{notes}'          => __( 'Customer notes', 'simple-booking' ),
			'{custom_fields}'  => __( 'Answers to custom fields', 'simple-booking' ),
			'{business_name}'  => __( 'Business name', 'simple-booking' ),
			'{location_name}'  => __( 'Location', 'simple-booking' ),
			'{location_address}' => __( 'Location address', 'simple-booking' ),
			'{manage_link}'    => __( 'Link for the customer to view, cancel or reschedule', 'simple-booking' ),
			'{change}'         => __( 'What the customer changed (admin email)', 'simple-booking' ),
		];
	}

	/**
	 * A template with saved values over the defaults.
	 *
	 * @return array{enabled: bool, subject: string, body: string, hours?: int}
	 */
	public static function get( string $key ): array {
		$saved = (array) ( get_option( self::OPTION_KEY, [] )[ $key ] ?? [] );
		return array_merge( self::defaults()[ $key ] ?? [], $saved );
	}

	/**
	 * @return array<string, array> every template, keyed like types()
	 */
	public static function get_all(): array {
		$out = [];
		foreach ( array_keys( self::types() ) as $key ) {
			$out[ $key ] = self::get( $key );
		}
		return $out;
	}

	public static function save( string $key, array $data ): bool {
		if ( ! isset( self::types()[ $key ] ) ) {
			return false;
		}
		$subject = sanitize_text_field( (string) ( $data['subject'] ?? '' ) );
		$body    = wp_kses_post( (string) ( $data['body'] ?? '' ) );
		if ( '' === $subject || '' === trim( $body ) ) {
			return false;
		}

		$row = [
			'enabled' => rest_sanitize_boolean( $data['enabled'] ?? false ),
			'subject' => $subject,
			'body'    => $body,
		];
		if ( 'customer_reminder' === $key ) {
			$row['hours'] = min( 168, max( 1, absint( $data['hours'] ?? self::REMINDER_HOURS_DEFAULT ) ) );
		}

		$all         = (array) get_option( self::OPTION_KEY, [] );
		$all[ $key ] = $row;
		update_option( self::OPTION_KEY, $all, false );
		return true;
	}

	/**
	 * Fill a template in. $values are placeholder => plain text.
	 *
	 * A body line whose placeholders all came out empty is dropped, so
	 * "With: {staff_name}" disappears when no staff member is assigned.
	 *
	 * @return array{subject: string, html: string}
	 */
	public static function render( array $template, array $values ): array {
		$escaped = array_map( 'esc_html', $values );

		$lines = [];
		foreach ( preg_split( '/\R/', $template['body'] ) as $line ) {
			preg_match_all( '/\{[a-z_]+\}/', $line, $found );
			$used = array_intersect( $found[0], array_keys( $values ) );
			if ( $used && '' === implode( '', array_map( fn( $p ) => trim( (string) $values[ $p ] ), $used ) ) ) {
				continue;
			}
			$lines[] = strtr( $line, $escaped );
		}

		return [
			// Header-safe: one line, no tags.
			'subject' => trim( preg_replace( '/[\r\n]+/', ' ', wp_strip_all_tags( strtr( $template['subject'], $values ) ) ) ),
			'html'    => wpautop( implode( "\n", $lines ) ),
		];
	}

	/**
	 * Built-in text. The old 1.0 switches decide which emails start switched on.
	 */
	private static function defaults(): array {
		$settings = SB_Settings::get_settings();
		$customer = ! empty( $settings['customer_notification'] );

		$manage  = "\n\n" . __( 'View, cancel or reschedule:', 'simple-booking' ) . ' {manage_link}';
		$details = "{service_name}\n" .
			__( 'Date:', 'simple-booking' ) . " {booking_date}\n" .
			__( 'Time:', 'simple-booking' ) . " {booking_time}–{end_time}\n" .
			__( 'With:', 'simple-booking' ) . " {staff_name}\n" .
			__( 'Where:', 'simple-booking' ) . " {location_name} {location_address}\n" .
			__( 'Booking code:', 'simple-booking' ) . ' {booking_code}';

		return [
			'customer_received'  => [
				'enabled' => $customer,
				/* translators: {booking_code} stays as is: it is replaced by the booking code. */
				'subject' => __( 'We received your booking {booking_code}', 'simple-booking' ),
				'body'    => __( 'Hi {customer_name},', 'simple-booking' ) . "\n\n" . __( 'Thank you for booking with {business_name}. We will email you again once it is confirmed.', 'simple-booking' ) . "\n\n" . $details . $manage,
			],
			'customer_confirmed' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking is confirmed: {service_name} on {booking_date}', 'simple-booking' ),
				'body'    => __( 'Hi {customer_name},', 'simple-booking' ) . "\n\n" . __( 'Your appointment is confirmed. See you then!', 'simple-booking' ) . "\n\n" . $details . $manage,
			],
			'customer_cancelled' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking {booking_code} was cancelled', 'simple-booking' ),
				'body'    => __( 'Hi {customer_name},', 'simple-booking' ) . "\n\n" . __( 'Your appointment has been cancelled. If this is unexpected, please contact {business_name}.', 'simple-booking' ) . "\n\n" . $details,
			],
			'customer_rescheduled' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking {booking_code} has a new time', 'simple-booking' ),
				'body'    => __( 'Hi {customer_name},', 'simple-booking' ) . "\n\n" . __( 'Your appointment has been moved. Here are the new details:', 'simple-booking' ) . "\n\n" . $details . $manage,
			],
			'customer_completed' => [
				'enabled' => $customer,
				'subject' => __( 'Thank you for visiting {business_name}', 'simple-booking' ),
				'body'    => __( 'Hi {customer_name},', 'simple-booking' ) . "\n\n" . __( 'Thank you for your visit for {service_name} on {booking_date}. We hope to see you again soon.', 'simple-booking' ) . "\n\n{business_name}",
			],
			'customer_reminder'  => [
				'enabled' => $customer,
				'hours'   => self::REMINDER_HOURS_DEFAULT,
				'subject' => __( 'Reminder: {service_name} on {booking_date} at {booking_time}', 'simple-booking' ),
				'body'    => __( 'Hi {customer_name},', 'simple-booking' ) . "\n\n" . __( 'This is a reminder of your upcoming appointment with {business_name}.', 'simple-booking' ) . "\n\n" . $details . $manage,
			],
			'staff_new'          => [
				'enabled' => false,
				'subject' => __( 'New booking: {service_name} on {booking_date} at {booking_time}', 'simple-booking' ),
				'body'    => __( 'Hi {staff_name},', 'simple-booking' ) . "\n\n" . __( 'You have a new booking.', 'simple-booking' ) . "\n\n" . $details . "\n\n" .
					__( 'Customer:', 'simple-booking' ) . " {customer_name}\n{customer_email}\n{customer_phone}\n" . __( 'Notes:', 'simple-booking' ) . " {notes}\n{custom_fields}",
			],
			'admin_customer_change' => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( '{customer_name} changed booking {booking_code}', 'simple-booking' ),
				'body'    => '{change}' . "\n\n" . $details . "\n" . __( 'Status:', 'simple-booking' ) . " {status}\n\n" .
					__( 'Customer:', 'simple-booking' ) . " {customer_name}\n{customer_email}\n{customer_phone}",
			],
			'admin_new'          => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( 'New booking {booking_code}: {customer_name}', 'simple-booking' ),
				'body'    => __( 'A new booking is waiting for you.', 'simple-booking' ) . "\n\n" . $details . "\n" . __( 'Status:', 'simple-booking' ) . " {status}\n\n" .
					__( 'Customer:', 'simple-booking' ) . " {customer_name}\n{customer_email}\n{customer_phone}\n" . __( 'Notes:', 'simple-booking' ) . " {notes}\n{custom_fields}",
			],
		];
	}
}
