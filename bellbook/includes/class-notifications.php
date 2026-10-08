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
			'customer_received'  => [ 'audience' => 'customer', 'label' => __( 'Booking received', 'bellbook' ), 'when' => __( 'Sent when a customer books.', 'bellbook' ) ],
			'customer_confirmed' => [ 'audience' => 'customer', 'label' => __( 'Confirmed', 'bellbook' ), 'when' => __( 'Sent when you change a booking to Confirmed.', 'bellbook' ) ],
			'customer_cancelled' => [ 'audience' => 'customer', 'label' => __( 'Cancelled', 'bellbook' ), 'when' => __( 'Sent when you change a booking to Cancelled.', 'bellbook' ) ],
			'customer_rescheduled' => [ 'audience' => 'customer', 'label' => __( 'Rescheduled', 'bellbook' ), 'when' => __( 'Sent when you move a booking to another time, unless you untick "Email the customer".', 'bellbook' ) ],
			'customer_completed' => [ 'audience' => 'customer', 'label' => __( 'Completed (follow-up)', 'bellbook' ), 'when' => __( 'Sent when you change a booking to Completed.', 'bellbook' ) ],
			'event_registered'   => [ 'audience' => 'customer', 'label' => __( 'Event registration', 'bellbook' ), 'when' => __( 'Sent when someone registers for an event.', 'bellbook' ) ],
			'event_cancelled'    => [ 'audience' => 'customer', 'label' => __( 'Event cancelled', 'bellbook' ), 'when' => __( 'Sent to everyone registered when you cancel an event, or to one person when you cancel their registration.', 'bellbook' ) ],
			'customer_reminder'  => [ 'audience' => 'customer', 'label' => __( 'Reminder', 'bellbook' ), 'when' => __( 'Sent once before a pending or confirmed appointment.', 'bellbook' ) ],
			'staff_new'          => [ 'audience' => 'staff', 'label' => __( 'New booking (staff member)', 'bellbook' ), 'when' => __( 'Sent to the staff member a new booking is assigned to.', 'bellbook' ) ],
			'admin_customer_change' => [ 'audience' => 'staff', 'label' => __( 'Customer cancelled or moved', 'bellbook' ), 'when' => __( 'Sent to the notification email in Settings when a customer cancels or reschedules from their link.', 'bellbook' ) ],
			'admin_event_registered' => [ 'audience' => 'staff', 'label' => __( 'Event registration (admin)', 'bellbook' ), 'when' => __( 'Sent to the notification email in Settings when someone registers for an event.', 'bellbook' ) ],
			'admin_new'          => [ 'audience' => 'staff', 'label' => __( 'New booking (admin)', 'bellbook' ), 'when' => __( 'Sent to the notification email in Settings when a customer books.', 'bellbook' ) ],
		];
	}

	/**
	 * Placeholder => description, for the editor.
	 */
	public static function placeholders(): array {
		return [
			'{customer_name}'  => __( 'Customer name', 'bellbook' ),
			'{customer_email}' => __( 'Customer email', 'bellbook' ),
			'{customer_phone}' => __( 'Customer phone', 'bellbook' ),
			'{service_name}'   => __( 'Service', 'bellbook' ),
			'{staff_name}'     => __( 'Staff member', 'bellbook' ),
			'{booking_date}'   => __( 'Date', 'bellbook' ),
			'{booking_time}'   => __( 'Start time', 'bellbook' ),
			'{end_time}'       => __( 'End time', 'bellbook' ),
			'{price}'          => __( 'Total price', 'bellbook' ),
			'{price_details}'  => __( 'Price breakdown (extras, coupon, tax, total)', 'bellbook' ),
			'{deposit}'        => __( 'Deposit asked for in advance', 'bellbook' ),
			'{amount_paid}'    => __( 'Amount paid so far', 'bellbook' ),
			'{balance}'        => __( 'Amount still due', 'bellbook' ),
			'{booking_code}'   => __( 'Booking code', 'bellbook' ),
			'{status}'         => __( 'Status', 'bellbook' ),
			'{notes}'          => __( 'Customer notes', 'bellbook' ),
			'{custom_fields}'  => __( 'Answers to custom fields', 'bellbook' ),
			'{recurring_details}' => __( 'All sessions of a recurring booking', 'bellbook' ),
			'{business_name}'  => __( 'Business name', 'bellbook' ),
			'{location_name}'  => __( 'Location', 'bellbook' ),
			'{location_address}' => __( 'Location address', 'bellbook' ),
			'{event_name}'     => __( 'Event name (event emails)', 'bellbook' ),
			'{spots}'          => __( 'Places registered (event emails)', 'bellbook' ),
			'{manage_link}'    => __( 'Link for the customer to view, cancel or reschedule', 'bellbook' ),
			'{change}'         => __( 'What the customer changed (admin email)', 'bellbook' ),
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

		$manage  = "\n\n" . __( 'View, cancel or reschedule:', 'bellbook' ) . ' {manage_link}';
		$details = "{service_name}\n" .
			__( 'Date:', 'bellbook' ) . " {booking_date}\n" .
			__( 'Time:', 'bellbook' ) . " {booking_time}–{end_time}\n" .
			__( 'With:', 'bellbook' ) . " {staff_name}\n" .
			__( 'Where:', 'bellbook' ) . " {location_name} {location_address}\n" .
			__( 'Booking code:', 'bellbook' ) . ' {booking_code}';

		return [
			'customer_received'  => [
				'enabled' => $customer,
				/* translators: {booking_code} stays as is: it is replaced by the booking code. */
				'subject' => __( 'We received your booking {booking_code}', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Thank you for booking with {business_name}. We will email you again once it is confirmed.', 'bellbook' ) . "\n\n" . $details . "\n\n{price_details}\n\n{recurring_details}" . $manage,
			],
			'customer_confirmed' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking is confirmed: {service_name} on {booking_date}', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Your appointment is confirmed. See you then!', 'bellbook' ) . "\n\n" . $details . "\n\n{price_details}\n\n{recurring_details}" . $manage,
			],
			'customer_cancelled' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking {booking_code} was cancelled', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Your appointment has been cancelled. If this is unexpected, please contact {business_name}.', 'bellbook' ) . "\n\n" . $details,
			],
			'customer_rescheduled' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking {booking_code} has a new time', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Your appointment has been moved. Here are the new details:', 'bellbook' ) . "\n\n" . $details . $manage,
			],
			'customer_completed' => [
				'enabled' => $customer,
				'subject' => __( 'Thank you for visiting {business_name}', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Thank you for your visit for {service_name} on {booking_date}. We hope to see you again soon.', 'bellbook' ) . "\n\n{business_name}",
			],
			'customer_reminder'  => [
				'enabled' => $customer,
				'hours'   => self::REMINDER_HOURS_DEFAULT,
				'subject' => __( 'Reminder: {service_name} on {booking_date} at {booking_time}', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'This is a reminder of your upcoming appointment with {business_name}.', 'bellbook' ) . "\n\n" . $details . $manage,
			],
			'staff_new'          => [
				'enabled' => false,
				'subject' => __( 'New booking: {service_name} on {booking_date} at {booking_time}', 'bellbook' ),
				'body'    => __( 'Hi {staff_name},', 'bellbook' ) . "\n\n" . __( 'You have a new booking.', 'bellbook' ) . "\n\n" . $details . "\n\n" .
					__( 'Customer:', 'bellbook' ) . " {customer_name}\n{customer_email}\n{customer_phone}\n" . __( 'Notes:', 'bellbook' ) . " {notes}\n{custom_fields}",
			],
			'event_registered'   => [
				'enabled' => $customer,
				'subject' => __( 'You\'re registered: {event_name} on {booking_date}', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Thank you for registering. See you there!', 'bellbook' ) . "\n\n{event_name}\n" .
					__( 'Date:', 'bellbook' ) . " {booking_date}\n" . __( 'Time:', 'bellbook' ) . " {booking_time}–{end_time}\n" .
					__( 'Where:', 'bellbook' ) . " {location_name} {location_address}\n" . __( 'Places:', 'bellbook' ) . " {spots}\n" .
					__( 'Price:', 'bellbook' ) . " {price}\n" . __( 'Registration code:', 'bellbook' ) . ' {booking_code}',
			],
			'event_cancelled'    => [
				'enabled' => $customer,
				'subject' => __( 'Cancelled: {event_name} on {booking_date}', 'bellbook' ),
				'body'    => __( 'Hi {customer_name},', 'bellbook' ) . "\n\n" . __( 'Your registration for {event_name} on {booking_date} at {booking_time} has been cancelled. Please contact {business_name} if you have questions.', 'bellbook' ),
			],
			'admin_event_registered' => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( 'New registration: {event_name} ({spots})', 'bellbook' ),
				'body'    => '{customer_name} ' . __( 'registered for', 'bellbook' ) . " {event_name} ({booking_date} {booking_time}).\n\n" .
					__( 'Places:', 'bellbook' ) . " {spots}\n" . __( 'Price:', 'bellbook' ) . " {price}\n{customer_email}\n{customer_phone}",
			],
			'admin_customer_change' => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( '{customer_name} changed booking {booking_code}', 'bellbook' ),
				'body'    => '{change}' . "\n\n" . $details . "\n" . __( 'Status:', 'bellbook' ) . " {status}\n\n" .
					__( 'Customer:', 'bellbook' ) . " {customer_name}\n{customer_email}\n{customer_phone}",
			],
			'admin_new'          => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( 'New booking {booking_code}: {customer_name}', 'bellbook' ),
				'body'    => __( 'A new booking is waiting for you.', 'bellbook' ) . "\n\n" . $details . "\n" . __( 'Status:', 'bellbook' ) . " {status}\n\n" .
					__( 'Customer:', 'bellbook' ) . " {customer_name}\n{customer_email}\n{customer_phone}\n" . __( 'Notes:', 'bellbook' ) . " {notes}\n{custom_fields}",
			],
		];
	}
}
