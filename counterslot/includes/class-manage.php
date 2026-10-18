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
class CSlot_Manage {

	public static function token( array $booking ): string {
		return substr( hash_hmac( 'sha256', $booking['id'] . '|' . $booking['booking_code'], wp_salt( 'auth' ) ), 0, 32 );
	}

	/**
	 * The booking for a link's id and token, or null if they don't match.
	 */
	public static function verify( int $id, string $token ): ?array {
		$booking = $id ? ( new CSlot_Bookings() )->get_by_id( $id ) : null;
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
	 * The page set in Settings, or the first published page with the [counterslot] shortcode.
	 * Page builders such as Elementor keep the shortcode in post meta rather than post_content,
	 * so both places are checked; without this, emails silently had no manage link.
	 */
	public static function booking_page_url(): string {
		$id = (int) CSlot_Settings::get_settings()['booking_page_id'];
		if ( ! $id ) {
			global $wpdb;
			$id = (int) $wpdb->get_var(
				"SELECT p.ID FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data'
				 WHERE p.post_status = 'publish' AND p.post_type IN ('page', 'post')
				 AND ( p.post_content LIKE '%[counterslot%' OR p.post_content LIKE '%[simple_booking%'
				       OR m.meta_value LIKE '%[counterslot%' OR m.meta_value LIKE '%[simple_booking%' )
				 ORDER BY p.post_type = 'page' DESC, p.ID ASC LIMIT 1"
			);
		}
		return $id ? (string) get_permalink( $id ) : '';
	}

	/**
	 * Admin warning when customers can change bookings but no booking page can be found, so the
	 * manage links in emails would be missing.
	 */
	public static function page_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || empty( CSlot_Settings::get_settings()['customer_changes'] ) || '' !== self::booking_page_url() ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'CounterSlot can\'t find the page with your booking form, so the "view, cancel or reschedule" links in customer emails are left out.', 'counterslot' ),
			esc_url( admin_url( 'admin.php?page=sb-settings' ) ),
			esc_html__( 'Choose the booking page in Settings', 'counterslot' )
		);
	}

	/**
	 * Whether the customer may still cancel or move this booking.
	 */
	public static function can_change( array $booking ): bool {
		$settings = CSlot_Settings::get_settings();
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
		return $start && $start->getTimestamp() - time() >= (int) CSlot_Settings::get_settings()['change_cutoff_hours'] * HOUR_IN_SECONDS;
	}

	/**
	 * When changes stop being possible, for display.
	 */
	public static function deadline( array $booking ): int {
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $booking['booking_date'] . ' ' . $booking['booking_time'], wp_timezone() );
		return $start ? $start->getTimestamp() - (int) CSlot_Settings::get_settings()['change_cutoff_hours'] * HOUR_IN_SECONDS : 0;
	}
}
