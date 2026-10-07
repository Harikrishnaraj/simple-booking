<?php
/**
 * A customer's booking, opened from the link in their email.
 *
 * @var array|null $booking Booking with service_name, staff_name, can_change and deadline; null if the link is invalid.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$statuses = [
	'pending'   => __( 'Pending confirmation', 'simple-booking' ),
	'confirmed' => __( 'Confirmed', 'simple-booking' ),
	'cancelled' => __( 'Cancelled', 'simple-booking' ),
	'completed' => __( 'Completed', 'simple-booking' ),
];
?>
<div class="sb-booking-wrapper sb-manage" data-sb-manage>
	<h2 class="sb-booking-title"><?php esc_html_e( 'Your booking', 'simple-booking' ); ?></h2>

	<?php if ( ! $booking ) : ?>
		<p class="sb-message sb-message-error"><?php esc_html_e( 'This link is not valid. Please use the link from your most recent email, or contact us.', 'simple-booking' ); ?></p>
	<?php else : ?>
		<dl class="sb-details-list">
			<dt><?php esc_html_e( 'Service', 'simple-booking' ); ?></dt>
			<dd><?php echo esc_html( $booking['service_name'] ); ?></dd>
			<dt><?php esc_html_e( 'When', 'simple-booking' ); ?></dt>
			<dd><?php echo esc_html( mysql2date( get_option( 'date_format' ), $booking['booking_date'] ) . ', ' . substr( $booking['booking_time'], 0, 5 ) . '–' . substr( $booking['end_time'], 0, 5 ) ); ?></dd>
			<?php if ( $booking['staff_name'] ) : ?>
				<dt><?php esc_html_e( 'With', 'simple-booking' ); ?></dt>
				<dd><?php echo esc_html( $booking['staff_name'] ); ?></dd>
			<?php endif; ?>
			<?php if ( $booking['location'] ) : ?>
				<dt><?php esc_html_e( 'Where', 'simple-booking' ); ?></dt>
				<dd><?php echo nl2br( esc_html( $booking['location'] ) ); ?></dd>
			<?php endif; ?>
			<dt><?php esc_html_e( 'Booking code', 'simple-booking' ); ?></dt>
			<dd><code translate="no"><?php echo esc_html( $booking['booking_code'] ); ?></code></dd>
			<dt><?php esc_html_e( 'Status', 'simple-booking' ); ?></dt>
			<dd><?php echo esc_html( $statuses[ $booking['status'] ] ?? $booking['status'] ); ?></dd>
		</dl>

		<?php if ( $booking['can_change'] ) : ?>
			<p class="sb-hint">
				<?php
				/* translators: %s: date and time */
				echo esc_html( sprintf( __( 'You can cancel or reschedule online until %s.', 'simple-booking' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $booking['deadline'] ) ) );
				?>
			</p>

			<div class="sb-actions" data-sb-manage-actions>
				<button type="button" class="sb-btn-secondary" data-sb-manage-cancel><?php esc_html_e( 'Cancel appointment', 'simple-booking' ); ?></button>
				<button type="button" class="sb-btn-primary" data-sb-manage-open aria-expanded="false" aria-controls="sb-manage-reschedule"><?php esc_html_e( 'Choose another time', 'simple-booking' ); ?></button>
			</div>

			<form id="sb-manage-reschedule" class="sb-manage-reschedule" hidden>
				<p class="sb-form-label"><?php esc_html_e( 'Pick a day', 'simple-booking' ); ?></p>
				<div class="sb-days" role="group" aria-label="<?php esc_attr_e( 'Available days', 'simple-booking' ); ?>"></div>
				<p class="sb-form-label" id="sb-manage-times"><?php esc_html_e( 'Pick a time', 'simple-booking' ); ?></p>
				<div class="sb-slots-grid" role="group" aria-labelledby="sb-manage-times" aria-live="polite">
					<p class="sb-hint"><?php esc_html_e( 'Choose a day to see available times.', 'simple-booking' ); ?></p>
				</div>
				<div class="sb-actions">
					<button type="submit" class="sb-btn-primary" disabled><?php esc_html_e( 'Move my appointment', 'simple-booking' ); ?></button>
				</div>
			</form>
		<?php elseif ( in_array( $booking['status'], [ 'pending', 'confirmed' ], true ) ) : ?>
			<p class="sb-hint"><?php esc_html_e( 'To change this appointment, please contact us.', 'simple-booking' ); ?></p>
		<?php endif; ?>

		<div class="sb-message" role="status" aria-live="polite" tabindex="-1" hidden></div>
	<?php endif; ?>
</div>
