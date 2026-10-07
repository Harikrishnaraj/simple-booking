<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Admin_Controller {

	private const PER_PAGE = 50;

	public function register_admin_menu(): void {
		add_menu_page( __( 'Simple Booking', 'simple-booking' ), __( 'Bookings', 'simple-booking' ), 'manage_options', 'sb-bookings', [ $this, 'render_bookings' ], 'dashicons-calendar-alt', 26 );
		add_submenu_page( 'sb-bookings', __( 'Bookings', 'simple-booking' ), __( 'All Bookings', 'simple-booking' ), 'manage_options', 'sb-bookings', [ $this, 'render_bookings' ] );
		add_submenu_page( 'sb-bookings', __( 'Services', 'simple-booking' ), __( 'Services', 'simple-booking' ), 'manage_options', 'sb-services', [ $this, 'render_services' ] );
		add_submenu_page( 'sb-bookings', __( 'Staff', 'simple-booking' ), __( 'Staff', 'simple-booking' ), 'manage_options', 'sb-staff', [ $this, 'render_staff' ] );
		add_submenu_page( 'sb-bookings', __( 'Settings', 'simple-booking' ), __( 'Settings', 'simple-booking' ), 'manage_options', 'sb-settings', [ $this, 'render_settings' ] );
	}

	public function enqueue_styles_and_scripts( string $hook ): void {
		if ( ! str_contains( $hook, 'sb-' ) ) {
			return;
		}
		wp_enqueue_script( 'sb-admin', SB_PLUGIN_URL . 'admin/assets/js/admin.js', [], SB_VERSION, true );
		wp_localize_script( 'sb-admin', 'sbAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'sb_admin_nonce' ),
			'i18n'    => [
				'error'         => __( 'Something went wrong. Please try again.', 'simple-booking' ),
				'confirmDelete' => __( 'Delete this item? Items with bookings are deactivated instead.', 'simple-booking' ),
			],
		] );
	}

	/* ---------- Pages ---------- */

	public function render_bookings(): void {
		$bookings = new SB_Bookings();
		$page     = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		sb_view( 'admin/views/bookings', [
			'bookings' => $bookings->get_list( $page, self::PER_PAGE ),
			'page'     => $page,
			'pages'    => (int) ceil( $bookings->count() / self::PER_PAGE ),
			'statuses' => $this->status_labels(),
		] );
	}

	public function render_services(): void {
		$services = new SB_Services();
		$edit_id  = absint( $_GET['edit'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		sb_view( 'admin/views/services', [
			'services' => $services->get_all( 'all' ),
			'editing'  => $edit_id ? $services->get_by_id( $edit_id ) : null,
			'page_url' => admin_url( 'admin.php?page=sb-services' ),
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
			'page_url'     => admin_url( 'admin.php?page=sb-staff' ),
		] );
	}

	public function render_settings(): void {
		sb_view( 'admin/views/settings', [ 'settings' => SB_Settings::get_settings() ] );
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
		$post = $this->guard();
		$ok   = ( new SB_Bookings() )->update_status( absint( $post['id'] ?? 0 ), sanitize_key( $post['status'] ?? '' ) );
		$ok ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'Could not update the booking status.', 'simple-booking' ) ], 422 );
	}

	public function ajax_save_settings(): void {
		$post = $this->guard();
		// update_option() returns false when nothing changed, so don't treat that as an error.
		SB_Settings::update_settings( (array) ( $post['settings'] ?? [] ) );
		wp_send_json_success();
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
