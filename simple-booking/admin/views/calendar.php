<?php
/**
 * @var DateTimeImmutable $first      First day of the month shown
 * @var DateTimeImmutable $grid_start First day of the grid (start of its week)
 * @var DateTimeImmutable $grid_end   Last day of the grid
 * @var int               $week_start 0 = Sunday … 6 = Saturday
 * @var int               $staff_id   0 = everyone
 * @var array             $staff
 * @var array             $bookings   "Y-m-d" => bookings
 * @var string[]          $work_days  English day names
 * @var string            $page_url
 * @var string            $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_locale;

$visible   = 3; // bookings listed per day before "+N more"

$month_url = static function ( DateTimeImmutable $month ) use ( $page_url, $staff_id ): string {
	return add_query_arg( array_filter( [ 'month' => $month->format( 'Y-m' ), 'staff' => $staff_id ?: null ] ), $page_url );
};
$today     = wp_date( 'Y-m-d' );
$month_key = $first->format( 'Y-m' );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Calendar', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<div class="sb-toolbar sb-toolbar--split">
			<nav class="sb-cal-nav" aria-label="<?php esc_attr_e( 'Month', 'simple-booking' ); ?>">
				<a class="sb-button sb-button--secondary" href="<?php echo esc_url( $month_url( new DateTimeImmutable( $today, wp_timezone() ) ) ); ?>"><?php esc_html_e( 'Today', 'simple-booking' ); ?></a>
				<a class="sb-icon-button" href="<?php echo esc_url( $month_url( $first->modify( '-1 month' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Previous month', 'simple-booking' ); ?>"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></a>
				<a class="sb-icon-button" href="<?php echo esc_url( $month_url( $first->modify( '+1 month' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Next month', 'simple-booking' ); ?>"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>
				<h2 class="sb-cal-month"><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp() ) ); ?></h2>
			</nav>

			<form method="get" class="sb-field-inline">
				<input type="hidden" name="page" value="sb-calendar">
				<input type="hidden" name="month" value="<?php echo esc_attr( $month_key ); ?>">
				<label for="sb-cal-staff"><?php esc_html_e( 'Staff', 'simple-booking' ); ?></label>
				<select id="sb-cal-staff" class="sb-input" name="staff" data-sb-autosubmit>
					<option value=""><?php esc_html_e( 'Everyone', 'simple-booking' ); ?></option>
					<?php foreach ( $staff as $member ) : ?>
						<option value="<?php echo (int) $member['id']; ?>" <?php selected( $staff_id, (int) $member['id'] ); ?>><?php echo esc_html( $member['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><button type="submit" class="sb-button sb-button--secondary"><?php esc_html_e( 'Filter', 'simple-booking' ); ?></button></noscript>
			</form>
		</div>

		<div class="sb-calendar">
			<div class="sb-calendar__row sb-calendar__weekdays">
				<?php for ( $i = 0; $i < 7; $i++ ) : ?>
					<div class="sb-calendar__weekday"><?php echo esc_html( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( ( $week_start + $i ) % 7 ) ) ); ?></div>
				<?php endfor; ?>
			</div>

			<?php for ( $day = $grid_start; $day <= $grid_end; ) : ?>
				<div class="sb-calendar__row">
					<?php for ( $i = 0; $i < 7; $i++, $day = $day->modify( '+1 day' ) ) : ?>
						<?php
						$key     = $day->format( 'Y-m-d' );
						$items   = $bookings[ $key ] ?? [];
						$classes = [ 'sb-calendar__day' ];
						if ( $day->format( 'Y-m' ) !== $month_key ) {
							$classes[] = 'is-other-month';
						}
						if ( ! in_array( $day->format( 'l' ), $work_days, true ) ) {
							$classes[] = 'is-closed';
						}
						if ( $key === $today ) {
							$classes[] = 'is-today';
						}
						$day_url = admin_url( 'admin.php?page=sb-bookings&from=' . $key . '&to=' . $key . ( $staff_id ? '&staff=' . $staff_id : '' ) );
						?>
						<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
							<a class="sb-calendar__date" href="<?php echo esc_url( $day_url ); ?>"
								aria-label="<?php echo esc_attr( sprintf( _n( '%1$s, %2$d booking', '%1$s, %2$d bookings', count( $items ), 'simple-booking' ), wp_date( get_option( 'date_format' ), $day->getTimestamp() ), count( $items ) ) ); ?>"
								<?php echo $key === $today ? 'aria-current="date"' : ''; ?>>
								<?php echo esc_html( $day->format( 'j' ) ); ?>
							</a>
							<?php foreach ( array_slice( $items, 0, $visible ) as $b ) : ?>
								<a class="sb-event sb-event--<?php echo esc_attr( $b['status'] ); ?>" href="<?php echo esc_url( $day_url ); ?>"
									title="<?php echo esc_attr( implode( ' · ', array_filter( [ substr( $b['booking_time'], 0, 5 ) . '–' . substr( $b['end_time'], 0, 5 ), $b['service_name'], $b['customer_name'], $b['staff_name'] ] ) ) ); ?>">
									<span class="sb-event__time"><?php echo esc_html( substr( $b['booking_time'], 0, 5 ) ); ?></span>
									<span class="sb-event__text"><?php echo esc_html( $b['customer_name'] ?? $b['service_name'] ?? '' ); ?></span>
								</a>
							<?php endforeach; ?>
							<?php if ( count( $items ) > $visible ) : ?>
								<a class="sb-calendar__more" href="<?php echo esc_url( $day_url ); ?>">
									<?php echo esc_html( sprintf( __( '+%d more', 'simple-booking' ), count( $items ) - $visible ) ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endfor; ?>
				</div>
			<?php endfor; ?>
		</div>

		<ul class="sb-legend">
			<li><span class="sb-event sb-event--pending" aria-hidden="true"></span><?php esc_html_e( 'Pending', 'simple-booking' ); ?></li>
			<li><span class="sb-event sb-event--confirmed" aria-hidden="true"></span><?php esc_html_e( 'Confirmed', 'simple-booking' ); ?></li>
			<li><span class="sb-event sb-event--completed" aria-hidden="true"></span><?php esc_html_e( 'Completed', 'simple-booking' ); ?></li>
			<li><span class="sb-event sb-event--cancelled" aria-hidden="true"></span><?php esc_html_e( 'Cancelled', 'simple-booking' ); ?></li>
			<li><span class="sb-legend__closed" aria-hidden="true"></span><?php esc_html_e( 'Closed', 'simple-booking' ); ?></li>
		</ul>
	</section>
</div>
