<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSlot_Public_Controller {

	private const DAYS_AHEAD = 14;
	// Per-IP limit via transients. Behind a proxy/CDN, Settings → "Visitor IP comes from" picks the real-IP header.
	private const RATE_LIMIT        = 5;
	private const RATE_LIMIT_WINDOW = 10 * MINUTE_IN_SECONDS;

	public function register_shortcodes(): void {
		add_shortcode( 'counterslot', [ $this, 'render_shortcode' ] );
		add_shortcode( 'counterslot_events', [ $this, 'render_events' ] );
		// Names from before the plugin was renamed, so existing pages keep working.
		add_shortcode( 'simple_booking', [ $this, 'render_shortcode' ] );
		add_shortcode( 'simple_booking_events', [ $this, 'render_events' ] );
	}

	/**
	 * Whether content holds the booking form (or, with $events, the events list), under the
	 * current shortcode name or the one used before the rename.
	 */
	public static function has_form( string $content, bool $events = false ): bool {
		$names = $events ? [ 'counterslot_events', 'simple_booking_events' ] : [ 'counterslot', 'simple_booking' ];
		return has_shortcode( $content, $names[0] ) || has_shortcode( $content, $names[1] );
	}

	public function enqueue_styles_and_scripts(): void {
		$this->register_assets();

		// Load the stylesheet in <head> on pages that use the shortcode, so the form doesn't flash unstyled.
		$post = get_post();
		if ( is_singular() && $post && self::has_form( $post->post_content ) ) {
			wp_enqueue_style( 'cslot-booking-form' );
		}
	}

	/**
	 * Safe to call more than once. Block themes render the shortcode before wp_enqueue_scripts,
	 * and wp_localize_script() silently drops its data for a handle that isn't registered yet.
	 */
	private function register_assets(): void {
		if ( ! wp_script_is( 'cslot-booking-form', 'registered' ) ) {
			wp_register_style( 'cslot-booking-form', CSLOT_PLUGIN_URL . 'public/assets/css/booking-form.css', [], CSLOT_VERSION );
			wp_register_script( 'cslot-booking-form', CSLOT_PLUGIN_URL . 'public/assets/js/booking-form.js', [], CSLOT_VERSION, true );
		}
	}

	/**
	 * Services grouped under their category name, categories A–Z, uncategorized last.
	 * A single '' group means no categories are in use, so the form shows a flat list.
	 *
	 * @return array<string, array[]>
	 */
	private function group_by_category( array $services ): array {
		$names  = array_column( ( new CSlot_Categories() )->get_all(), 'name', 'id' );
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
			$groups[ __( 'Other', 'counterslot' ) ] = $other;
		}
		return $groups;
	}

	/**
	 * The visitor's IP, from the header chosen in Settings. Proxy headers can be forged by anyone
	 * not behind that proxy, so they're only read when the site owner opts in, and only the
	 * first, client-most address of X-Forwarded-For is used.
	 */
	public static function visitor_ip(): string {
		$header = CSlot_Settings::get_settings()['ip_header'];
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
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- standard constant read by caching plugins
		}
		do_action( 'litespeed_control_set_nocache', 'CounterSlot form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache's own action
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
		if ( is_singular() && $post && ( self::has_form( $post->post_content ) || self::has_form( $post->post_content, true ) ) ) {
			$this->prevent_caching();
		}
	}

	public function render_shortcode(): string {
		$this->prevent_caching();

		// Opened from the link in a customer's email.
		if ( isset( $_GET['sb_booking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- the signed token is the check
			return $this->render_manage();
		}

		$services = ( new CSlot_Services() )->get_all();
		if ( ! $services ) {
			return '<p>' . esc_html__( 'Online booking is not available yet.', 'counterslot' ) . '</p>';
		}

		$staff_mgr = new CSlot_Staff();
		$staff     = array_map(
			fn( $member ) => $member + [
				'service_ids' => $staff_mgr->service_ids( $member ),
				'photo_url'   => $staff_mgr->photo_url( $member ),
			],
			$staff_mgr->get_all()
		);

		$this->register_assets();
		wp_enqueue_style( 'cslot-booking-form' );
		wp_enqueue_script( 'cslot-booking-form' );
		wp_localize_script( 'cslot-booking-form', 'cslotBooking', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cslot_public_nonce' ),
			'days'    => $this->upcoming_days(),
			'i18n'    => [
				'error'   => __( 'Something went wrong. Please try again.', 'counterslot' ),
				'loading' => __( 'Loading available times…', 'counterslot' ),
				'noSlots' => __( 'No times left on this day. Please pick another day.', 'counterslot' ),
				'sending' => __( 'Sending your booking…', 'counterslot' ),
			],
		] );

		$locations = ( new CSlot_Locations() )->get_all();

		ob_start();
		cslot_view( 'public/views/booking-form', [
			'groups' => $this->group_by_category( $services ),
			'staff'     => $staff,
			'fields'    => CSlot_Custom_Fields::all(),
			'extras'    => CSlot_Pricing::extras(),
			// Price summary and coupon box only when something costs money.
			'priced'    => (bool) array_filter( $services, fn( $s ) => (float) $s['price'] > 0 ) || CSlot_Pricing::extras(),
			// The location question only appears when there is a choice.
			'locations' => count( $locations ) > 1 ? $locations : [],
		] );
		return (string) ob_get_clean();
	}

	private function render_manage(): string {
		// phpcs:disable WordPress.Security.NonceVerification
		$id      = absint( $_GET['sb_booking'] ?? 0 );
		$token   = sanitize_key( wp_unslash( $_GET['sb_token'] ?? '' ) );
		// phpcs:enable
		$booking = CSlot_Manage::verify( $id, $token );

		wp_enqueue_style( 'cslot-booking-form' );
		if ( $booking && CSlot_Manage::can_change( $booking ) ) {
			wp_enqueue_script( 'cslot-manage', CSLOT_PLUGIN_URL . 'public/assets/js/manage.js', [], CSLOT_VERSION, true );
			wp_localize_script( 'cslot-manage', 'cslotManage', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'id'      => $id,
				'token'   => $token,
				'days'    => $this->upcoming_days(),
				'i18n'    => [
					'error'         => __( 'Something went wrong. Please try again.', 'counterslot' ),
					'loading'       => __( 'Loading available times…', 'counterslot' ),
					'noSlots'       => __( 'No free times on this day. Please pick another.', 'counterslot' ),
					'confirmCancel' => __( 'Cancel this appointment?', 'counterslot' ),
				],
			] );
		}

		ob_start();
		cslot_view( 'public/views/manage', [ 'booking' => $booking ? $this->booking_details( $booking ) : null ] );
		return (string) ob_get_clean();
	}

	/**
	 * Booking row plus the names the manage view shows.
	 */
	private function booking_details( array $booking ): array {
		$service = ( new CSlot_Services() )->get_by_id( (int) $booking['service_id'] );
		$staff    = $booking['staff_id'] ? ( new CSlot_Staff() )->get_by_id( (int) $booking['staff_id'] ) : null;
		$location = ( new CSlot_Locations() )->get_by_id( (int) ( $booking['location_id'] ?? 0 ) );
		return $booking + [
			'service_name' => $service['name'] ?? '',
			'staff_name'   => $staff['name'] ?? '',
			'location'     => $location ? trim( $location['name'] . "\n" . $location['address'] ) : '',
			'can_change'   => CSlot_Manage::can_change( $booking ),
			'deposit'      => (float) ( json_decode( (string) ( $booking['pricing'] ?? '' ), true )['deposit'] ?? 0 ),
			'invoice_url'  => empty( $booking['pricing'] ) ? '' : add_query_arg( [ 'action' => 'cslot_invoice', 'id' => (int) $booking['id'], 'token' => CSlot_Manage::token( $booking ) ], admin_url( 'admin-post.php' ) ),
			'deadline'     => CSlot_Manage::deadline( $booking ),
		];
	}

	/**
	 * The booking a manage request is about, after checking its token and that it can still change.
	 * Wrong tokens count towards the rate limit, so links can't be guessed by brute force.
	 */
	private function manage_guard(): array {
		$post    = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- the signed token is the check
		$booking = CSlot_Manage::verify( absint( $post['id'] ?? 0 ), sanitize_key( $post['token'] ?? '' ) );
		if ( ! $booking ) {
			$this->is_rate_limited()
				? wp_send_json_error( [ 'message' => __( 'Too many attempts. Please wait a few minutes and try again.', 'counterslot' ) ], 429 )
				: wp_send_json_error( [ 'message' => __( 'This link is not valid.', 'counterslot' ) ], 403 );
		}
		if ( ! CSlot_Manage::can_change( $booking ) ) {
			wp_send_json_error( [ 'message' => __( 'This booking can no longer be changed online. Please contact us.', 'counterslot' ) ], 409 );
		}
		return $booking + [ 'post' => $post ];
	}

	public function ajax_manage_slots(): void {
		$booking = $this->manage_guard();
		$date    = sanitize_text_field( $booking['post']['date'] ?? '' );
		$slots   = ( new CSlot_Bookings() )->get_available_slots(
			(int) $booking['service_id'],
			$booking['staff_id'] ? (int) $booking['staff_id'] : null,
			$date,
			(int) $booking['id']
		);
		wp_send_json_success( [ 'slots' => array_values( array_filter( $slots, fn( $t ) => CSlot_Manage::allowed_start( $date, $t ) ) ) ] );
	}

	public function ajax_manage_cancel(): void {
		$booking = $this->manage_guard();
		$result  = ( new CSlot_Bookings() )->update_status( (int) $booking['id'], 'cancelled' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 409 );
		}
		( new CSlot_Email() )->customer_changed( (int) $booking['id'], __( 'The customer cancelled this booking.', 'counterslot' ) );
		wp_send_json_success( [ 'message' => __( 'Your appointment has been cancelled.', 'counterslot' ) ] );
	}

	/**
	 * Move to another free time with the same staff member (or anyone, if none was assigned).
	 */
	public function ajax_manage_reschedule(): void {
		$booking = $this->manage_guard();
		if ( ! CSlot_Manage::allowed_start( sanitize_text_field( $booking['post']['booking_date'] ?? '' ), sanitize_text_field( $booking['post']['booking_time'] ?? '' ) ) ) {
			wp_send_json_error( [ 'code' => 'slot_unavailable', 'message' => __( 'That time is too soon to book online. Please choose a later time.', 'counterslot' ) ], 409 );
		}
		$old     = mysql2date( get_option( 'date_format' ), $booking['booking_date'] ) . ' ' . substr( $booking['booking_time'], 0, 5 );
		$moved   = ( new CSlot_Bookings() )->reschedule(
			(int) $booking['id'],
			sanitize_text_field( $booking['post']['booking_date'] ?? '' ),
			sanitize_text_field( $booking['post']['booking_time'] ?? '' ),
			$booking['staff_id'] ? (int) $booking['staff_id'] : null
		);
		if ( ! $moved ) {
			wp_send_json_error( [ 'code' => 'slot_unavailable', 'message' => __( 'Sorry, that time was just taken. Please choose another time.', 'counterslot' ) ], 409 );
		}
		/* translators: %s: the old date and time */
		( new CSlot_Email() )->customer_changed( (int) $booking['id'], sprintf( __( 'The customer moved this booking from %s to:', 'counterslot' ), $old ) );
		wp_send_json_success( [ 'message' => __( 'Your appointment has been moved. We have emailed you the new details.', 'counterslot' ) ] );
	}

	/**
	 * Printable invoice (admin-post.php?action=cslot_invoice&id=…). Admins get here with a nonce;
	 * customers with their booking's signed token (link on their manage page).
	 */
	public function render_invoice(): void {
		// phpcs:disable WordPress.Security.NonceVerification -- checked below (nonce or signed token)
		$id    = absint( $_GET['id'] ?? 0 );
		$token = sanitize_key( wp_unslash( $_GET['token'] ?? '' ) );
		// phpcs:enable
		$booking = null;
		if ( current_user_can( 'manage_options' ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'cslot_invoice_' . $id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$booking = ( new CSlot_Bookings() )->get_by_id( $id );
		} elseif ( $token ) {
			$booking = CSlot_Manage::verify( $id, $token );
		}
		if ( ! $booking || empty( $booking['pricing'] ) ) {
			wp_die( esc_html__( 'This invoice is not available.', 'counterslot' ), '', [ 'response' => 403 ] );
		}

		nocache_headers();
		$details  = $this->booking_details( $booking );
		$customer = ( new CSlot_Customers() )->get_by_id( (int) $booking['customer_id'] );
		cslot_view( 'public/views/invoice', [
			'booking'  => $details,
			'invoice'  => CSlot_Payments::invoice( $booking ),
			'customer' => $customer,
			'pricing'  => json_decode( (string) $booking['pricing'], true ),
			'payments' => ( new CSlot_Payments() )->for_booking( $id ),
			'settings' => CSlot_Settings::get_settings(),
		] );
		exit;
	}

	/**
	 * [counterslot_events]: upcoming events with places left and a registration form.
	 */
	public function render_events(): string {
		$this->prevent_caching();
		$this->register_assets();
		wp_enqueue_style( 'cslot-booking-form' );
		wp_enqueue_script( 'cslot-events', CSLOT_PLUGIN_URL . 'public/assets/js/events.js', [], CSLOT_VERSION, true );
		wp_localize_script( 'cslot-events', 'cslotEvents', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cslot_public_nonce' ),
			'i18n'    => [
				'error'   => __( 'Something went wrong. Please try again.', 'counterslot' ),
				'sending' => __( 'Sending…', 'counterslot' ),
			],
		] );
		ob_start();
		cslot_view( 'public/views/events', [ 'events' => ( new CSlot_Events() )->get_all( true ) ] );
		return (string) ob_get_clean();
	}

	public function ajax_event_register(): void {
		CSlot_Security::verify_nonce( 'cslot_public_nonce' );
		$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked above; fields are sanitized where used
		if ( ! empty( $post['website'] ) ) { // honeypot
			wp_send_json_error( [ 'message' => __( 'Your registration could not be sent.', 'counterslot' ) ], 400 );
		}
		if ( $this->is_rate_limited() ) {
			wp_send_json_error( [ 'message' => __( 'Too many attempts. Please wait a few minutes and try again.', 'counterslot' ) ], 429 );
		}
		$name  = sanitize_text_field( $post['name'] ?? '' );
		$email = sanitize_email( $post['email'] ?? '' );
		$phone = sanitize_text_field( $post['phone'] ?? '' );
		$spots = min( 20, max( 1, absint( $post['spots'] ?? 1 ) ) );
		if ( '' === $name || mb_strlen( $name ) > 191 || ! is_email( $email ) || mb_strlen( $phone ) > 50 ) {
			wp_send_json_error( [ 'message' => __( 'Please enter your name and a valid email address.', 'counterslot' ) ], 422 );
		}

		$events = new CSlot_Events();
		$event  = $events->get_by_id( absint( $post['event_id'] ?? 0 ) );
		// Check places before saving the customer; register() checks again under the lock.
		$left = $event ? (int) $event['capacity'] - (int) $event['taken'] : 0;
		if ( ! $event || 'active' !== $event['status'] || $left < $spots ) {
			wp_send_json_error( [
				'message' => $left > 0
					/* translators: %d: places left */
					? sprintf( _n( 'Only %d place is left.', 'Only %d places are left.', $left, 'counterslot' ), $left )
					: __( 'Sorry, this event is full.', 'counterslot' ),
			], 409 );
		}
		$customer_id = ( new CSlot_Customers() )->find_or_create( $name, $email, $phone );
		$result      = $customer_id ? $events->register( (int) $event['id'], $customer_id, $spots ) : __( 'Your registration could not be saved. Please try again.', 'counterslot' );
		if ( ! is_int( $result ) ) {
			wp_send_json_error( [ 'message' => $result ], 409 );
		}
		$registration = $events->registration( $result );
		( new CSlot_Email() )->event_registration( $event, $registration, 'event_registered' );
		wp_send_json_success( [
			/* translators: 1: event name, 2: registration code */
			'message' => sprintf( __( 'You\'re registered for %1$s. Your code is %2$s; we\'ve emailed you the details.', 'counterslot' ), $event['name'], $registration['code'] ),
		] );
	}

	/**
	 * Price preview for the form. Public and read-only; it doesn't use up the coupon.
	 */
	public function ajax_quote(): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$q = CSlot_Pricing::quote(
			absint( $_POST['service_id'] ?? 0 ),
			array_map( 'sanitize_key', (array) wp_unslash( $_POST['extras'] ?? [] ) ),
			sanitize_text_field( wp_unslash( $_POST['coupon'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) ) ?: wp_date( 'Y-m-d' )
		);
		// phpcs:enable
		// Wrong codes count towards the rate limit, so coupon codes can't be guessed by trying many.
		if ( $q['coupon_error'] && $this->is_rate_limited() ) {
			wp_send_json_error( [ 'message' => __( 'Too many attempts. Please wait a few minutes and try again.', 'counterslot' ) ], 429 );
		}
		wp_send_json_success( [
			'lines'       => array_map( fn( $l ) => [ $l[0], cslot_price( $l[1] ) ], CSlot_Pricing::lines( $q ) ),
			'couponError' => $q['coupon_error'],
			'total'       => $q['total'],
		] );
	}

	/**
	 * Read-only and public, so no nonce: it only reveals which times are free.
	 */
	public function ajax_get_available_slots(): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$service_id = absint( $_POST['service_id'] ?? 0 );
		$staff_id   = absint( $_POST['staff_id'] ?? 0 ) ?: null;
		$date       = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
		$location   = absint( $_POST['location_id'] ?? 0 ) ?: null;
		// phpcs:enable

		wp_send_json_success( [
			'slots' => ( new CSlot_Bookings() )->get_available_slots( $service_id, $staff_id, $date, null, $location ),
		] );
	}

	public function ajax_submit_booking(): void {
		CSlot_Security::verify_nonce( 'cslot_public_nonce' );
		$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked above; fields are sanitized where used

		// Honeypot: real visitors never see or fill this field.
		if ( ! empty( $post['website'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Your booking could not be sent.', 'counterslot' ) ], 400 );
		}
		if ( $this->is_rate_limited() ) {
			wp_send_json_error( [ 'message' => __( 'Too many booking attempts. Please wait a few minutes and try again.', 'counterslot' ) ], 429 );
		}

		$data = CSlot_Validator::booking_request( $post );
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( [ 'message' => $data->get_error_message() ], 422 );
		}

		$answers = CSlot_Custom_Fields::answers( $post, $data['service_id'] );
		if ( is_wp_error( $answers ) ) {
			wp_send_json_error( [ 'message' => $answers->get_error_message() ], 422 );
		}
		$data['custom_fields'] = $answers;

		// Price is always worked out here; the form's summary is only a preview.
		$quote = CSlot_Pricing::quote( $data['service_id'], (array) ( $post['extras'] ?? [] ), sanitize_text_field( $post['coupon'] ?? '' ), $data['booking_date'] );
		if ( $quote['coupon_error'] ) {
			wp_send_json_error( [ 'message' => $quote['coupon_error'] ], 422 );
		}
		$data['pricing'] = CSlot_Pricing::to_store( $quote );

		$bookings = new CSlot_Bookings();
		$taken    = static fn() => wp_send_json_error( [
			'code'    => 'slot_unavailable',
			'message' => __( 'Sorry, that time was just taken. Please choose another time.', 'counterslot' ),
		], 409 );

		// Check the time before saving the customer, so rejected requests don't leave customer records.
		// create_booking() checks again under the lock.
		if ( ! in_array( $data['booking_time'], $bookings->get_available_slots( $data['service_id'], $data['staff_id'] ?: null, $data['booking_date'], null, $data['location_id'] ?: null ), true ) ) {
			$taken();
		}

		$customer_id = ( new CSlot_Customers() )->find_or_create( $data['name'], $data['email'], $data['phone'] );
		if ( ! $customer_id ) {
			wp_send_json_error( [ 'message' => __( 'Your booking could not be saved. Please try again.', 'counterslot' ) ], 500 );
		}

		// Count the coupon use first (atomically), and give it back if the booking fails.
		if ( $quote['coupon'] && ! CSlot_Pricing::redeem( (int) $quote['coupon']['id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'This coupon has been used up.', 'counterslot' ) ], 422 );
		}
		$booking_id = $bookings->create_booking( [ 'customer_id' => $customer_id ] + $data );
		if ( ! $booking_id ) {
			if ( $quote['coupon'] ) {
				CSlot_Pricing::unredeem( (int) $quote['coupon']['id'] );
			}
			$taken();
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: booking reference code */
				__( 'Thank you! Your booking request %s has been received. We will be in touch to confirm it.', 'counterslot' ),
				$bookings->get_code( $booking_id )
			),
		] );
	}

	/**
	 * Next DAYS_AHEAD days in the site timezone, flagged open/closed from the working days.
	 */
	private function upcoming_days(): array {
		$staff_mgr = new CSlot_Staff();
		$staff     = $staff_mgr->get_all();
		$day       = new DateTimeImmutable( 'today', wp_timezone() );
		$days      = [];

		// Open if anyone works that day (or, with no staff, the business is open). Per-service
		// and per-staff detail is left to the times list.
		$open = static fn( DateTimeImmutable $d ) => $staff
			? (bool) array_filter( $staff, fn( $m ) => null !== $staff_mgr->hours_on( $m, $d->format( 'Y-m-d' ) ) )
			: null !== CSlot_Staff::business_hours_on( $d->format( 'l' ) );

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
		$key  = 'cslot_rl_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $hits + 1, self::RATE_LIMIT_WINDOW );
		return false;
	}
}
