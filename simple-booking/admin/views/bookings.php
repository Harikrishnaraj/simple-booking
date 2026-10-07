<?php
/**
 * @var array  $bookings
 * @var array  $filters  search, status, staff_id, date_from, date_to
 * @var int    $total
 * @var int    $page
 * @var int    $pages
 * @var array  $statuses value => label
 * @var array  $staff
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$page_url  = admin_url( 'admin.php?page=sb-bookings' );
$filtered  = (bool) array_filter( $filters );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Bookings', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<form method="get" class="sb-toolbar">
			<input type="hidden" name="page" value="sb-bookings">
			<div class="sb-search">
				<label class="screen-reader-text" for="sb-booking-search"><?php esc_html_e( 'Search bookings', 'simple-booking' ); ?></label>
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<input id="sb-booking-search" class="sb-input" type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Code, customer name or email…', 'simple-booking' ); ?>">
			</div>
			<div class="sb-field-inline">
				<label for="sb-f-from"><?php esc_html_e( 'From', 'simple-booking' ); ?></label>
				<input id="sb-f-from" class="sb-input" type="date" name="from" value="<?php echo esc_attr( $filters['date_from'] ); ?>">
			</div>
			<div class="sb-field-inline">
				<label for="sb-f-to"><?php esc_html_e( 'To', 'simple-booking' ); ?></label>
				<input id="sb-f-to" class="sb-input" type="date" name="to" value="<?php echo esc_attr( $filters['date_to'] ); ?>">
			</div>
			<div class="sb-field-inline">
				<label for="sb-f-status" class="screen-reader-text"><?php esc_html_e( 'Status', 'simple-booking' ); ?></label>
				<select id="sb-f-status" class="sb-input" name="status">
					<option value=""><?php esc_html_e( 'All statuses', 'simple-booking' ); ?></option>
					<?php foreach ( $statuses as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php if ( $staff ) : ?>
				<div class="sb-field-inline">
					<label for="sb-f-staff" class="screen-reader-text"><?php esc_html_e( 'Staff', 'simple-booking' ); ?></label>
					<select id="sb-f-staff" class="sb-input" name="staff">
						<option value=""><?php esc_html_e( 'All staff', 'simple-booking' ); ?></option>
						<?php foreach ( $staff as $member ) : ?>
							<option value="<?php echo (int) $member['id']; ?>" <?php selected( $filters['staff_id'], (int) $member['id'] ); ?>><?php echo esc_html( $member['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<button type="submit" class="sb-button sb-button--secondary"><?php esc_html_e( 'Filter', 'simple-booking' ); ?></button>
			<?php if ( $filtered ) : ?>
				<a class="sb-link" href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Clear', 'simple-booking' ); ?></a>
			<?php endif; ?>
			<button type="button" class="sb-button sb-toolbar__end" data-sb-open="sb-booking-dialog">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Booking', 'simple-booking' ); ?>
			</button>
		</form>

		<p class="sb-muted sb-count">
			<?php
			/* translators: %s: number of bookings */
			echo esc_html( sprintf( _n( '%s booking', '%s bookings', $total, 'simple-booking' ), number_format_i18n( $total ) ) );
			?>
			· <?php echo wp_kses_post( sprintf( __( 'Add the booking form to any page with the %s shortcode.', 'simple-booking' ), '<code>[simple_booking]</code>' ) ); ?>
		</p>

		<?php if ( $bookings ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Code', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'When', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Service', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Staff', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Customer', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Notes', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bookings as $b ) : ?>
							<tr>
								<td><code translate="no"><?php echo esc_html( $b['booking_code'] ); ?></code></td>
								<td>
									<strong><?php echo esc_html( mysql2date( get_option( 'date_format' ), $b['booking_date'] ) ); ?></strong><br>
									<span class="sb-muted"><?php echo esc_html( substr( $b['booking_time'], 0, 5 ) . '–' . substr( $b['end_time'], 0, 5 ) ); ?></span>
								</td>
								<td><?php echo esc_html( $b['service_name'] ?? '—' ); ?></td>
								<td>
									<?php echo esc_html( $b['staff_name'] ?? __( 'Any', 'simple-booking' ) ); ?>
									<?php if ( ! empty( $b['location_name'] ) ) : ?>
										<br><span class="sb-muted"><?php echo esc_html( $b['location_name'] ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( $b['customer_name'] ?? '—' ); ?><br>
									<a class="sb-link" href="<?php echo esc_url( 'mailto:' . $b['customer_email'] ); ?>"><?php echo esc_html( $b['customer_email'] ); ?></a>
									<?php if ( ! empty( $b['customer_phone'] ) ) : ?>
										<br><a class="sb-link" href="<?php echo esc_url( 'tel:' . $b['customer_phone'] ); ?>"><?php echo esc_html( $b['customer_phone'] ); ?></a>
									<?php endif; ?>
								</td>
								<td class="sb-note">
									<?php echo nl2br( esc_html( $b['notes'] ) ); ?>
									<?php $answers = SB_Custom_Fields::as_text( $b['custom_fields'] ?? null ); ?>
									<?php if ( '' !== $answers ) : ?>
										<div class="sb-answers"><?php echo nl2br( esc_html( $answers ) ); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<select class="sb-input sb-status sb-status--<?php echo esc_attr( $b['status'] ); ?>" data-sb-status data-id="<?php echo (int) $b['id']; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Status for booking %s', 'simple-booking' ), $b['booking_code'] ) ); ?>">
										<?php foreach ( $statuses as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $b['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="sb-actions">
									<?php if ( in_array( $b['status'], [ 'pending', 'confirmed' ], true ) ) : ?>
										<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-reschedule-dialog"
											data-sb-fill="<?php echo esc_attr( wp_json_encode( [
												'id'           => (int) $b['id'],
												'service_id'   => (int) $b['service_id'],
												'staff_id'     => (string) ( $b['staff_id'] ?? '' ),
												'booking_date' => $b['booking_date'],
												'current_time' => substr( $b['booking_time'], 0, 5 ),
												'summary'      => implode( ' · ', array_filter( [ $b['booking_code'], $b['service_name'], $b['customer_name'] ] ) ),
											] ) ); ?>"
											aria-label="<?php echo esc_attr( sprintf( __( 'Reschedule booking %s', 'simple-booking' ), $b['booking_code'] ) ); ?>">
											<?php esc_html_e( 'Reschedule', 'simple-booking' ); ?>
										</button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php sb_view( 'admin/views/partials/pagination', [ 'page' => $page, 'pages' => $pages ] ); ?>
		<?php else : ?>
			<p class="sb-empty"><?php echo $filtered ? esc_html__( 'No bookings match these filters.', 'simple-booking' ) : esc_html__( 'No bookings yet.', 'simple-booking' ); ?></p>
		<?php endif; ?>
	</section>
</div>
