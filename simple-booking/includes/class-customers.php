<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Customers {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sb_customers';
	}

	/**
	 * Return the customer id for this email, creating the customer if needed.
	 * Existing customers are not overwritten: the form is public, so anyone could
	 * otherwise change another customer's name or phone by entering their email.
	 */
	public function find_or_create( string $name, string $email, string $phone ): int|false {
		global $wpdb;

		$id = $this->get_id_by_email( $email );
		if ( $id ) {
			return $id;
		}

		$inserted = $wpdb->insert(
			$this->table_name,
			[ 'name' => $name, 'email' => $email, 'phone' => $phone ],
			[ '%s', '%s', '%s' ]
		);

		// A failed insert usually means a parallel request created the same email (UNIQUE).
		return $inserted ? (int) $wpdb->insert_id : ( $this->get_id_by_email( $email ) ?: false );
	}

	private function get_id_by_email( string $email ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE email = %s", $email )
		);
	}
}
