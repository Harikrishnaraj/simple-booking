<?php
/**
 * @var string $from     "Y-m-d"
 * @var string $to       "Y-m-d"
 * @var array  $now      SB_Reports::summary() for the range
 * @var array  $before   SB_Reports::summary() for the previous range of equal length
 * @var array  $daily    "Y-m-d" => booking count
 * @var array  $upcoming
 * @var array  $statuses value => label
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$change = static function ( string $key ) use ( $now, $before ): string {
	$pct = SB_Reports::change( (float) $now[ $key ], (float) $before[ $key ] );
	if ( null === $pct ) {
		return '<span class="sb-change">' . esc_html__( 'No earlier data', 'counterslot' ) . '</span>';
	}
	$dir   = $pct > 0 ? 'up' : ( $pct < 0 ? 'down' : 'flat' );
	$arrow = [ 'up' => '▲', 'down' => '▼', 'flat' => '•' ][ $dir ];
	return sprintf(
		'<span class="sb-change sb-change--%1$s"><span aria-hidden="true">%2$s</span> %3$s</span>',
		esc_attr( $dir ),
		$arrow,
		/* translators: %s: signed percentage change, e.g. +12% */
		esc_html( sprintf( __( '%s vs previous period', 'counterslot' ), ( $pct > 0 ? '+' : '' ) . $pct . '%' ) )
	);
};

$hours     = static fn( int $minutes ): string => number_format_i18n( $minutes / 60, $minutes % 60 ? 1 : 0 ) . 'h';
$returning = $now['customers'] - $now['new_customers'];
$max_day   = max( 1, max( $daily ?: [ 0 ] ) );
$date_fmt  = get_option( 'date_format' );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Dashboard', 'counterslot' ), 'theme' => $theme ] ); ?>

	<form class="sb-toolbar" method="get">
		<input type="hidden" name="page" value="sb-dashboard">
		<div class="sb-field-inline">
			<label for="sb-from"><?php esc_html_e( 'From', 'counterslot' ); ?></label>
			<input id="sb-from" class="sb-input" type="date" name="from" value="<?php echo esc_attr( $from ); ?>">
		</div>
		<div class="sb-field-inline">
			<label for="sb-to"><?php esc_html_e( 'To', 'counterslot' ); ?></label>
			<input id="sb-to" class="sb-input" type="date" name="to" value="<?php echo esc_attr( $to ); ?>">
		</div>
		<button type="submit" class="sb-button sb-button--secondary"><?php esc_html_e( 'Apply', 'counterslot' ); ?></button>
	</form>

	<div class="sb-stats">
		<section class="sb-card sb-stat">
			<h2 class="sb-stat__label"><?php esc_html_e( 'Appointments', 'counterslot' ); ?></h2>
			<p class="sb-stat__value"><?php echo esc_html( number_format_i18n( $now['appointments'] ) ); ?></p>
			<?php echo $change( 'appointments' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>
		</section>

		<section class="sb-card sb-stat">
			<h2 class="sb-stat__label"><?php esc_html_e( 'Customers', 'counterslot' ); ?></h2>
			<p class="sb-stat__value"><?php echo esc_html( number_format_i18n( $now['customers'] ) ); ?></p>
			<p class="sb-stat__meta">
				<?php
				/* translators: 1: new customers, 2: returning customers */
				echo esc_html( sprintf( __( '%1$s new · %2$s returning', 'counterslot' ), number_format_i18n( $now['new_customers'] ), number_format_i18n( $returning ) ) );
				?>
			</p>
		</section>

		<section class="sb-card sb-stat">
			<h2 class="sb-stat__label"><?php esc_html_e( 'Occupancy', 'counterslot' ); ?></h2>
			<p class="sb-stat__value"><?php echo esc_html( $now['occupancy'] . '%' ); ?></p>
			<div class="sb-meter" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%d%% of bookable time is booked', 'counterslot' ), $now['occupancy'] ) ); ?>">
				<span style="width: <?php echo (int) $now['occupancy']; ?>%"></span>
			</div>
			<p class="sb-stat__meta">
				<?php
				/* translators: 1: booked hours, 2: bookable hours */
				echo esc_html( sprintf( __( '%1$s booked of %2$s', 'counterslot' ), $hours( $now['booked_minutes'] ), $hours( $now['available_minutes'] ) ) );
				?>
			</p>
		</section>

		<section class="sb-card sb-stat">
			<h2 class="sb-stat__label"><?php esc_html_e( 'Revenue', 'counterslot' ); ?></h2>
			<p class="sb-stat__value"><?php echo esc_html( sb_price( $now['revenue'] ) ); ?></p>
			<?php echo $change( 'revenue' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>
			<p class="sb-stat__meta"><?php esc_html_e( 'Confirmed and completed bookings', 'counterslot' ); ?></p>
			<p class="sb-stat__meta">
				<a class="sb-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-finance&from=' . $from . '&to=' . $to ) ); ?>">
					<?php
					/* translators: %s: amount */
					echo esc_html( sprintf( __( '%s received in payments', 'counterslot' ), sb_price( $now['received'] ) ) );
					?>
				</a>
			</p>
		</section>
	</div>

	<section class="sb-card">
		<div class="sb-card__head">
			<h2 class="sb-card__title"><?php esc_html_e( 'Bookings per day', 'counterslot' ); ?></h2>
			<span class="sb-muted">
				<?php echo esc_html( mysql2date( $date_fmt, $from ) . ' – ' . mysql2date( $date_fmt, $to ) ); ?>
			</span>
		</div>
		<?php if ( array_sum( $daily ) ) : ?>
			<div class="sb-chart" aria-hidden="true">
				<div class="sb-chart__axis">
					<span><?php echo (int) $max_day; ?></span>
					<span>0</span>
				</div>
				<div class="sb-chart__bars">
					<?php foreach ( $daily as $day => $count ) : ?>
						<?php $label = sprintf( _n( '%1$s: %2$d booking', '%1$s: %2$d bookings', $count, 'counterslot' ), mysql2date( $date_fmt, $day ), $count ); ?>
						<a class="sb-chart__bar" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-bookings&from=' . $day . '&to=' . $day ) ); ?>" data-tip="<?php echo esc_attr( $label ); ?>" tabindex="-1">
							<span style="height: <?php echo esc_attr( round( $count / $max_day * 100, 2 ) ); ?>%"></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<details class="sb-details">
				<summary><?php esc_html_e( 'Show as table', 'counterslot' ); ?></summary>
				<table class="sb-table sb-table--compact">
					<thead><tr><th scope="col"><?php esc_html_e( 'Date', 'counterslot' ); ?></th><th scope="col"><?php esc_html_e( 'Bookings', 'counterslot' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $daily as $day => $count ) : ?>
							<tr><td><?php echo esc_html( mysql2date( $date_fmt, $day ) ); ?></td><td><?php echo (int) $count; ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No bookings in this period.', 'counterslot' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="sb-card">
		<div class="sb-card__head">
			<h2 class="sb-card__title"><?php esc_html_e( 'Upcoming appointments', 'counterslot' ); ?></h2>
			<a class="sb-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-bookings&from=' . wp_date( 'Y-m-d' ) ) ); ?>"><?php esc_html_e( 'View all', 'counterslot' ); ?></a>
		</div>
		<?php if ( $upcoming ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'When', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Customer', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Service', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Staff', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'counterslot' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $upcoming as $b ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( mysql2date( $date_fmt, $b['booking_date'] ) ); ?></strong><br>
									<span class="sb-muted"><?php echo esc_html( substr( $b['booking_time'], 0, 5 ) . '–' . substr( $b['end_time'], 0, 5 ) ); ?></span>
								</td>
								<td><?php echo esc_html( $b['customer_name'] ?? '—' ); ?><br><span class="sb-muted"><?php echo esc_html( $b['customer_email'] ?? '' ); ?></span></td>
								<td><?php echo esc_html( $b['service_name'] ?? '—' ); ?></td>
								<td><?php echo esc_html( $b['staff_name'] ?? __( 'Any', 'counterslot' ) ); ?></td>
								<td><span class="sb-badge sb-badge--<?php echo esc_attr( $b['status'] ); ?>"><?php echo esc_html( $statuses[ $b['status'] ] ?? $b['status'] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No upcoming appointments.', 'counterslot' ); ?></p>
		<?php endif; ?>
	</section>
</div>
