<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Admin_Controller {

	private const PER_PAGE = 50;

	private const THEME_META = 'sb_admin_theme';

	public function register_admin_menu(): void {
		$cap = 'manage_options';
		add_menu_page( __( 'Simple Booking', 'simple-booking' ), __( 'Simple Booking', 'simple-booking' ), $cap, 'sb-dashboard', [ $this, 'render_dashboard' ], 'dashicons-calendar-alt', 26 );
		add_submenu_page( 'sb-dashboard', __( 'Dashboard', 'simple-booking' ), __( 'Dashboard', 'simple-booking' ), $cap, 'sb-dashboard', [ $this, 'render_dashboard' ] );
		add_submenu_page( 'sb-dashboard', __( 'Calendar', 'simple-booking' ), __( 'Calendar', 'simple-booking' ), $cap, 'sb-calendar', [ $this, 'render_calendar' ] );
		add_submenu_page( 'sb-dashboard', __( 'Bookings', 'simple-booking' ), __( 'Bookings', 'simple-booking' ), $cap, 'sb-bookings', [ $this, 'render_bookings' ] );
		add_submenu_page( 'sb-dashboard', __( 'Finance', 'simple-booking' ), __( 'Finance', 'simple-booking' ), $cap, 'sb-finance', [ $this, 'render_finance' ] );
		add_submenu_page( 'sb-dashboard', __( 'Events', 'simple-booking' ), __( 'Events', 'simple-booking' ), $cap, 'sb-events', [ $this, 'render_events' ] );
		add_submenu_page( 'sb-dashboard', __( 'Customers', 'simple-booking' ), __( 'Customers', 'simple-booking' ), $cap, 'sb-customers', [ $this, 'render_customers' ] );
		add_submenu_page( 'sb-dashboard', __( 'Services', 'simple-booking' ), __( 'Services', 'simple-booking' ), $cap, 'sb-services', [ $this, 'render_services' ] );
		add_submenu_page( 'sb-dashboard', __( 'Locations', 'simple-booking' ), __( 'Locations', 'simple-booking' ), $cap, 'sb-locations', [ $this, 'render_locations' ] );
		add_submenu_page( 'sb-dashboard', __( 'Staff', 'simple-booking' ), __( 'Staff', 'simple-booking' ), $cap, 'sb-staff', [ $this, 'render_staff' ] );
		add_submenu_page( 'sb-dashboard', __( 'Pricing', 'simple-booking' ), __( 'Pricing', 'simple-booking' ), $cap, 'sb-pricing', [ $this, 'render_pricing' ] );
		add_submenu_page( 'sb-dashboard', __( 'Custom Fields', 'simple-booking' ), __( 'Custom Fields', 'simple-booking' ), $cap, 'sb-custom-fields', [ $this, 'render_custom_fields' ] );
		add_submenu_page( 'sb-dashboard', __( 'Notifications', 'simple-booking' ), __( 'Notifications', 'simple-booking' ), $cap, 'sb-notifications', [ $this, 'render_notifications' ] );
		add_submenu_page( 'sb-dashboard', __( 'Settings', 'simple-booking' ), __( 'Settings', 'simple-booking' ), $cap, 'sb-settings', [ $this, 'render_settings' ] );
	}

	public function enqueue_styles_and_scripts( string $hook ): void {
		if ( ! $this->is_plugin_screen() ) {
			return;
		}
		wp_enqueue_style( 'sb-admin', SB_PLUGIN_URL . 'admin/assets/css/admin.css', [], SB_VERSION );
		if ( 'sb-staff' === sanitize_key( $_GET['page'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_media(); // staff photo picker
		}
		wp_enqueue_script( 'sb-admin', SB_PLUGIN_URL . 'admin/assets/js/admin.js', [], SB_VERSION, true );
		wp_localize_script( 'sb-admin', 'sbAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'sb_admin_nonce' ),
			'i18n'    => [
				'error'         => __( 'Something went wrong. Please try again.', 'simple-booking' ),
				'confirmDelete' => __( 'Delete this item? Items with bookings are deactivated instead.', 'simple-booking' ),
				'confirmDeleteCategory' => __( 'Delete this category? Its services are kept and become uncategorized.', 'simple-booking' ),
				'choosePhoto'   => __( 'Choose a photo', 'simple-booking' ),
				'usePhoto'      => __( 'Use this photo', 'simple-booking' ),
				'saved'         => __( 'Saved.', 'simple-booking' ),
				'pickDate'      => __( 'Pick a date first', 'simple-booking' ),
				'delete'        => __( 'Delete', 'simple-booking' ),
				'confirmDeletePayment' => __( 'Delete this payment record?', 'simple-booking' ),
				'cancel'               => __( 'Cancel', 'simple-booking' ),
				'cancelled'            => __( 'Cancelled', 'simple-booking' ),
				'confirmCancelRegistration' => __( 'Cancel this registration? The person is emailed.', 'simple-booking' ),
				'loadingTimes'  => __( 'Loading free times…', 'simple-booking' ),
				'noTimes'       => __( 'No free times on this day', 'simple-booking' ),
				'testSent'      => __( 'Test email sent to %s.', 'simple-booking' ),
			],
		] );
	}

	/**
	 * Dark or light theme on plugin pages, remembered per user. Dark by default.
	 */
	public function admin_body_class( string $classes ): string {
		if ( ! $this->is_plugin_screen() ) {
			return $classes;
		}
		return $classes . ' sb-admin-screen sb-theme-' . $this->theme();
	}

	private function theme(): string {
		return 'light' === get_user_meta( get_current_user_id(), self::THEME_META, true ) ? 'light' : 'dark';
	}

	private function is_plugin_screen(): bool {
		$page = sanitize_key( $_GET['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		return str_starts_with( $page, 'sb-' );
	}

	/* ---------- Pages ---------- */

	public function render_dashboard(): void {
		$month_start = wp_date( 'Y-m-01' );
		$from        = $this->date_param( 'from', $month_start );
		$to          = $this->date_param( 'to', wp_date( 'Y-m-t' ) );
		if ( $to < $from ) {
			[ $from, $to ] = [ $to, $from ];
		}
		$reports = new SB_Reports();
		sb_view( 'admin/views/dashboard', [
			'from'     => $from,
			'to'       => $to,
			'now'      => $reports->summary( $from, $to ),
			'before'   => $reports->summary( ...SB_Reports::previous_range( $from, $to ) ),
			'daily'    => $reports->daily_counts( $from, $to ),
			'upcoming' => $reports->upcoming(),
			'statuses' => $this->status_labels(),
			'theme'    => $this->theme(),
		] );
	}

	public function render_calendar(): void {
		$month = sanitize_text_field( wp_unslash( $_GET['month'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$first = DateTimeImmutable::createFromFormat( '!Y-m-d', $month . '-01', wp_timezone() );
		if ( ! $first || $first->format( 'Y-m' ) !== $month ) {
			$first = new DateTimeImmutable( wp_date( 'Y-m-01' ), wp_timezone() );
		}

		// Grid runs from the week containing the 1st to the week containing the last day.
		$week_start = (int) get_option( 'start_of_week', 1 );
		$grid_start = $first->modify( '-' . ( ( (int) $first->format( 'w' ) - $week_start + 7 ) % 7 ) . ' days' );
		$last       = $first->modify( 'last day of this month' );
		$grid_end   = $last->modify( '+' . ( ( $week_start + 6 - (int) $last->format( 'w' ) ) % 7 ) . ' days' );

		$staff_id = absint( $_GET['staff'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		sb_view( 'admin/views/calendar', [
			'first'      => $first,
			'grid_start' => $grid_start,
			'grid_end'   => $grid_end,
			'week_start' => $week_start,
			'staff_id'   => $staff_id,
			'staff'      => ( new SB_Staff() )->get_all( 'all' ),
			'bookings'   => ( new SB_Reports() )->bookings_by_day( $grid_start->format( 'Y-m-d' ), $grid_end->format( 'Y-m-d' ), $staff_id ?: null ),
			// Events by day; with a staff filter, only events they host.
			'events'     => array_reduce(
				array_filter( ( new SB_Events() )->on( $grid_start->format( 'Y-m-d' ), $grid_end->format( 'Y-m-d' ) ), fn( $e ) => ! $staff_id || (int) $e['staff_id'] === $staff_id ),
				function ( $by_day, $e ) {
					$by_day[ $e['event_date'] ][] = $e;
					return $by_day;
				},
				[]
			),
			'work_days'  => (array) SB_Settings::get_settings()['work_days'],
			'page_url'   => admin_url( 'admin.php?page=sb-calendar' ),
			'theme'      => $this->theme(),
		] );
	}

	public function render_bookings(): void {
		// phpcs:disable WordPress.Security.NonceVerification -- read-only filters
		$status  = sanitize_key( $_GET['status'] ?? '' );
		$filters = [
			'search'    => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'status'    => isset( $this->status_labels()[ $status ] ) ? $status : '',
			'staff_id'  => absint( $_GET['staff'] ?? 0 ),
			'date_from' => $this->date_param( 'from', '' ),
			'date_to'   => $this->date_param( 'to', '' ),
		];
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		// phpcs:enable

		$bookings = new SB_Bookings();
		$total    = $bookings->count( $filters );
		$list     = $bookings->get_list( $page, self::PER_PAGE, $filters );
		$payments = [];
		foreach ( $list as $b ) {
			$payments[ $b['id'] ] = [];
		}
		if ( $payments ) {
			global $wpdb;
			foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_payments WHERE booking_id IN (" . implode( ',', array_map( 'intval', array_keys( $payments ) ) ) . ') ORDER BY paid_at, id', ARRAY_A ) as $p ) {
				$payments[ $p['booking_id'] ][] = $p;
			}
		}
		sb_view( 'admin/views/bookings', [
			'bookings' => $list,
			'payments' => $payments,
			'filters'  => $filters,
			'total'    => $total,
			'page'     => $page,
			'pages'    => (int) ceil( $total / self::PER_PAGE ),
			'statuses' => $this->status_labels(),
			'staff'    => ( new SB_Staff() )->get_all( 'all' ),
			'theme'    => $this->theme(),
		] );

		// Add-booking and reschedule dialogs: active services and staff, and existing customers to pick from.
		$staff_mgr = new SB_Staff();
		sb_view( 'admin/views/partials/booking-dialogs', [
			'services'  => ( new SB_Services() )->get_all(),
			'staff'     => array_map( fn( $m ) => $m + [ 'service_ids' => $staff_mgr->service_ids( $m ) ], $staff_mgr->get_all() ),
			'customers' => ( new SB_Customers() )->get_list( '', 1, 500 ),
			'fields'    => SB_Custom_Fields::all(),
			'extras'    => SB_Pricing::extras(),
		] );
	}

	public function render_customers(): void {
		$search    = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$page      = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$customers = new SB_Customers();
		sb_view( 'admin/views/customers', [
			'customers' => $customers->get_list( $search, $page, self::PER_PAGE ),
			'search'    => $search,
			'page'      => $page,
			'pages'     => (int) ceil( $customers->count( $search ) / self::PER_PAGE ),
			'theme'     => $this->theme(),
		] );
	}

	public function render_services(): void {
		$services = new SB_Services();
		$edit_id  = absint( $_GET['edit'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$all      = $services->get_all( 'all' );

		// ?category=ID shows one category, ?category=none the uncategorized services.
		$category = sanitize_key( $_GET['category'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$category = 'none' === $category ? 'none' : absint( $category );
		$shown    = ! $category ? $all : array_filter(
			$all,
			fn( $s ) => 'none' === $category ? empty( $s['category_id'] ) : (int) $s['category_id'] === $category
		);

		sb_view( 'admin/views/services', [
			'services'      => $shown,
			'total'         => count( $all ),
			'uncategorized' => count( array_filter( $all, fn( $s ) => empty( $s['category_id'] ) ) ),
			'categories'    => ( new SB_Categories() )->get_all(),
			'category'      => $category,
			'editing'       => $edit_id ? $services->get_by_id( $edit_id ) : null,
			'page_url'      => admin_url( 'admin.php?page=sb-services' ),
			'theme'         => $this->theme(),
		] );
	}

	public function render_staff(): void {
		$staff   = new SB_Staff();
		$edit_id = absint( $_GET['edit'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$editing = $edit_id ? $staff->get_by_id( $edit_id ) : null;
		sb_view( 'admin/views/staff', [
			'staff'        => $staff->get_all( 'all' ),
			'editing'      => $editing,
			'editing_ids'  => $editing ? $staff->service_ids( $editing ) : [],
			'all_services' => ( new SB_Services() )->get_all( 'all' ),
			'locations'    => ( new SB_Locations() )->get_all( 'all' ),
			'page_url'     => admin_url( 'admin.php?page=sb-staff' ),
			'theme'        => $this->theme(),
		] );
	}

	public function render_locations(): void {
		global $wpdb;
		$locations = new SB_Locations();
		sb_view( 'admin/views/locations', [
			'locations'   => $locations->get_all( 'all' ),
			'staff_count' => array_column( $wpdb->get_results( "SELECT location_id, COUNT(*) AS n FROM {$wpdb->prefix}sb_staff WHERE location_id IS NOT NULL GROUP BY location_id", ARRAY_A ), 'n', 'location_id' ),
			'theme'       => $this->theme(),
		] );
	}

	public function ajax_save_location(): void {
		$post  = $this->guard();
		$error = ( new SB_Locations() )->save( absint( $post['id'] ?? 0 ), $post );
		'' === $error ? wp_send_json_success() : wp_send_json_error( [ 'message' => $error ], 422 );
	}

	public function ajax_delete_location(): void {
		$post      = $this->guard();
		$id        = absint( $post['id'] ?? 0 );
		$locations = new SB_Locations();
		if ( ! $locations->delete( $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not delete the location.', 'simple-booking' ) ], 500 );
		}
		wp_send_json_success( [
			'message' => $locations->get_by_id( $id ) ? __( 'This location has bookings, so it was deactivated instead of deleted.', 'simple-booking' ) : '',
		] );
	}

	public function render_events(): void {
		$events    = new SB_Events();
		$list      = $events->get_all();
		$attendees = [];
		foreach ( $list as $e ) {
			$attendees[ $e['id'] ] = $events->registrations( (int) $e['id'] );
		}
		sb_view( 'admin/views/events', [
			'events'    => $list,
			'attendees' => $attendees,
			'locations' => ( new SB_Locations() )->get_all(),
			'staff'     => ( new SB_Staff() )->get_all(),
			'theme'     => $this->theme(),
		] );
	}

	public function ajax_save_event(): void {
		$post  = $this->guard();
		$error = ( new SB_Events() )->save( absint( $post['id'] ?? 0 ), $post );
		'' === $error ? wp_send_json_success() : wp_send_json_error( [ 'message' => $error ], 422 );
	}

	public function ajax_cancel_event(): void {
		$id     = absint( $this->guard()['id'] ?? 0 );
		$events = new SB_Events();
		$event  = $events->get_by_id( $id );
		$mailer = new SB_Email();
		foreach ( $event ? $events->cancel( $id ) : [] as $registration ) {
			$mailer->event_registration( $event, $registration, 'event_cancelled' );
		}
		wp_send_json_success();
	}

	public function ajax_cancel_registration(): void {
		$id           = absint( $this->guard()['id'] ?? 0 );
		$events       = new SB_Events();
		$registration = $events->registration( $id );
		if ( ! $registration || ! $events->cancel_registration( $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not cancel the registration.', 'simple-booking' ) ], 409 );
		}
		( new SB_Email() )->event_registration( (array) $events->get_by_id( (int) $registration['event_id'] ), $registration, 'event_cancelled' );
		wp_send_json_success();
	}

	public function render_finance(): void {
		$tab  = 'unpaid' === sanitize_key( $_GET['tab'] ?? '' ) ? 'unpaid' : 'payments'; // phpcs:ignore WordPress.Security.NonceVerification
		$from = $this->date_param( 'from', wp_date( 'Y-m-01' ) );
		$to   = $this->date_param( 'to', wp_date( 'Y-m-t' ) );
		$pay  = new SB_Payments();
		sb_view( 'admin/views/finance', [
			'tab'         => $tab,
			'from'        => $from,
			'to'          => $to,
			'payments'    => 'payments' === $tab ? $pay->between( $from, $to ) : [],
			'outstanding' => 'unpaid' === $tab ? $pay->outstanding() : [],
			'theme'       => $this->theme(),
		] );
	}

	public function ajax_add_payment(): void {
		$post  = $this->guard();
		$error = ( new SB_Payments() )->add( absint( $post['id'] ?? 0 ), $post );
		'' === $error ? wp_send_json_success() : wp_send_json_error( [ 'message' => $error ], 422 );
	}

	public function ajax_delete_payment(): void {
		( new SB_Payments() )->delete( absint( $this->guard()['id'] ?? 0 ) );
		wp_send_json_success();
	}

	/**
	 * admin-post: download payments in a date range as CSV.
	 */
	public function export_payments(): void {
		SB_Security::check_admin_permission();
		check_admin_referer( 'sb_export_payments' );
		$from    = $this->date_param( 'from', wp_date( 'Y-m-01' ) );
		$to      = $this->date_param( 'to', wp_date( 'Y-m-t' ) );
		$methods = SB_Payments::methods();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="payments-' . $from . '-to-' . $to . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM so Excel reads UTF-8 (₹)
		fputcsv( $out, [ 'Date', 'Amount', 'Method', 'Booking', 'Appointment', 'Customer', 'Service', 'Note' ] );
		foreach ( ( new SB_Payments() )->between( $from, $to ) as $p ) {
			// A leading = + - @ would be run as a formula by spreadsheet apps.
			$cell = static fn( $v ) => preg_match( '/^[=+\-@\t\r]/', (string) $v ) ? "'" . $v : (string) $v;
			fputcsv( $out, [ $p['paid_at'], number_format( (float) $p['amount'], 2, '.', '' ), $methods[ $p['method'] ] ?? $p['method'], $p['booking_code'], $p['booking_date'], $cell( $p['customer_name'] ), $cell( $p['service_name'] ), $cell( $p['note'] ) ] );
		}
		fclose( $out );
		exit;
	}

	public function render_pricing(): void {
		$tab = sanitize_key( $_GET['tab'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		sb_view( 'admin/views/pricing', [
			'tab'      => in_array( $tab, [ 'extras', 'coupons', 'tax' ], true ) ? $tab : 'extras',
			'extras'   => SB_Pricing::extras(),
			'coupons'  => SB_Pricing::coupons(),
			'services' => ( new SB_Services() )->get_all( 'all' ),
			'settings' => SB_Settings::get_settings(),
			'theme'    => $this->theme(),
		] );
	}

	public function ajax_save_extra(): void {
		$error = SB_Pricing::save_extra( $this->guard() );
		'' === $error ? wp_send_json_success() : wp_send_json_error( [ 'message' => $error ], 422 );
	}

	public function ajax_delete_extra(): void {
		SB_Pricing::delete_extra( sanitize_key( $this->guard()['id'] ?? '' ) );
		wp_send_json_success();
	}

	public function ajax_save_coupon(): void {
		$post  = $this->guard();
		$error = SB_Pricing::save_coupon( absint( $post['id'] ?? 0 ), $post );
		'' === $error ? wp_send_json_success() : wp_send_json_error( [ 'message' => $error ], 422 );
	}

	public function ajax_delete_coupon(): void {
		SB_Pricing::delete_coupon( absint( $this->guard()['id'] ?? 0 ) );
		wp_send_json_success();
	}

	public function render_custom_fields(): void {
		sb_view( 'admin/views/custom-fields', [
			'fields'   => SB_Custom_Fields::all(),
			'services' => ( new SB_Services() )->get_all( 'all' ),
			'theme'    => $this->theme(),
		] );
	}

	public function render_notifications(): void {
		$types   = SB_Notifications::types();
		$current = sanitize_key( $_GET['email'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		sb_view( 'admin/views/notifications', [
			'types'     => $types,
			'templates' => SB_Notifications::get_all(),
			'current'   => isset( $types[ $current ] ) ? $current : array_key_first( $types ),
			'next_run'  => wp_next_scheduled( SB_Email::REMINDER_HOOK ),
			'page_url'  => admin_url( 'admin.php?page=sb-notifications' ),
			'theme'     => $this->theme(),
		] );
	}

	public function render_settings(): void {
		sb_view( 'admin/views/settings', [ 'settings' => SB_Settings::get_settings(), 'theme' => $this->theme() ] );
	}

	/* ---------- AJAX ---------- */

	public function ajax_save_service(): void {
		$post     = $this->guard();
		$services = new SB_Services();
		$id       = absint( $post['id'] ?? 0 );
		$ok       = $id ? $services->update( $id, $post ) : $services->create( $post );
		$ok ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'Please enter a name and a duration of at least 1 minute.', 'simple-booking' ) ], 422 );
	}

	public function ajax_delete_service(): void {
		$post     = $this->guard();
		$services = new SB_Services();
		$id       = absint( $post['id'] ?? 0 );
		if ( ! $services->delete( $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not delete the service.', 'simple-booking' ) ], 500 );
		}
		wp_send_json_success( [
			'message' => $services->get_by_id( $id ) ? __( 'This service has bookings, so it was deactivated instead of deleted.', 'simple-booking' ) : '',
		] );
	}

	public function ajax_save_staff(): void {
		$post  = $this->guard();
		$staff = new SB_Staff();
		$id    = absint( $post['id'] ?? 0 );
		$ok    = $id ? $staff->update( $id, $post ) : $staff->create( $post );
		$ok ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'Please enter a name and a valid email address.', 'simple-booking' ) ], 422 );
	}

	public function ajax_delete_staff(): void {
		$post  = $this->guard();
		$staff = new SB_Staff();
		$id    = absint( $post['id'] ?? 0 );
		if ( ! $staff->delete( $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not delete the staff member.', 'simple-booking' ) ], 500 );
		}
		wp_send_json_success( [
			'message' => $staff->get_by_id( $id ) ? __( 'This staff member has bookings, so they were deactivated instead of deleted.', 'simple-booking' ) : '',
		] );
	}

	public function ajax_update_booking_status(): void {
		$post   = $this->guard();
		$result = ( new SB_Bookings() )->update_status( absint( $post['id'] ?? 0 ), sanitize_key( $post['status'] ?? '' ) );
		is_wp_error( $result ) ? wp_send_json_error( [ 'message' => $result->get_error_message() ], 409 ) : wp_send_json_success();
	}

	/**
	 * Free start times for the admin booking and reschedule dialogs.
	 */
	public function ajax_admin_slots(): void {
		$post = $this->guard();
		wp_send_json_success( [
			'slots' => ( new SB_Bookings() )->get_available_slots(
				absint( $post['service_id'] ?? 0 ),
				absint( $post['staff_id'] ?? 0 ) ?: null,
				sanitize_text_field( $post['date'] ?? '' ),
				absint( $post['exclude'] ?? 0 ) ?: null
			),
		] );
	}

	public function ajax_admin_create_booking(): void {
		$post = $this->guard();
		$data = SB_Validator::booking_request( $post );
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( [ 'message' => $data->get_error_message() ], 422 );
		}
		// The admin may leave questions unanswered (e.g. a phone booking).
		$data['custom_fields'] = SB_Custom_Fields::answers( $post, $data['service_id'], false );
		if ( is_wp_error( $data['custom_fields'] ) ) {
			wp_send_json_error( [ 'message' => $data['custom_fields']->get_error_message() ], 422 );
		}

		$quote = SB_Pricing::quote( $data['service_id'], (array) ( $post['extras'] ?? [] ), sanitize_text_field( $post['coupon'] ?? '' ), $data['booking_date'] );
		if ( $quote['coupon_error'] ) {
			wp_send_json_error( [ 'message' => $quote['coupon_error'] ], 422 );
		}
		$data['pricing'] = SB_Pricing::to_store( $quote );

		$bookings = new SB_Bookings();
		$free     = $bookings->get_available_slots( $data['service_id'], $data['staff_id'] ?: null, $data['booking_date'] );
		// Only save a new customer once the time is known to be free.
		$customer_id = in_array( $data['booking_time'], $free, true ) ? ( new SB_Customers() )->find_or_create( $data['name'], $data['email'], $data['phone'] ) : 0;
		$args = [
			'customer_id'     => $customer_id,
			'status'          => sanitize_key( $post['status'] ?? '' ),
			'by_admin'        => true,
			'notify_customer' => ! empty( $post['notify'] ),
		] + $data;

		// A coupon counts once, for a single booking or a whole series.
		if ( $customer_id && $quote['coupon'] && ! SB_Pricing::redeem( (int) $quote['coupon']['id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'This coupon has been used up.', 'simple-booking' ) ], 422 );
		}
		$release = static function () use ( $quote ) {
			if ( $quote['coupon'] ) {
				SB_Pricing::unredeem( (int) $quote['coupon']['id'] );
			}
		};

		$every = absint( $post['repeat_weeks'] ?? 0 );
		$count = absint( $post['repeat_count'] ?? 1 );
		if ( $customer_id && $every && $count > 1 ) {
			$series = $bookings->create_series( $args, $every, $count );
			if ( ! $series['ids'] ) {
				$release();
				wp_send_json_error( [ 'message' => __( 'That time is no longer free. Please pick another.', 'simple-booking' ) ], 409 );
			}
			$message = $series['missed']
				? sprintf(
					/* translators: 1: sessions booked, 2: list of dates */
					__( '%1$d sessions booked. These dates were not free and were skipped: %2$s', 'simple-booking' ),
					count( $series['ids'] ),
					implode( ', ', array_map( fn( $d ) => mysql2date( get_option( 'date_format' ), $d ), $series['missed'] ) )
				)
				: '';
			wp_send_json_success( [ 'ids' => $series['ids'], 'message' => $message ] );
		}

		$booking_id = $customer_id ? $bookings->create_booking( $args ) : false;
		if ( $customer_id && ! $booking_id ) {
			$release();
		}
		$booking_id
			? wp_send_json_success( [ 'id' => $booking_id ] )
			: wp_send_json_error( [ 'message' => __( 'That time is no longer free. Please pick another.', 'simple-booking' ) ], 409 );
	}

	public function ajax_cancel_series(): void {
		$post = $this->guard();
		$n    = ( new SB_Bookings() )->cancel_series( sanitize_key( $post['id'] ?? '' ) );
		/* translators: %d: number of sessions */
		wp_send_json_success( [ 'message' => sprintf( _n( '%d upcoming session cancelled.', '%d upcoming sessions cancelled.', $n, 'simple-booking' ), $n ) ] );
	}

	public function ajax_admin_reschedule(): void {
		$post = $this->guard();
		$ok   = ( new SB_Bookings() )->reschedule(
			absint( $post['id'] ?? 0 ),
			sanitize_text_field( $post['booking_date'] ?? '' ),
			sanitize_text_field( $post['booking_time'] ?? '' ),
			absint( $post['staff_id'] ?? 0 ) ?: null,
			! empty( $post['notify'] )
		);
		$ok ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'That time is no longer free, or the booking can\'t be moved. Please pick another time.', 'simple-booking' ) ], 409 );
	}

	public function ajax_save_settings(): void {
		$post = $this->guard();
		// update_option() returns false when nothing changed, so don't treat that as an error.
		SB_Settings::update_settings( (array) ( $post['settings'] ?? [] ) );
		wp_send_json_success();
	}

	public function ajax_save_customer(): void {
		$post   = $this->guard();
		$result = ( new SB_Customers() )->save( absint( $post['id'] ?? 0 ), $post );
		is_int( $result ) ? wp_send_json_success( [ 'id' => $result ] ) : wp_send_json_error( [ 'message' => $result ], 422 );
	}

	public function ajax_save_category(): void {
		$post = $this->guard();
		$id   = ( new SB_Categories() )->save( absint( $post['id'] ?? 0 ), (string) ( $post['name'] ?? '' ) );
		$id ? wp_send_json_success( [ 'id' => $id ] ) : wp_send_json_error( [ 'message' => __( 'Please enter a category name.', 'simple-booking' ) ], 422 );
	}

	public function ajax_delete_category(): void {
		$post = $this->guard();
		( new SB_Categories() )->delete( absint( $post['id'] ?? 0 ) )
			? wp_send_json_success()
			: wp_send_json_error( [ 'message' => __( 'Could not delete the category.', 'simple-booking' ) ], 500 );
	}

	public function ajax_save_field(): void {
		$error = SB_Custom_Fields::save( $this->guard() );
		'' === $error ? wp_send_json_success() : wp_send_json_error( [ 'message' => $error ], 422 );
	}

	public function ajax_delete_field(): void {
		SB_Custom_Fields::delete( sanitize_key( $this->guard()['id'] ?? '' ) );
		wp_send_json_success();
	}

	public function ajax_move_field(): void {
		$post = $this->guard();
		SB_Custom_Fields::move( sanitize_key( $post['id'] ?? '' ), (int) ( $post['direction'] ?? 0 ) );
		wp_send_json_success();
	}

	public function ajax_save_template(): void {
		$post = $this->guard();
		SB_Notifications::save( sanitize_key( $post['key'] ?? '' ), $post )
			? wp_send_json_success()
			: wp_send_json_error( [ 'message' => __( 'Please enter a subject and a message.', 'simple-booking' ) ], 422 );
	}

	/**
	 * Send the saved version of a template to the current admin, filled in with the newest booking (or sample data).
	 */
	public function ajax_test_template(): void {
		global $wpdb;
		$post = $this->guard();
		$key  = sanitize_key( $post['key'] ?? '' );
		$to   = wp_get_current_user()->user_email;
		if ( ! isset( SB_Notifications::types()[ $key ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Unknown email.', 'simple-booking' ) ], 422 );
		}
		$latest = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}sb_bookings" );
		( new SB_Email() )->send_test( $key, $to, $latest ?: null )
			? wp_send_json_success( [ 'to' => $to ] )
			: wp_send_json_error( [ 'message' => __( 'WordPress could not send the email. Check your site\'s email setup (e.g. an SMTP plugin).', 'simple-booking' ) ], 500 );
	}

	public function ajax_save_theme(): void {
		$post = $this->guard();
		update_user_meta( get_current_user_id(), self::THEME_META, 'light' === ( $post['theme'] ?? '' ) ? 'light' : 'dark' );
		wp_send_json_success();
	}

	/**
	 * A "Y-m-d" query arg, or $default when missing or not a real date.
	 */
	private function date_param( string $key, string $default ): string {
		$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value ? $value : $default;
	}

	/**
	 * Capability + nonce check for every admin AJAX action. Returns unslashed POST data.
	 */
	private function guard(): array {
		SB_Security::check_admin_permission();
		SB_Security::verify_nonce();
		return wp_unslash( $_POST );
	}

	private function status_labels(): array {
		return [
			'pending'   => __( 'Pending', 'simple-booking' ),
			'confirmed' => __( 'Confirmed', 'simple-booking' ),
			'cancelled' => __( 'Cancelled', 'simple-booking' ),
			'completed' => __( 'Completed', 'simple-booking' ),
		];
	}
}
