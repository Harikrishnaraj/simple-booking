<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSlot_Validator {

	// Letters in any script, with spaces, dots, hyphens and apostrophes between them.
	public const NAME_PATTERN = "/^[\\p{L}\\p{M}][\\p{L}\\p{M} .'’-]*$/u";

	/**
	 * Why a customer's name is not acceptable, or '' when it is.
	 */
	public static function name_error( string $name ): string {
		if ( '' === $name ) {
			return __( 'Please enter your name.', 'counterslot' );
		}
		if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 100 ) {
			return __( 'Please enter your full name (2 to 100 characters).', 'counterslot' );
		}
		if ( ! preg_match( self::NAME_PATTERN, $name ) ) {
			return __( 'Your name can only contain letters, spaces, dots, hyphens and apostrophes.', 'counterslot' );
		}
		return '';
	}

	/**
	 * Why an (optional) phone number is not acceptable, or '' when it is fine or empty.
	 */
	public static function phone_error( string $phone ): string {
		if ( '' === $phone ) {
			return '';
		}
		$digits = strlen( preg_replace( '/\\D/', '', $phone ) );
		if ( ! preg_match( '/^\\+?[0-9 ().-]+$/', $phone ) || $digits < 7 || $digits > 15 ) {
			return __( 'Please enter a valid phone number: 7 to 15 digits, optionally starting with +.', 'counterslot' );
		}
		return '';
	}

	/**
	 * Validate the public booking form. Checks shape only; whether the date/time
	 * is actually bookable is decided by CSlot_Bookings::get_available_slots().
	 *
	 * @param array $in Unslashed request data.
	 */
	public static function booking_request( array $in ): array|WP_Error {
		$data = [
			'name'         => sanitize_text_field( $in['name'] ?? '' ),
			'email'        => sanitize_email( $in['email'] ?? '' ),
			'phone'        => sanitize_text_field( $in['phone'] ?? '' ),
			'service_id'   => absint( $in['service_id'] ?? 0 ),
			'staff_id'     => absint( $in['staff_id'] ?? 0 ),
			'location_id'  => absint( $in['location_id'] ?? 0 ),
			'booking_date' => sanitize_text_field( $in['booking_date'] ?? '' ),
			'booking_time' => sanitize_text_field( $in['booking_time'] ?? '' ),
			'notes'        => sanitize_textarea_field( $in['notes'] ?? '' ),
		];

		$error = self::name_error( $data['name'] );
		if ( '' !== $error ) {
			return new WP_Error( 'name', $error );
		}
		if ( ! is_email( $data['email'] ) || strlen( $data['email'] ) > 191 ) {
			return new WP_Error( 'email', __( 'Please enter a valid email address.', 'counterslot' ) );
		}
		$error = self::phone_error( $data['phone'] );
		if ( '' !== $error ) {
			return new WP_Error( 'phone', $error );
		}
		if ( mb_strlen( $data['notes'] ) > 2000 ) {
			return new WP_Error( 'notes', __( 'Notes must be 2000 characters or fewer.', 'counterslot' ) );
		}
		if ( ! $data['service_id']
			|| ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['booking_date'] )
			|| ! preg_match( '/^\d{2}:\d{2}$/', $data['booking_time'] ) ) {
			return new WP_Error( 'slot', __( 'Please choose a service, day and time.', 'counterslot' ) );
		}

		return $data;
	}
}
