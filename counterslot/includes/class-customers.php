<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names come from $wpdb->prefix; every value goes through $wpdb->prepare().

class CSlot_Customers {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'cslot_customers';
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

	/**
	 * Customers with their booking totals for the admin list, newest first.
	 * $search matches name, email or phone.
	 */
	public function get_list( string $search, int $page, int $per_page ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, COUNT(b.id) AS total_bookings, MAX(b.booking_date) AS last_booking
				 FROM {$this->table_name} c
				 LEFT JOIN {$wpdb->prefix}cslot_bookings b ON b.customer_id = c.id AND b.status <> 'cancelled'
				 WHERE {$this->search_sql( $search )}
				 GROUP BY c.id
				 ORDER BY c.id DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				( max( 1, $page ) - 1 ) * $per_page
			),
			ARRAY_A
		);
	}

	public function count( string $search = '' ): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} c WHERE {$this->search_sql( $search )}" );
	}

	public function get_by_id( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Create ($id = 0) or update a customer from the admin. Returns the id, or an error message.
	 */
	public function save( int $id, array $data ): int|string {
		global $wpdb;

		$row = [
			'name'  => sanitize_text_field( $data['name'] ?? '' ),
			'email' => sanitize_email( $data['email'] ?? '' ),
			'phone' => sanitize_text_field( $data['phone'] ?? '' ),
			'note'  => sanitize_textarea_field( $data['note'] ?? '' ),
		];
		if ( '' === $row['name'] || ! is_email( $row['email'] ) ) {
			return __( 'Please enter a name and a valid email address.', 'counterslot' );
		}

		$owner = $this->get_id_by_email( $row['email'] );
		if ( $owner && $owner !== $id ) {
			return __( 'Another customer already uses this email address.', 'counterslot' );
		}

		if ( $id ) {
			$ok = false !== $wpdb->update( $this->table_name, $row, [ 'id' => $id ], [ '%s', '%s', '%s', '%s' ], [ '%d' ] );
		} else {
			$ok = (bool) $wpdb->insert( $this->table_name, $row, [ '%s', '%s', '%s', '%s' ] );
			$id = (int) $wpdb->insert_id;
		}
		return $ok ? $id : __( 'Could not save the customer.', 'counterslot' );
	}

	private function search_sql( string $search ): string {
		global $wpdb;
		if ( '' === $search ) {
			return '1=1';
		}
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		return $wpdb->prepare( '(c.name LIKE %s OR c.email LIKE %s OR c.phone LIKE %s)', $like, $like, $like );
	}

	private function get_id_by_email( string $email ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE email = %s", $email )
		);
	}
}
