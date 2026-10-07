<?php
/**
 * @var array  $events    SB_Events::get_all() with 'taken'
 * @var array  $attendees event id => registrations
 * @var array  $locations
 * @var array  $staff
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$date_fmt = get_option( 'date_format' );
$today    = wp_date( 'Y-m-d' );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Events', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<div class="sb-toolbar sb-toolbar--split">
			<p class="sb-muted sb-toolbar__text">
				<?php
				echo wp_kses_post( sprintf(
					/* translators: %s: shortcode */
					__( 'Group sessions with a limited number of places. List them on any page with %s. A host is not bookable for appointments during their event.', 'simple-booking' ),
					'<code>[simple_booking_events]</code>'
				) );
				?>
			</p>
			<button type="button" class="sb-button" data-sb-open="sb-event-dialog"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Event', 'simple-booking' ); ?></button>
		</div>

		<?php if ( $events ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Event', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'When', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Places', 'simple-booking' ); ?></th>
						<th scope="col" class="sb-num"><?php esc_html_e( 'Price', 'simple-booking' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $events as $e ) : ?>
							<?php $past = $e['event_date'] < $today; ?>
							<tr class="<?php echo $past ? 'is-past' : ''; ?>">
								<td>
									<strong><?php echo esc_html( $e['name'] ); ?></strong>
									<?php if ( $e['location_name'] || $e['staff_name'] ) : ?>
										<br><span class="sb-muted"><?php echo esc_html( implode( ' · ', array_filter( [ $e['location_name'], $e['staff_name'] ] ) ) ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( mysql2date( $date_fmt, $e['event_date'] ) ); ?><br><span class="sb-muted"><?php echo esc_html( substr( $e['start_time'], 0, 5 ) . '–' . substr( $e['end_time'], 0, 5 ) ); ?></span></td>
								<td>
									<?php echo esc_html( (int) $e['taken'] . ' / ' . (int) $e['capacity'] ); ?>
									<div class="sb-meter sb-meter--small" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d places taken', 'simple-booking' ), (int) $e['taken'], (int) $e['capacity'] ) ); ?>"><span style="width: <?php echo (int) min( 100, round( (int) $e['taken'] / max( 1, (int) $e['capacity'] ) * 100 ) ); ?>%"></span></div>
								</td>
								<td class="sb-num"><?php echo (float) $e['price'] > 0 ? esc_html( sb_price( $e['price'] ) ) : esc_html__( 'Free', 'simple-booking' ); ?></td>
								<td>
									<?php if ( 'cancelled' === $e['status'] ) : ?>
										<span class="sb-badge sb-badge--cancelled"><?php esc_html_e( 'Cancelled', 'simple-booking' ); ?></span>
									<?php else : ?>
										<?php sb_view( 'admin/views/partials/active-badge', [ 'active' => 'active' === $e['status'] ] ); ?>
									<?php endif; ?>
								</td>
								<td class="sb-actions">
									<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-attendees-dialog"
										data-sb-fill="<?php echo esc_attr( wp_json_encode( [ 'id' => (int) $e['id'], 'summary' => $e['name'] . ' · ' . mysql2date( $date_fmt, $e['event_date'] ), 'attendees' => array_map( fn( $r ) => [ 'id' => (int) $r['id'], 'text' => $r['customer_name'] . ' · ' . $r['customer_email'] . ( $r['customer_phone'] ? ' · ' . $r['customer_phone'] : '' ) . ' · ' . sprintf( _n( '%d place', '%d places', (int) $r['spots'], 'simple-booking' ), (int) $r['spots'] ) . ' · ' . $r['code'], 'active' => 'registered' === $r['status'] ], $attendees[ $e['id'] ] ?? [] ) ] ) ); ?>">
										<?php echo esc_html( sprintf( __( 'Attendees (%d)', 'simple-booking' ), count( array_filter( $attendees[ $e['id'] ] ?? [], fn( $r ) => 'registered' === $r['status'] ) ) ) ); ?>
									</button>
									<?php if ( 'cancelled' !== $e['status'] ) : ?>
										<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-event-dialog"
											data-sb-fill="<?php echo esc_attr( wp_json_encode( [ 'id' => (int) $e['id'], 'name' => $e['name'], 'description' => $e['description'], 'event_date' => $e['event_date'], 'start_time' => substr( $e['start_time'], 0, 5 ), 'end_time' => substr( $e['end_time'], 0, 5 ), 'capacity' => (int) $e['capacity'], 'price' => (float) $e['price'], 'location_id' => (string) ( $e['location_id'] ?? '' ), 'staff_id' => (string) ( $e['staff_id'] ?? '' ), 'status' => $e['status'] ] ) ); ?>"><?php esc_html_e( 'Edit', 'simple-booking' ); ?></button>
										<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_cancel_event" data-id="<?php echo (int) $e['id']; ?>"
											data-sb-confirm="<?php esc_attr_e( 'Cancel this event? Everyone registered is emailed that it is cancelled.', 'simple-booking' ); ?>"><?php esc_html_e( 'Cancel event', 'simple-booking' ); ?></button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No events yet.', 'simple-booking' ); ?></p>
		<?php endif; ?>
	</section>

	<dialog id="sb-event-dialog" class="sb-dialog sb-dialog--wide" aria-labelledby="sb-event-dialog-title">
		<form data-sb-action="sb_save_event" data-sb-redirect="">
			<div class="sb-dialog__head">
				<h2 id="sb-event-dialog-title" data-new="<?php esc_attr_e( 'Add event', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Edit event', 'simple-booking' ); ?>"><?php esc_html_e( 'Add event', 'simple-booking' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="0">
			<p class="sb-field"><label for="sb-e-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label><input id="sb-e-name" class="sb-input sb-input--wide" type="text" name="name" required maxlength="191"></p>
			<p class="sb-field"><label for="sb-e-desc"><?php esc_html_e( 'Description (optional)', 'simple-booking' ); ?></label><textarea id="sb-e-desc" class="sb-input" name="description" rows="3"></textarea></p>
			<div class="sb-field-row">
				<p class="sb-field"><label for="sb-e-date"><?php esc_html_e( 'Date', 'simple-booking' ); ?></label><input id="sb-e-date" class="sb-input sb-input--wide" type="date" name="event_date" required></p>
				<div class="sb-field-row">
					<p class="sb-field"><label for="sb-e-start"><?php esc_html_e( 'Start', 'simple-booking' ); ?></label><input id="sb-e-start" class="sb-input sb-input--wide" type="time" name="start_time" required></p>
					<p class="sb-field"><label for="sb-e-end"><?php esc_html_e( 'End', 'simple-booking' ); ?></label><input id="sb-e-end" class="sb-input sb-input--wide" type="time" name="end_time" required></p>
				</div>
			</div>
			<div class="sb-field-row">
				<p class="sb-field"><label for="sb-e-capacity"><?php esc_html_e( 'Places', 'simple-booking' ); ?></label><input id="sb-e-capacity" class="sb-input sb-input--wide" type="number" name="capacity" min="1" step="1" required value="10"></p>
				<p class="sb-field"><label for="sb-e-price"><?php esc_html_e( 'Price per place', 'simple-booking' ); ?></label><input id="sb-e-price" class="sb-input sb-input--wide" type="number" name="price" min="0" step="0.01" value="0"></p>
			</div>
			<div class="sb-field-row">
				<p class="sb-field"><label for="sb-e-location"><?php esc_html_e( 'Location', 'simple-booking' ); ?></label>
					<select id="sb-e-location" class="sb-input sb-input--wide" name="location_id">
						<option value=""><?php esc_html_e( 'None', 'simple-booking' ); ?></option>
						<?php foreach ( $locations as $l ) : ?><option value="<?php echo (int) $l['id']; ?>"><?php echo esc_html( $l['name'] ); ?></option><?php endforeach; ?>
					</select></p>
				<p class="sb-field"><label for="sb-e-staff"><?php esc_html_e( 'Host', 'simple-booking' ); ?></label>
					<select id="sb-e-staff" class="sb-input sb-input--wide" name="staff_id">
						<option value=""><?php esc_html_e( 'None', 'simple-booking' ); ?></option>
						<?php foreach ( $staff as $m ) : ?><option value="<?php echo (int) $m['id']; ?>"><?php echo esc_html( $m['name'] ); ?></option><?php endforeach; ?>
					</select></p>
			</div>
			<p class="sb-field"><label for="sb-e-status"><?php esc_html_e( 'Status', 'simple-booking' ); ?></label>
				<select id="sb-e-status" class="sb-input" name="status">
					<option value="active"><?php esc_html_e( 'Active (open for registration)', 'simple-booking' ); ?></option>
					<option value="inactive"><?php esc_html_e( 'Inactive (hidden)', 'simple-booking' ); ?></option>
				</select></p>
			<div class="sb-dialog__foot">
				<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></button>
				<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'simple-booking' ); ?></button>
			</div>
		</form>
	</dialog>

	<dialog id="sb-attendees-dialog" class="sb-dialog sb-dialog--wide" aria-labelledby="sb-attendees-dialog-title">
		<form data-sb-attendees-form>
			<div class="sb-dialog__head">
				<h2 id="sb-attendees-dialog-title" data-new="<?php esc_attr_e( 'Attendees', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Attendees', 'simple-booking' ); ?>"><?php esc_html_e( 'Attendees', 'simple-booking' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="0">
			<p class="sb-field"><input class="sb-input sb-input--wide" type="text" name="summary" readonly aria-label="<?php esc_attr_e( 'Event', 'simple-booking' ); ?>"></p>
			<ul class="sb-payment-history" data-sb-attendee-list></ul>
			<p class="sb-empty" data-sb-attendee-empty hidden><?php esc_html_e( 'Nobody has registered yet.', 'simple-booking' ); ?></p>
		</form>
	</dialog>
</div>
