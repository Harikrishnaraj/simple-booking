<?php
/**
 * @var string $tab         payments | unpaid
 * @var string $from
 * @var string $to
 * @var array  $payments    SB_Payments::between()
 * @var array  $outstanding SB_Payments::outstanding()
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$page_url = admin_url( 'admin.php?page=sb-finance' );
$methods  = SB_Payments::methods();
$date_fmt = get_option( 'date_format' );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Finance', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<nav class="sb-tabs" aria-label="<?php esc_attr_e( 'Finance sections', 'simple-booking' ); ?>">
		<a class="sb-tab <?php echo 'payments' === $tab ? 'is-current' : ''; ?>" href="<?php echo esc_url( $page_url ); ?>" <?php echo 'payments' === $tab ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'Payments', 'simple-booking' ); ?></a>
		<a class="sb-tab <?php echo 'unpaid' === $tab ? 'is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'unpaid', $page_url ) ); ?>" <?php echo 'unpaid' === $tab ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'Unpaid', 'simple-booking' ); ?></a>
	</nav>

	<section class="sb-card">
	<?php if ( 'payments' === $tab ) : ?>
		<?php
		$total     = array_sum( array_map( 'floatval', array_column( $payments, 'amount' ) ) );
		$by_method = [];
		foreach ( $payments as $p ) {
			$by_method[ $p['method'] ] = ( $by_method[ $p['method'] ] ?? 0 ) + (float) $p['amount'];
		}
		?>
		<form method="get" class="sb-toolbar">
			<input type="hidden" name="page" value="sb-finance">
			<div class="sb-field-inline"><label for="sb-fin-from"><?php esc_html_e( 'From', 'simple-booking' ); ?></label><input id="sb-fin-from" class="sb-input" type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></div>
			<div class="sb-field-inline"><label for="sb-fin-to"><?php esc_html_e( 'To', 'simple-booking' ); ?></label><input id="sb-fin-to" class="sb-input" type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></div>
			<button type="submit" class="sb-button sb-button--secondary"><?php esc_html_e( 'Apply', 'simple-booking' ); ?></button>
			<a class="sb-button sb-button--secondary sb-toolbar__end" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sb_export_payments&from=' . $from . '&to=' . $to ), 'sb_export_payments' ) ); ?>">
				<span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'Export CSV', 'simple-booking' ); ?>
			</a>
		</form>

		<div class="sb-stats">
			<div class="sb-stat sb-stat--plain">
				<p class="sb-stat__label"><?php esc_html_e( 'Received', 'simple-booking' ); ?></p>
				<p class="sb-stat__value"><?php echo esc_html( sb_price( $total ) ); ?></p>
				<p class="sb-stat__meta"><?php echo esc_html( sprintf( _n( '%d payment', '%d payments', count( $payments ), 'simple-booking' ), count( $payments ) ) ); ?></p>
			</div>
			<?php foreach ( $by_method as $method => $amount ) : ?>
				<div class="sb-stat sb-stat--plain">
					<p class="sb-stat__label"><?php echo esc_html( $methods[ $method ] ?? $method ); ?></p>
					<p class="sb-stat__value sb-stat__value--small"><?php echo esc_html( sb_price( $amount ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $payments ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Date', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Customer', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Booking', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Method', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Note', 'simple-booking' ); ?></th>
						<th scope="col" class="sb-num"><?php esc_html_e( 'Amount', 'simple-booking' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $payments as $p ) : ?>
							<tr>
								<td><?php echo esc_html( mysql2date( $date_fmt, $p['paid_at'] ) ); ?></td>
								<td><?php echo esc_html( $p['customer_name'] ?? '—' ); ?></td>
								<td><a class="sb-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-bookings&s=' . rawurlencode( (string) $p['booking_code'] ) ) ); ?>"><code translate="no"><?php echo esc_html( $p['booking_code'] ); ?></code></a><br><span class="sb-muted"><?php echo esc_html( $p['service_name'] ); ?></span></td>
								<td><?php echo esc_html( $methods[ $p['method'] ] ?? $p['method'] ); ?></td>
								<td class="sb-note"><?php echo esc_html( $p['note'] ); ?></td>
								<td class="sb-num"><?php echo esc_html( sb_price( $p['amount'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No payments recorded in this period. Record payments from the Bookings page.', 'simple-booking' ); ?></p>
		<?php endif; ?>

	<?php else : ?>
		<?php $due = array_sum( array_map( fn( $b ) => (float) $b['total'] - (float) $b['paid'], $outstanding ) ); ?>
		<p class="sb-muted"><?php echo esc_html( sprintf( __( '%1$s outstanding across %2$d bookings (pending, confirmed and completed).', 'simple-booking' ), sb_price( $due ), count( $outstanding ) ) ); ?></p>
		<?php if ( $outstanding ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Appointment', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Customer', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Booking', 'simple-booking' ); ?></th>
						<th scope="col" class="sb-num"><?php esc_html_e( 'Total', 'simple-booking' ); ?></th>
						<th scope="col" class="sb-num"><?php esc_html_e( 'Paid', 'simple-booking' ); ?></th>
						<th scope="col" class="sb-num"><?php esc_html_e( 'Due', 'simple-booking' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $outstanding as $b ) : ?>
							<tr>
								<td><?php echo esc_html( mysql2date( $date_fmt, $b['booking_date'] ) . ' ' . substr( $b['booking_time'], 0, 5 ) ); ?></td>
								<td><?php echo esc_html( $b['customer_name'] ?? '—' ); ?></td>
								<td><a class="sb-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-bookings&s=' . rawurlencode( (string) $b['booking_code'] ) ) ); ?>"><code translate="no"><?php echo esc_html( $b['booking_code'] ); ?></code></a><br><span class="sb-muted"><?php echo esc_html( $b['service_name'] ); ?></span></td>
								<td class="sb-num"><?php echo esc_html( sb_price( $b['total'] ) ); ?></td>
								<td class="sb-num"><?php echo esc_html( sb_price( $b['paid'] ) ); ?></td>
								<td class="sb-num"><strong><?php echo esc_html( sb_price( (float) $b['total'] - (float) $b['paid'] ) ); ?></strong></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'Nothing outstanding.', 'simple-booking' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	</section>
</div>
