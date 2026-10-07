<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Security {

	/**
	 * Verify admin capabilities.
	 */
	public static function check_admin_permission(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'simple-booking' ) ], 403 );
		}
	}

	/**
	 * Verify Nonce.
	 */
	public static function verify_nonce( string $nonce_action = 'sb_admin_nonce' ): void {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_send_json_error( [ 'message' => __( 'Security verification failed.', 'simple-booking' ) ], 403 );
		}
	}
}
