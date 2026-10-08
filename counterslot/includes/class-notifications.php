<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email templates: which emails exist, their saved text and on/off state, and filling in placeholders.
 * Sending lives in CSlot_Email.
 */
class CSlot_Notifications {

	private const OPTION_KEY = 'cslot_email_templates';

	public const REMINDER_HOURS_DEFAULT = 24;

	/**
	 * Every email the plugin can send, in display order.
	 *
	 * @return array<string, array{audience: string, label: string, when: string}>
	 */
	public static function types(): array {
		return [
			'customer_received'  => [ 'audience' => 'customer', 'label' => __( 'Booking received', 'counterslot' ), 'when' => __( 'Sent when a customer books.', 'counterslot' ) ],
			'customer_confirmed' => [ 'audience' => 'customer', 'label' => __( 'Confirmed', 'counterslot' ), 'when' => __( 'Sent when you change a booking to Confirmed.', 'counterslot' ) ],
			'customer_cancelled' => [ 'audience' => 'customer', 'label' => __( 'Cancelled', 'counterslot' ), 'when' => __( 'Sent when you change a booking to Cancelled.', 'counterslot' ) ],
			'customer_rescheduled' => [ 'audience' => 'customer', 'label' => __( 'Rescheduled', 'counterslot' ), 'when' => __( 'Sent when you move a booking to another time, unless you untick "Email the customer".', 'counterslot' ) ],
			'customer_completed' => [ 'audience' => 'customer', 'label' => __( 'Completed (follow-up)', 'counterslot' ), 'when' => __( 'Sent when you change a booking to Completed.', 'counterslot' ) ],
			'event_registered'   => [ 'audience' => 'customer', 'label' => __( 'Event registration', 'counterslot' ), 'when' => __( 'Sent when someone registers for an event.', 'counterslot' ) ],
			'event_cancelled'    => [ 'audience' => 'customer', 'label' => __( 'Event cancelled', 'counterslot' ), 'when' => __( 'Sent to everyone registered when you cancel an event, or to one person when you cancel their registration.', 'counterslot' ) ],
			'customer_reminder'  => [ 'audience' => 'customer', 'label' => __( 'Reminder', 'counterslot' ), 'when' => __( 'Sent once before a pending or confirmed appointment.', 'counterslot' ) ],
			'staff_new'          => [ 'audience' => 'staff', 'label' => __( 'New booking (staff member)', 'counterslot' ), 'when' => __( 'Sent to the staff member a new booking is assigned to.', 'counterslot' ) ],
			'admin_customer_change' => [ 'audience' => 'staff', 'label' => __( 'Customer cancelled or moved', 'counterslot' ), 'when' => __( 'Sent to the notification email in Settings when a customer cancels or reschedules from their link.', 'counterslot' ) ],
			'admin_event_registered' => [ 'audience' => 'staff', 'label' => __( 'Event registration (admin)', 'counterslot' ), 'when' => __( 'Sent to the notification email in Settings when someone registers for an event.', 'counterslot' ) ],
			'admin_new'          => [ 'audience' => 'staff', 'label' => __( 'New booking (admin)', 'counterslot' ), 'when' => __( 'Sent to the notification email in Settings when a customer books.', 'counterslot' ) ],
		];
	}

	/**
	 * Placeholder => description, for the editor.
	 */
	public static function placeholders(): array {
		return [
			'{customer_name}'  => __( 'Customer name', 'counterslot' ),
			'{customer_email}' => __( 'Customer email', 'counterslot' ),
			'{customer_phone}' => __( 'Customer phone', 'counterslot' ),
			'{service_name}'   => __( 'Service', 'counterslot' ),
			'{staff_name}'     => __( 'Staff member', 'counterslot' ),
			'{booking_date}'   => __( 'Date', 'counterslot' ),
			'{booking_time}'   => __( 'Start time', 'counterslot' ),
			'{end_time}'       => __( 'End time', 'counterslot' ),
			'{price}'          => __( 'Total price', 'counterslot' ),
			'{price_details}'  => __( 'Price breakdown (extras, coupon, tax, total)', 'counterslot' ),
			'{deposit}'        => __( 'Deposit asked for in advance', 'counterslot' ),
			'{amount_paid}'    => __( 'Amount paid so far', 'counterslot' ),
			'{balance}'        => __( 'Amount still due', 'counterslot' ),
			'{booking_code}'   => __( 'Booking code', 'counterslot' ),
			'{status}'         => __( 'Status', 'counterslot' ),
			'{notes}'          => __( 'Customer notes', 'counterslot' ),
			'{custom_fields}'  => __( 'Answers to custom fields', 'counterslot' ),
			'{recurring_details}' => __( 'All sessions of a recurring booking', 'counterslot' ),
			'{business_name}'  => __( 'Business name', 'counterslot' ),
			'{location_name}'  => __( 'Location', 'counterslot' ),
			'{location_address}' => __( 'Location address', 'counterslot' ),
			'{event_name}'     => __( 'Event name (event emails)', 'counterslot' ),
			'{spots}'          => __( 'Places registered (event emails)', 'counterslot' ),
			'{manage_link}'    => __( 'Link for the customer to view, cancel or reschedule', 'counterslot' ),
			'{change}'         => __( 'What the customer changed (admin email)', 'counterslot' ),
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
		$settings = CSlot_Settings::get_settings();
		$customer = ! empty( $settings['customer_notification'] );

		$manage  = "\n\n" . __( 'View, cancel or reschedule:', 'counterslot' ) . ' {manage_link}';
		$details = "{service_name}\n" .
			__( 'Date:', 'counterslot' ) . " {booking_date}\n" .
			__( 'Time:', 'counterslot' ) . " {booking_time}–{end_time}\n" .
			__( 'With:', 'counterslot' ) . " {staff_name}\n" .
			__( 'Where:', 'counterslot' ) . " {location_name} {location_address}\n" .
			__( 'Booking code:', 'counterslot' ) . ' {booking_code}';

		return [
			'customer_received'  => [
				'enabled' => $customer,
				/* translators: {booking_code} stays as is: it is replaced by the booking code. */
				'subject' => __( 'We received your booking {booking_code}', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Thank you for booking with {business_name}. We will email you again once it is confirmed.', 'counterslot' ) . "\n\n" . $details . "\n\n{price_details}\n\n{recurring_details}" . $manage,
			],
			'customer_confirmed' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking is confirmed: {service_name} on {booking_date}', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Your appointment is confirmed. See you then!', 'counterslot' ) . "\n\n" . $details . "\n\n{price_details}\n\n{recurring_details}" . $manage,
			],
			'customer_cancelled' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking {booking_code} was cancelled', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Your appointment has been cancelled. If this is unexpected, please contact {business_name}.', 'counterslot' ) . "\n\n" . $details,
			],
			'customer_rescheduled' => [
				'enabled' => $customer,
				'subject' => __( 'Your booking {booking_code} has a new time', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Your appointment has been moved. Here are the new details:', 'counterslot' ) . "\n\n" . $details . $manage,
			],
			'customer_completed' => [
				'enabled' => $customer,
				'subject' => __( 'Thank you for visiting {business_name}', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Thank you for your visit for {service_name} on {booking_date}. We hope to see you again soon.', 'counterslot' ) . "\n\n{business_name}",
			],
			'customer_reminder'  => [
				'enabled' => $customer,
				'hours'   => self::REMINDER_HOURS_DEFAULT,
				'subject' => __( 'Reminder: {service_name} on {booking_date} at {booking_time}', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'This is a reminder of your upcoming appointment with {business_name}.', 'counterslot' ) . "\n\n" . $details . $manage,
			],
			'staff_new'          => [
				'enabled' => false,
				'subject' => __( 'New booking: {service_name} on {booking_date} at {booking_time}', 'counterslot' ),
				'body'    => __( 'Hi {staff_name},', 'counterslot' ) . "\n\n" . __( 'You have a new booking.', 'counterslot' ) . "\n\n" . $details . "\n\n" .
					__( 'Customer:', 'counterslot' ) . " {customer_name}\n{customer_email}\n{customer_phone}\n" . __( 'Notes:', 'counterslot' ) . " {notes}\n{custom_fields}",
			],
			'event_registered'   => [
				'enabled' => $customer,
				'subject' => __( 'You\'re registered: {event_name} on {booking_date}', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Thank you for registering. See you there!', 'counterslot' ) . "\n\n{event_name}\n" .
					__( 'Date:', 'counterslot' ) . " {booking_date}\n" . __( 'Time:', 'counterslot' ) . " {booking_time}–{end_time}\n" .
					__( 'Where:', 'counterslot' ) . " {location_name} {location_address}\n" . __( 'Places:', 'counterslot' ) . " {spots}\n" .
					__( 'Price:', 'counterslot' ) . " {price}\n" . __( 'Registration code:', 'counterslot' ) . ' {booking_code}',
			],
			'event_cancelled'    => [
				'enabled' => $customer,
				'subject' => __( 'Cancelled: {event_name} on {booking_date}', 'counterslot' ),
				'body'    => __( 'Hi {customer_name},', 'counterslot' ) . "\n\n" . __( 'Your registration for {event_name} on {booking_date} at {booking_time} has been cancelled. Please contact {business_name} if you have questions.', 'counterslot' ),
			],
			'admin_event_registered' => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( 'New registration: {event_name} ({spots})', 'counterslot' ),
				'body'    => '{customer_name} ' . __( 'registered for', 'counterslot' ) . " {event_name} ({booking_date} {booking_time}).\n\n" .
					__( 'Places:', 'counterslot' ) . " {spots}\n" . __( 'Price:', 'counterslot' ) . " {price}\n{customer_email}\n{customer_phone}",
			],
			'admin_customer_change' => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( '{customer_name} changed booking {booking_code}', 'counterslot' ),
				'body'    => '{change}' . "\n\n" . $details . "\n" . __( 'Status:', 'counterslot' ) . " {status}\n\n" .
					__( 'Customer:', 'counterslot' ) . " {customer_name}\n{customer_email}\n{customer_phone}",
			],
			'admin_new'          => [
				'enabled' => ! empty( $settings['admin_notification'] ),
				'subject' => __( 'New booking {booking_code}: {customer_name}', 'counterslot' ),
				'body'    => __( 'A new booking is waiting for you.', 'counterslot' ) . "\n\n" . $details . "\n" . __( 'Status:', 'counterslot' ) . " {status}\n\n" .
					__( 'Customer:', 'counterslot' ) . " {customer_name}\n{customer_email}\n{customer_phone}\n" . __( 'Notes:', 'counterslot' ) . " {notes}\n{custom_fields}",
			],
		];
	}
}
