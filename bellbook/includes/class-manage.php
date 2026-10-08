<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer self-service: a signed link in emails lets the customer see their booking and,
 * up to a cut-off before it starts, cancel or move it. No account needed.
 *
 * The token is an HMAC of the booking id and code with the site's secret salt, so it can't be
 * guessed or reused for another booking, and it changes nothing in the database.
 */
class SB_Manage {

	public static function token( array $booking ): string {
		return substr( hash_hmac( 'sha256', $booking['id'] . '|' . $booking['booking_code'], wp_salt( 'auth' ) ), 0, 32 );
	}

	/**
	 * The booking for a link's id and token, or null if they don't match.
	 */
	public static function verify( int $id, string $token ): ?array {
		$booking = $id ? ( new SB_Bookings() )->get_by_id( $id ) : null;
		return $booking && hash_equals( self::token( $booking ), $token ) ? $booking : null;
	}

	/**
	 * Link to the booking page with the booking's id and token, or '' when no booking page is known.
	 */
	public static function url( array $booking ): string {
		$page = self::booking_page_url();
		return $page ? add_query_arg( [ 'sb_booking' => (int) $booking['id'], 'sb_token' => self::token( $booking ) ], $page ) : '';
	}

	/**
	 * The page set in Settings, or the first published page using the [bellbook] shortcode.
	 */
	public static function booking_page_url(): string {
		$id = (int) SB_Settings::get_settings()['booking_page_id'];
		if ( ! $id ) {
			global $wpdb;
			$id = (int) $wpdb->get_var(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page', 'post')
				 AND ( post_content LIKE '%[bellbook%' OR post_content LIKE '%[simple_booking%' ) ORDER BY post_type = 'page' DESC, ID ASC LIMIT 1"
			);
		}
		return $id ? (string) get_permalink( $id ) : '';
	}

	/**
	 * Whether the customer may still cancel or move this booking.
	 */
	public static function can_change( array $booking ): bool {
		$settings = SB_Settings::get_settings();
		if ( empty( $settings['customer_changes'] ) || ! in_array( $booking['status'], [ 'pending', 'confirmed' ], true ) ) {
			return false;
		}
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $booking['booking_date'] . ' ' . $booking['booking_time'], wp_timezone() );
		return $start && $start->getTimestamp() - time() >= (int) $settings['change_cutoff_hours'] * HOUR_IN_SECONDS;
	}

	/**
	 * Whether a new start time is far enough ahead for the customer to move to (the same
	 * cut-off as for changes, so they can't move into the window where changes stop).
	 */
	public static function allowed_start( string $date, string $time ): bool {
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . substr( $time, 0, 5 ), wp_timezone() );
		return $start && $start->getTimestamp() - time() >= (int) SB_Settings::get_settings()['change_cutoff_hours'] * HOUR_IN_SECONDS;
	}

	/**
	 * When changes stop being possible, for display.
	 */
	public static function deadline( array $booking ): int {
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $booking['booking_date'] . ' ' . $booking['booking_time'], wp_timezone() );
		return $start ? $start->getTimestamp() - (int) SB_Settings::get_settings()['change_cutoff_hours'] * HOUR_IN_SECONDS : 0;
	}
}
