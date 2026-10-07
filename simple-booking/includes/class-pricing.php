<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a booking costs: service price + chosen extras − coupon discount, with tax.
 * Extras are a small option list; coupons are a table so their use count can be
 * updated atomically. Each booking stores its own breakdown, so later price changes
 * don't alter past bookings.
 */
class SB_Pricing {

	private const EXTRAS_KEY = 'sb_extras';

	/* ---------- Extras ---------- */

	/**
	 * @return array<int, array{id: string, name: string, price: float, services: int[]}>
	 */
	public static function extras(): array {
		return array_values( (array) get_option( self::EXTRAS_KEY, [] ) );
	}

	public static function extras_for_service( int $service_id ): array {
		return array_values( array_filter( self::extras(), fn( $e ) => ! $e['services'] || in_array( $service_id, $e['services'], true ) ) );
	}

	/**
	 * Create (empty id) or update an extra. Returns '' or an error message.
	 */
	public static function save_extra( array $in ): string {
		$name = sanitize_text_field( (string) ( $in['name'] ?? '' ) );
		if ( '' === $name || mb_strlen( $name ) > 191 ) {
			return __( 'Please enter a name.', 'simple-booking' );
		}
		$extra = [
			'id'       => sanitize_key( (string) ( $in['id'] ?? '' ) ) ?: 'x' . strtolower( wp_generate_password( 8, false ) ),
			'name'     => $name,
			'price'    => max( 0, round( (float) ( $in['price'] ?? 0 ), 2 ) ),
			'services' => array_values( array_unique( array_filter( array_map( 'absint', (array) ( $in['services'] ?? [] ) ) ) ) ),
		];
		$all   = self::extras();
		$index = array_search( $extra['id'], array_column( $all, 'id' ), true );
		if ( false === $index ) {
			$all[] = $extra;
		} else {
			$all[ $index ] = $extra;
		}
		update_option( self::EXTRAS_KEY, $all, false );
		return '';
	}

	public static function delete_extra( string $id ): void {
		update_option( self::EXTRAS_KEY, array_values( array_filter( self::extras(), fn( $e ) => $e['id'] !== $id ) ), false );
	}

	/* ---------- Coupons ---------- */

	private static function coupons_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'sb_coupons';
	}

	public static function coupons(): array {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::coupons_table() . ' ORDER BY code ASC', ARRAY_A );
	}

	public static function coupon_by_code( string $code ): ?array {
		global $wpdb;
		$row = '' === $code ? null : $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::coupons_table() . ' WHERE code = %s', self::normalise_code( $code ) ), ARRAY_A );
		return $row ?: null;
	}

	public static function normalise_code( string $code ): string {
		return strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', $code ) );
	}

	/**
	 * Create ($id = 0) or update a coupon. Returns '' or an error message.
	 */
	public static function save_coupon( int $id, array $in ): string {
		global $wpdb;
		$code  = self::normalise_code( (string) ( $in['code'] ?? '' ) );
		$type  = 'fixed' === ( $in['type'] ?? '' ) ? 'fixed' : 'percent';
		$value = max( 0, round( (float) ( $in['value'] ?? 0 ), 2 ) );
		$date  = static function ( $d ) {
			$x = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $d );
			return $x && $x->format( 'Y-m-d' ) === $d ? $d : null;
		};
		if ( '' === $code || strlen( $code ) > 50 ) {
			return __( 'Please enter a code (letters, numbers, - and _).', 'simple-booking' );
		}
		if ( ! $value || ( 'percent' === $type && $value > 100 ) ) {
			return __( 'Please enter a discount above 0 (at most 100 for a percentage).', 'simple-booking' );
		}
		$existing = self::coupon_by_code( $code );
		if ( $existing && (int) $existing['id'] !== $id ) {
			return __( 'Another coupon already uses this code.', 'simple-booking' );
		}

		$row = [
			'code'       => $code,
			'type'       => $type,
			'value'      => $value,
			'services'   => wp_json_encode( array_values( array_unique( array_filter( array_map( 'absint', (array) ( $in['services'] ?? [] ) ) ) ) ) ),
			'valid_from' => $date( $in['valid_from'] ?? '' ),
			'valid_to'   => $date( $in['valid_to'] ?? '' ),
			'max_uses'   => absint( $in['max_uses'] ?? 0 ),
			'status'     => 'inactive' === ( $in['status'] ?? '' ) ? 'inactive' : 'active',
		];
		$formats = [ '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s' ];
		$ok      = $id
			? false !== $wpdb->update( self::coupons_table(), $row, [ 'id' => $id ], $formats, [ '%d' ] )
			: (bool) $wpdb->insert( self::coupons_table(), $row, $formats );
		return $ok ? '' : __( 'Could not save the coupon.', 'simple-booking' );
	}

	public static function delete_coupon( int $id ): void {
		global $wpdb;
		$wpdb->delete( self::coupons_table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Why a coupon can't be used for this service on this date, or '' if it can.
	 */
	public static function coupon_problem( ?array $coupon, int $service_id, string $date ): string {
		if ( ! $coupon || 'active' !== $coupon['status'] ) {
			return __( 'This coupon code is not valid.', 'simple-booking' );
		}
		$services = (array) json_decode( (string) $coupon['services'], true );
		if ( $services && ! in_array( $service_id, array_map( 'intval', $services ), true ) ) {
			return __( 'This coupon can\'t be used for this service.', 'simple-booking' );
		}
		if ( ( $coupon['valid_from'] && $date < $coupon['valid_from'] ) || ( $coupon['valid_to'] && $date > $coupon['valid_to'] ) ) {
			return __( 'This coupon isn\'t valid for that date.', 'simple-booking' );
		}
		if ( $coupon['max_uses'] && (int) $coupon['used'] >= (int) $coupon['max_uses'] ) {
			return __( 'This coupon has been used up.', 'simple-booking' );
		}
		return '';
	}

	/**
	 * Count one use, unless it's used up (atomic, so two bookings can't both take the last use).
	 */
	public static function redeem( int $coupon_id ): bool {
		global $wpdb;
		return 1 === (int) $wpdb->query(
			$wpdb->prepare( 'UPDATE ' . self::coupons_table() . ' SET used = used + 1 WHERE id = %d AND (max_uses = 0 OR used < max_uses)', $coupon_id )
		);
	}

	public static function unredeem( int $coupon_id ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::coupons_table() . ' SET used = used - 1 WHERE id = %d AND used > 0', $coupon_id ) );
	}

	/* ---------- Quote ---------- */

	/**
	 * Price breakdown for a service with extras (ids) and an optional coupon code.
	 *
	 * @return array{price: float, extras: array, extras_total: float, subtotal: float, discount: float,
	 *               coupon: ?array, coupon_error: string, tax: float, total: float, tax_included: bool}
	 */
	public static function quote( int $service_id, array $extra_ids, string $coupon_code, string $date ): array {
		$service  = ( new SB_Services() )->get_by_id( $service_id );
		$price    = (float) ( $service['price'] ?? 0 );
		$wanted   = array_map( 'strval', $extra_ids );
		$extras   = array_values( array_filter( self::extras_for_service( $service_id ), fn( $e ) => in_array( $e['id'], $wanted, true ) ) );
		$extras   = array_map( fn( $e ) => [ 'id' => $e['id'], 'name' => $e['name'], 'price' => (float) $e['price'] ], $extras );
		$subtotal = $price + array_sum( array_column( $extras, 'price' ) );

		$coupon   = null;
		$error    = '';
		$discount = 0.0;
		if ( '' !== trim( $coupon_code ) ) {
			$coupon = self::coupon_by_code( $coupon_code );
			$error  = self::coupon_problem( $coupon, $service_id, $date );
			if ( $error ) {
				$coupon = null;
			} else {
				$discount = 'fixed' === $coupon['type'] ? (float) $coupon['value'] : $subtotal * (float) $coupon['value'] / 100;
				$discount = min( $subtotal, round( $discount, 2 ) );
			}
		}

		$settings = SB_Settings::get_settings();
		$rate     = max( 0, (float) $settings['tax_rate'] ) / 100;
		$included = ! empty( $settings['prices_include_tax'] );
		$net      = $subtotal - $discount;
		$tax      = $included ? $net - $net / ( 1 + $rate ) : $net * $rate;
		$tax      = round( $tax, 2 );

		return [
			'tax_name'     => (string) $settings['tax_name'],
			'tax_rate'     => (float) $settings['tax_rate'],
			'price'        => round( $price, 2 ),
			'extras'       => $extras,
			'extras_total' => round( $subtotal - $price, 2 ),
			'subtotal'     => round( $subtotal, 2 ),
			'discount'     => $discount,
			'coupon'       => $coupon,
			'coupon_error' => $error,
			'tax'          => $tax,
			'total'        => round( $included ? $net : $net + $tax, 2 ),
			'tax_included' => $included,
		];
	}

	/**
	 * The part of a quote saved on the booking (bookings.pricing).
	 */
	public static function to_store( array $q ): array {
		$q['coupon_code'] = $q['coupon']['code'] ?? '';
		unset( $q['coupon'], $q['coupon_error'] );
		return $q;
	}

	/**
	 * Breakdown lines for display, from a quote or a stored breakdown: [ label, amount ] pairs,
	 * ending with the total.
	 */
	public static function lines( array $q ): array {
		$lines = [ [ __( 'Service', 'simple-booking' ), (float) $q['price'] ] ];
		foreach ( $q['extras'] as $e ) {
			$lines[] = [ '+ ' . $e['name'], (float) $e['price'] ];
		}
		if ( $q['discount'] > 0 ) {
			/* translators: %s: coupon code */
			$lines[] = [ sprintf( __( 'Coupon %s', 'simple-booking' ), $q['coupon']['code'] ?? $q['coupon_code'] ?? '' ), -(float) $q['discount'] ];
		}
		if ( $q['tax'] > 0 ) {
			$name    = $q['tax_name'] ?: __( 'Tax', 'simple-booking' );
			/* translators: 1: tax name, 2: rate */
			$lines[] = [ sprintf( $q['tax_included'] ? __( 'Includes %1$s (%2$s%%)', 'simple-booking' ) : __( '%1$s (%2$s%%)', 'simple-booking' ), $name, (float) $q['tax_rate'] ), (float) $q['tax'] ];
		}
		$lines[] = [ __( 'Total', 'simple-booking' ), $q['total'] ];
		return $lines;
	}
}
