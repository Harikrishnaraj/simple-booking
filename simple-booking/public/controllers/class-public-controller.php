<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Public_Controller {

	private const DAYS_AHEAD = 14;
	// Per-IP limit via transients. Behind a proxy/CDN, Settings → "Visitor IP comes from" picks the real-IP header.
	private const RATE_LIMIT        = 5;
	private const RATE_LIMIT_WINDOW = 10 * MINUTE_IN_SECONDS;

	public function register_shortcodes(): void {
		add_shortcode( 'simple_booking', [ $this, 'render_shortcode' ] );
	}

	public function enqueue_styles_and_scripts(): void {
		$this->register_assets();

		// Load the stylesheet in <head> on pages that use the shortcode, so the form doesn't flash unstyled.
		$post = get_post();
		if ( is_singular() && $post && has_shortcode( $post->post_content, 'simple_booking' ) ) {
			wp_enqueue_style( 'sb-booking-form' );
		}
	}

	/**
	 * Safe to call more than once. Block themes render the shortcode before wp_enqueue_scripts,
	 * and wp_localize_script() silently drops its data for a handle that isn't registered yet.
	 */
	private function register_assets(): void {
		if ( ! wp_script_is( 'sb-booking-form', 'registered' ) ) {
			wp_register_style( 'sb-booking-form', SB_PLUGIN_URL . 'public/assets/css/booking-form.css', [], SB_VERSION );
			wp_register_script( 'sb-booking-form', SB_PLUGIN_URL . 'public/assets/js/booking-form.js', [], SB_VERSION, true );
		}
	}

	/**
	 * Services grouped under their category name, categories A–Z, uncategorized last.
	 * A single '' group means no categories are in use, so the form shows a flat list.
	 *
	 * @return array<string, array[]>
	 */
	private function group_by_category( array $services ): array {
		$names  = array_column( ( new SB_Categories() )->get_all(), 'name', 'id' );
		$groups = [];
		foreach ( $names as $name ) {
			$groups[ $name ] = [];
		}
		$other = [];
		foreach ( $services as $s ) {
			if ( isset( $names[ $s['category_id'] ?? 0 ] ) ) {
				$groups[ $names[ $s['category_id'] ] ][] = $s;
			} else {
				$other[] = $s;
			}
		}
		$groups = array_filter( $groups );
		if ( ! $groups ) {
			return [ '' => $other ];
		}
		if ( $other ) {
			$groups[ __( 'Other', 'simple-booking' ) ] = $other;
		}
		return $groups;
	}

	/**
	 * The visitor's IP, from the header chosen in Settings. Proxy headers can be forged by anyone
	 * not behind that proxy, so they're only read when the site owner opts in, and only the
	 * first, client-most address of X-Forwarded-For is used.
	 */
	public static function visitor_ip(): string {
		$header = SB_Settings::get_settings()['ip_header'];
		$value  = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ?? '' ) );
		$ip     = trim( explode( ',', $value )[0] );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		}
		return $ip;
	}

	/**
	 * Booking pages carry a security token and today's list of days, so page caches must not
	 * store them. DONOTCACHEPAGE is honoured by WP Super Cache, W3 Total Cache, WP Rocket and
	 * others; LiteSpeed has its own action.
	 */
	public function prevent_caching(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'Simple Booking form' );
		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}

	/**
	 * template_redirect: runs before any output, so the no-cache headers can still be sent.
	 * Forms placed by page builders outside post_content are covered by render_shortcode().
	 */
	public function maybe_prevent_caching(): void {
		$post = get_post();
		if ( is_singular() && $post && has_shortcode( $post->post_content, 'simple_booking' ) ) {
			$this->prevent_caching();
		}
	}

	public function render_shortcode(): string {
		$this->prevent_caching();
		$services = ( new SB_Services() )->get_all();
		if ( ! $services ) {
			return '<p>' . esc_html__( 'Online booking is not available yet.', 'simple-booking' ) . '</p>';
		}

		$staff_mgr = new SB_Staff();
		$staff     = array_map(
			fn( $member ) => $member + [
				'service_ids' => $staff_mgr->service_ids( $member ),
				'photo_url'   => $staff_mgr->photo_url( $member ),
			],
			$staff_mgr->get_all()
		);

		$this->register_assets();
		wp_enqueue_style( 'sb-booking-form' );
		wp_enqueue_script( 'sb-booking-form' );
		wp_localize_script( 'sb-booking-form', 'sbBooking', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'sb_public_nonce' ),
			'days'    => $this->upcoming_days(),
			'i18n'    => [
				'error'   => __( 'Something went wrong. Please try again.', 'simple-booking' ),
				'loading' => __( 'Loading available times…', 'simple-booking' ),
				'noSlots' => __( 'No times left on this day. Please pick another day.', 'simple-booking' ),
				'sending' => __( 'Sending your booking…', 'simple-booking' ),
			],
		] );

		ob_start();
		sb_view( 'public/views/booking-form', [ 'groups' => $this->group_by_category( $services ), 'staff' => $staff ] );
		return (string) ob_get_clean();
	}

	/**
	 * Read-only and public, so no nonce: it only reveals which times are free.
	 */
	public function ajax_get_available_slots(): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$service_id = absint( $_POST['service_id'] ?? 0 );
		$staff_id   = absint( $_POST['staff_id'] ?? 0 ) ?: null;
		$date       = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
		// phpcs:enable

		wp_send_json_success( [
			'slots' => ( new SB_Bookings() )->get_available_slots( $service_id, $staff_id, $date ),
		] );
	}

	public function ajax_submit_booking(): void {
		SB_Security::verify_nonce( 'sb_public_nonce' );
		$post = wp_unslash( $_POST );

		// Honeypot: real visitors never see or fill this field.
		if ( ! empty( $post['website'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Your booking could not be sent.', 'simple-booking' ) ], 400 );
		}
		if ( $this->is_rate_limited() ) {
			wp_send_json_error( [ 'message' => __( 'Too many booking attempts. Please wait a few minutes and try again.', 'simple-booking' ) ], 429 );
		}

		$data = SB_Validator::booking_request( $post );
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( [ 'message' => $data->get_error_message() ], 422 );
		}

		$bookings = new SB_Bookings();
		$taken    = static fn() => wp_send_json_error( [
			'code'    => 'slot_unavailable',
			'message' => __( 'Sorry, that time was just taken. Please choose another time.', 'simple-booking' ),
		], 409 );

		// Check the time before saving the customer, so rejected requests don't leave customer records.
		// create_booking() checks again under the lock.
		if ( ! in_array( $data['booking_time'], $bookings->get_available_slots( $data['service_id'], $data['staff_id'] ?: null, $data['booking_date'] ), true ) ) {
			$taken();
		}

		$customer_id = ( new SB_Customers() )->find_or_create( $data['name'], $data['email'], $data['phone'] );
		if ( ! $customer_id ) {
			wp_send_json_error( [ 'message' => __( 'Your booking could not be saved. Please try again.', 'simple-booking' ) ], 500 );
		}

		$booking_id = $bookings->create_booking( [ 'customer_id' => $customer_id ] + $data );
		if ( ! $booking_id ) {
			$taken();
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: booking reference code */
				__( 'Thank you! Your booking request %s has been received. We will be in touch to confirm it.', 'simple-booking' ),
				$bookings->get_code( $booking_id )
			),
		] );
	}

	/**
	 * Next DAYS_AHEAD days in the site timezone, flagged open/closed from the working days.
	 */
	private function upcoming_days(): array {
		$staff_mgr = new SB_Staff();
		$staff     = $staff_mgr->get_all();
		$day       = new DateTimeImmutable( 'today', wp_timezone() );
		$days      = [];

		// Open if anyone works that day (or, with no staff, the business is open). Per-service
		// and per-staff detail is left to the times list.
		$open = static fn( DateTimeImmutable $d ) => $staff
			? (bool) array_filter( $staff, fn( $m ) => null !== $staff_mgr->hours_on( $m, $d->format( 'Y-m-d' ) ) )
			: null !== SB_Staff::business_hours_on( $d->format( 'l' ) );

		for ( $i = 0; $i < self::DAYS_AHEAD; $i++, $day = $day->modify( '+1 day' ) ) {
			$ts     = $day->getTimestamp();
			$days[] = [
				'date'  => $day->format( 'Y-m-d' ),
				'week'  => wp_date( 'D', $ts ),
				'day'   => wp_date( 'j', $ts ),
				'month' => wp_date( 'M', $ts ),
				'label' => wp_date( get_option( 'date_format' ), $ts ),
				'open'  => $open( $day ),
			];
		}
		return $days;
	}

	private function is_rate_limited(): bool {
		$ip   = self::visitor_ip();
		$key  = 'sb_rl_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $hits + 1, self::RATE_LIMIT_WINDOW );
		return false;
	}
}
