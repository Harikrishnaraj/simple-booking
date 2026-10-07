<?php
/**
 * @var array $bookings
 * @var int   $page
 * @var int   $pages
 * @var array $statuses value => label
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Bookings', 'simple-booking' ); ?></h1>
	<p><?php echo wp_kses_post( sprintf( __( 'Add the booking form to any page with the %s shortcode.', 'simple-booking' ), '<code>[simple_booking]</code>' ) ); ?></p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Code', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Date', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Time', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Service', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Staff', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Customer', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Notes', 'simple-booking' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $bookings ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No bookings yet.', 'simple-booking' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $bookings as $b ) : ?>
				<tr>
					<td><code translate="no"><?php echo esc_html( $b['booking_code'] ); ?></code></td>
					<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $b['booking_date'] ) ); ?></td>
					<td><?php echo esc_html( substr( $b['booking_time'], 0, 5 ) . '–' . substr( $b['end_time'], 0, 5 ) ); ?></td>
					<td><?php echo esc_html( $b['service_name'] ?? '—' ); ?></td>
					<td><?php echo esc_html( $b['staff_name'] ?? __( 'Any', 'simple-booking' ) ); ?></td>
					<td>
						<?php echo esc_html( $b['customer_name'] ?? '—' ); ?><br>
						<a href="<?php echo esc_url( 'mailto:' . $b['customer_email'] ); ?>"><?php echo esc_html( $b['customer_email'] ); ?></a>
						<?php if ( ! empty( $b['customer_phone'] ) ) : ?>
							<br><a href="<?php echo esc_url( 'tel:' . $b['customer_phone'] ); ?>"><?php echo esc_html( $b['customer_phone'] ); ?></a>
						<?php endif; ?>
					</td>
					<td><?php echo nl2br( esc_html( $b['notes'] ) ); ?></td>
					<td>
						<select data-sb-status data-id="<?php echo (int) $b['id']; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Status for booking %s', 'simple-booking' ), $b['booking_code'] ) ); ?>">
							<?php foreach ( $statuses as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $b['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo wp_kses_post( paginate_links( [
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'current' => $page,
					'total'   => $pages,
				] ) );
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
