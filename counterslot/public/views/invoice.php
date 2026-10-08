<?php
/**
 * Printable invoice: a standalone page (print or "Save as PDF" from the browser).
 *
 * @var array      $booking  Booking with service_name, staff_name, location
 * @var array      $invoice  number and date
 * @var array|null $customer
 * @var array      $pricing  Stored price breakdown
 * @var array      $payments Payments for the booking
 * @var array      $settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- included inside cslot_view(), so these are local variables.
$paid     = array_sum( array_map( 'floatval', array_column( $payments, 'amount' ) ) );
$balance  = round( (float) $pricing['total'] - $paid, 2 );
$methods  = CSlot_Payments::methods();
$date_fmt = get_option( 'date_format' );
$lines    = CSlot_Pricing::lines( $pricing );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<title><?php echo esc_html( sprintf( /* translators: invoice number */ __( 'Invoice %s', 'counterslot' ), $invoice['number'] ) ); ?></title>
	<style>
		body { margin: 0; background: #f3f4f6; color: #111827; font: 14px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
		.sheet { max-width: 760px; margin: 24px auto; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
		header { display: flex; justify-content: space-between; gap: 24px; margin-bottom: 32px; }
		h1 { margin: 0 0 4px; font-size: 24px; }
		h2 { margin: 0 0 6px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
		.muted { color: #6b7280; }
		.cols { display: flex; gap: 40px; margin-bottom: 28px; }
		table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
		th, td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: left; }
		td.num, th.num { text-align: right; }
		tr.total td { border-bottom: 0; border-top: 2px solid #111827; font-weight: 700; font-size: 16px; }
		.balance { margin-top: 20px; padding: 12px 16px; border-radius: 6px; background: #f9fafb; display: flex; justify-content: space-between; font-weight: 600; }
		.actions { max-width: 760px; margin: 0 auto; text-align: right; }
		.actions button { padding: 8px 16px; border: 0; border-radius: 6px; background: #4f46e5; color: #fff; font: inherit; cursor: pointer; }
		@media print { body { background: #fff; } .sheet { margin: 0; box-shadow: none; } .actions { display: none; } }
	</style>
</head>
<body>
	<div class="actions"><button type="button" onclick="window.print()"><?php esc_html_e( 'Print / Save as PDF', 'counterslot' ); ?></button></div>
	<main class="sheet">
		<header>
			<div>
				<h1><?php echo esc_html( $settings['business_name'] ); ?></h1>
				<div class="muted"><?php echo nl2br( esc_html( $settings['business_address'] ) ); ?></div>
				<?php if ( $settings['tax_id'] ) : ?>
					<div class="muted"><?php echo esc_html( ( $pricing['tax_name'] ?: __( 'Tax', 'counterslot' ) ) . ' ' . __( 'number:', 'counterslot' ) . ' ' . $settings['tax_id'] ); ?></div>
				<?php endif; ?>
			</div>
			<div style="text-align:right">
				<h2><?php esc_html_e( 'Invoice', 'counterslot' ); ?></h2>
				<div><strong><?php echo esc_html( $invoice['number'] ); ?></strong></div>
				<div class="muted"><?php echo esc_html( mysql2date( $date_fmt, $invoice['date'] ) ); ?></div>
			</div>
		</header>

		<div class="cols">
			<div>
				<h2><?php esc_html_e( 'Billed to', 'counterslot' ); ?></h2>
				<div><?php echo esc_html( $customer['name'] ?? '' ); ?></div>
				<div class="muted"><?php echo esc_html( $customer['email'] ?? '' ); ?></div>
				<?php if ( ! empty( $customer['phone'] ) ) : ?><div class="muted"><?php echo esc_html( $customer['phone'] ); ?></div><?php endif; ?>
			</div>
			<div>
				<h2><?php esc_html_e( 'Appointment', 'counterslot' ); ?></h2>
				<div><?php echo esc_html( $booking['service_name'] ); ?></div>
				<div class="muted"><?php echo esc_html( mysql2date( $date_fmt, $booking['booking_date'] ) . ', ' . substr( $booking['booking_time'], 0, 5 ) ); ?></div>
				<?php if ( $booking['staff_name'] ) : ?><div class="muted"><?php echo esc_html( $booking['staff_name'] ); ?></div><?php endif; ?>
				<div class="muted"><?php echo esc_html( $booking['booking_code'] ); ?></div>
			</div>
		</div>

		<table>
			<thead><tr><th scope="col"><?php esc_html_e( 'Description', 'counterslot' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Amount', 'counterslot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $lines as $i => [ $label, $amount ] ) : ?>
					<tr class="<?php echo count( $lines ) - 1 === $i ? 'total' : ''; ?>">
						<td><?php echo esc_html( 0 === $i ? $booking['service_name'] : $label ); ?></td>
						<td class="num"><?php echo esc_html( cslot_price( $amount ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $payments ) : ?>
			<h2 style="margin-top:28px"><?php esc_html_e( 'Payments', 'counterslot' ); ?></h2>
			<table>
				<tbody>
					<?php foreach ( $payments as $p ) : ?>
						<tr>
							<td><?php echo esc_html( mysql2date( $date_fmt, $p['paid_at'] ) . ' · ' . ( $methods[ $p['method'] ] ?? $p['method'] ) . ( (float) $p['amount'] < 0 ? ' · ' . __( 'Refund', 'counterslot' ) : '' ) ); ?></td>
							<td class="num"><?php echo esc_html( cslot_price( $p['amount'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<div class="balance">
			<span><?php echo $balance > 0 ? esc_html__( 'Balance due', 'counterslot' ) : esc_html__( 'Paid in full', 'counterslot' ); ?></span>
			<span><?php echo esc_html( cslot_price( max( 0, $balance ) ) ); ?></span>
		</div>
	</main>
</body>
</html>
