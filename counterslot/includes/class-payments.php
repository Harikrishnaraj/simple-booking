<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payments recorded by hand (pay at the clinic): cash, UPI, card, bank transfer.
 * A negative amount is a refund. A booking's balance is its total minus what's been paid.
 */
class SB_Payments {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sb_payments';
	}

	public static function methods(): array {
		return [
			'cash'  => __( 'Cash', 'counterslot' ),
			'upi'   => __( 'UPI', 'counterslot' ),
			'card'  => __( 'Card', 'counterslot' ),
			'bank'  => __( 'Bank transfer', 'counterslot' ),
			'other' => __( 'Other', 'counterslot' ),
		];
	}

	public function for_booking( int $booking_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE booking_id = %d ORDER BY paid_at ASC, id ASC", $booking_id ), ARRAY_A );
	}

	/**
	 * booking id => amount paid, for a list of bookings.
	 */
	public function paid_by_booking( array $booking_ids ): array {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', $booking_ids ) );
		if ( ! $ids ) {
			return [];
		}
		$rows = $wpdb->get_results(
			"SELECT booking_id, SUM(amount) AS paid FROM {$this->table_name} WHERE booking_id IN (" . implode( ',', $ids ) . ') GROUP BY booking_id',
			ARRAY_A
		);
		return array_map( 'floatval', array_column( $rows, 'paid', 'booking_id' ) );
	}

	/**
	 * Record a payment. Returns '' or an error message.
	 */
	public function add( int $booking_id, array $in ): string {
		global $wpdb;
		$amount = round( (float) ( $in['amount'] ?? 0 ), 2 );
		$method = sanitize_key( $in['method'] ?? '' );
		$date   = sanitize_text_field( $in['paid_at'] ?? '' );
		$day    = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		if ( ! ( new SB_Bookings() )->get_by_id( $booking_id ) ) {
			return __( 'Booking not found.', 'counterslot' );
		}
		if ( ! $amount || abs( $amount ) > 10000000 ) {
			return __( 'Please enter an amount (negative for a refund).', 'counterslot' );
		}
		if ( ! isset( self::methods()[ $method ] ) ) {
			return __( 'Please choose how it was paid.', 'counterslot' );
		}
		$ok = $wpdb->insert(
			$this->table_name,
			[
				'booking_id' => $booking_id,
				'amount'     => $amount,
				'method'     => $method,
				'note'       => mb_substr( sanitize_text_field( $in['note'] ?? '' ), 0, 191 ),
				'paid_at'    => ( $day && $day->format( 'Y-m-d' ) === $date ? $date : wp_date( 'Y-m-d' ) ) . ' ' . wp_date( 'H:i:s' ),
				'created_by' => get_current_user_id(),
			],
			[ '%d', '%f', '%s', '%s', '%s', '%d' ]
		);
		return $ok ? '' : __( 'Could not save the payment.', 'counterslot' );
	}

	public function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Payments between two dates (inclusive), newest first, with booking and customer details.
	 */
	public function between( string $from, string $to ): array {
		global $wpdb;
		$p = $wpdb->prefix;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pay.*, b.booking_code, b.booking_date, c.name AS customer_name, s.name AS service_name
				 FROM {$this->table_name} pay
				 LEFT JOIN {$p}sb_bookings b ON b.id = pay.booking_id
				 LEFT JOIN {$p}sb_customers c ON c.id = b.customer_id
				 LEFT JOIN {$p}sb_services s ON s.id = b.service_id
				 WHERE pay.paid_at BETWEEN %s AND %s
				 ORDER BY pay.paid_at DESC, pay.id DESC",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);
	}

	/**
	 * Unpaid or part-paid bookings (not cancelled), oldest appointment first.
	 */
	public function outstanding(): array {
		global $wpdb;
		$p = $wpdb->prefix;
		return $wpdb->get_results(
			"SELECT b.id, b.booking_code, b.booking_date, b.booking_time, b.status, b.total, c.name AS customer_name, s.name AS service_name,
				COALESCE(pay.paid, 0) AS paid
			 FROM {$p}sb_bookings b
			 LEFT JOIN (SELECT booking_id, SUM(amount) AS paid FROM {$this->table_name} GROUP BY booking_id) pay ON pay.booking_id = b.id
			 LEFT JOIN {$p}sb_customers c ON c.id = b.customer_id
			 LEFT JOIN {$p}sb_services s ON s.id = b.service_id
			 WHERE b.status IN ('pending', 'confirmed', 'completed') AND b.total > 0 AND COALESCE(pay.paid, 0) < b.total
			 ORDER BY b.booking_date ASC, b.booking_time ASC
			 LIMIT 500",
			ARRAY_A
		);
	}

	/**
	 * 'free', 'unpaid', 'partial', 'paid' or 'overpaid'.
	 */
	public static function status( ?float $total, float $paid ): string {
		$total = (float) $total;
		if ( $total <= 0 && $paid <= 0 ) {
			return 'free';
		}
		if ( $paid <= 0 ) {
			return 'unpaid';
		}
		if ( $paid + 0.005 < $total ) {
			return 'partial';
		}
		return $paid - 0.005 > $total ? 'overpaid' : 'paid';
	}

	public static function status_label( string $status ): string {
		return [
			'free'     => __( 'Free', 'counterslot' ),
			'unpaid'   => __( 'Unpaid', 'counterslot' ),
			'partial'  => __( 'Part paid', 'counterslot' ),
			'paid'     => __( 'Paid', 'counterslot' ),
			'overpaid' => __( 'Overpaid', 'counterslot' ),
		][ $status ] ?? $status;
	}

	/**
	 * The booking's invoice number and date, assigned the first time it's opened:
	 * prefix + 4-digit sequence, and that day's date (it doesn't change afterwards).
	 *
	 * @return array{number: string, date: string}
	 */
	public static function invoice( array $booking ): array {
		global $wpdb;
		if ( ! empty( $booking['invoice_number'] ) ) {
			if ( empty( $booking['invoice_date'] ) ) {
				$booking['invoice_date'] = wp_date( 'Y-m-d' );
				$wpdb->update( "{$wpdb->prefix}sb_bookings", [ 'invoice_date' => $booking['invoice_date'] ], [ 'id' => (int) $booking['id'] ], [ '%s' ], [ '%d' ] );
			}
			return [ 'number' => $booking['invoice_number'], 'date' => $booking['invoice_date'] ];
		}
		// Atomic counter: concurrent invoices can't get the same number.
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s", 'sb_invoice_counter' ) );
		if ( ! $wpdb->rows_affected ) {
			add_option( 'sb_invoice_counter', 1, '', false );
		}
		wp_cache_delete( 'sb_invoice_counter', 'options' );
		$number = SB_Settings::get_settings()['invoice_prefix'] . str_pad( (string) get_option( 'sb_invoice_counter' ), 4, '0', STR_PAD_LEFT );
		$date   = wp_date( 'Y-m-d' );
		$wpdb->update( "{$wpdb->prefix}sb_bookings", [ 'invoice_number' => $number, 'invoice_date' => $date ], [ 'id' => (int) $booking['id'] ], [ '%s', '%s' ], [ '%d' ] );
		return [ 'number' => $number, 'date' => $date ];
	}
}
