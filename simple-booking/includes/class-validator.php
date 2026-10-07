<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Validator {

	/**
	 * Validate the public booking form. Checks shape only; whether the date/time
	 * is actually bookable is decided by SB_Bookings::get_available_slots().
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

		if ( '' === $data['name'] || mb_strlen( $data['name'] ) > 191 ) {
			return new WP_Error( 'name', __( 'Please enter your name.', 'simple-booking' ) );
		}
		if ( ! is_email( $data['email'] ) || strlen( $data['email'] ) > 191 ) {
			return new WP_Error( 'email', __( 'Please enter a valid email address.', 'simple-booking' ) );
		}
		if ( mb_strlen( $data['phone'] ) > 50 ) {
			return new WP_Error( 'phone', __( 'Please enter a shorter phone number.', 'simple-booking' ) );
		}
		if ( mb_strlen( $data['notes'] ) > 2000 ) {
			return new WP_Error( 'notes', __( 'Notes must be 2000 characters or fewer.', 'simple-booking' ) );
		}
		if ( ! $data['service_id']
			|| ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['booking_date'] )
			|| ! preg_match( '/^\d{2}:\d{2}$/', $data['booking_time'] ) ) {
			return new WP_Error( 'slot', __( 'Please choose a service, day and time.', 'simple-booking' ) );
		}

		return $data;
	}
}
