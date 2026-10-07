<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service categories. A service has at most one; NULL means uncategorized.
 */
class SB_Categories {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sb_categories';
	}

	/**
	 * All categories by name, each with 'service_count' (services of any status).
	 */
	public function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT c.*, COUNT(s.id) AS service_count
			 FROM {$this->table_name} c
			 LEFT JOIN {$wpdb->prefix}sb_services s ON s.category_id = c.id
			 GROUP BY c.id
			 ORDER BY c.name ASC",
			ARRAY_A
		);
	}

	public function exists( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$this->table_name} WHERE id = %d", $id ) );
	}

	/**
	 * Create ($id = 0) or rename a category. Returns the id, or false.
	 */
	public function save( int $id, string $name ): int|false {
		global $wpdb;
		$name = sanitize_text_field( $name );
		if ( '' === $name || mb_strlen( $name ) > 191 ) {
			return false;
		}
		if ( $id ) {
			return false !== $wpdb->update( $this->table_name, [ 'name' => $name ], [ 'id' => $id ], [ '%s' ], [ '%d' ] ) ? $id : false;
		}
		return $wpdb->insert( $this->table_name, [ 'name' => $name ], [ '%s' ] ) ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete a category. Its services stay, uncategorized.
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}sb_services", [ 'category_id' => null ], [ 'category_id' => $id ], null, [ '%d' ] );
		return (bool) $wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}
}
