<?php
/**
 * Add-booking and reschedule dialogs for the Bookings page.
 *
 * @var array $services  Active services
 * @var array $staff     Active staff, each with 'service_ids'
 * @var array $customers Existing customers (newest first)
 * @var array $fields    Custom fields
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$page_url    = admin_url( 'admin.php?page=sb-bookings' );
$staff_field = static function ( string $id ) use ( $staff ): void {
	?>
	<p class="sb-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Staff member', 'simple-booking' ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" class="sb-input sb-input--wide" name="staff_id">
			<option value=""><?php esc_html_e( 'Any available', 'simple-booking' ); ?></option>
			<?php foreach ( $staff as $member ) : ?>
				<option value="<?php echo (int) $member['id']; ?>" data-services="<?php echo esc_attr( implode( ',', $member['service_ids'] ) ); ?>"><?php echo esc_html( $member['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php
};
$when_fields = static function ( string $prefix ): void {
	?>
	<div class="sb-field-row">
		<p class="sb-field">
			<label for="<?php echo esc_attr( $prefix ); ?>-date"><?php esc_html_e( 'Date', 'simple-booking' ); ?></label>
			<input id="<?php echo esc_attr( $prefix ); ?>-date" class="sb-input sb-input--wide" type="date" name="booking_date" required min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
		</p>
		<p class="sb-field">
			<label for="<?php echo esc_attr( $prefix ); ?>-time"><?php esc_html_e( 'Time', 'simple-booking' ); ?></label>
			<select id="<?php echo esc_attr( $prefix ); ?>-time" class="sb-input sb-input--wide" name="booking_time" required aria-describedby="<?php echo esc_attr( $prefix ); ?>-time-hint">
				<option value=""><?php esc_html_e( 'Pick a date first', 'simple-booking' ); ?></option>
			</select>
		</p>
	</div>
	<p class="sb-hint" id="<?php echo esc_attr( $prefix ); ?>-time-hint"><?php esc_html_e( 'Only free times within your business hours are listed.', 'simple-booking' ); ?></p>
	<?php
};
?>
<div class="sb-app">
<datalist id="sb-customer-list">
	<?php foreach ( $customers as $c ) : ?>
		<option value="<?php echo esc_attr( $c['email'] ); ?>" label="<?php echo esc_attr( $c['name'] ); ?>" data-name="<?php echo esc_attr( $c['name'] ); ?>" data-phone="<?php echo esc_attr( $c['phone'] ); ?>"></option>
	<?php endforeach; ?>
</datalist>

<dialog id="sb-booking-dialog" class="sb-dialog sb-dialog--wide" aria-labelledby="sb-booking-dialog-title">
	<form data-sb-action="sb_admin_create_booking" data-sb-redirect="<?php echo esc_url( $page_url ); ?>" data-sb-slots>
		<div class="sb-dialog__head">
			<h2 id="sb-booking-dialog-title" data-new="<?php esc_attr_e( 'Book appointment', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Book appointment', 'simple-booking' ); ?>"><?php esc_html_e( 'Book appointment', 'simple-booking' ); ?></h2>
			<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</div>
		<input type="hidden" name="id" value="0">

		<fieldset class="sb-fieldset">
			<legend class="sb-label"><?php esc_html_e( 'Customer', 'simple-booking' ); ?></legend>
			<p class="sb-field">
				<label for="sb-b-email"><?php esc_html_e( 'Email', 'simple-booking' ); ?></label>
				<input id="sb-b-email" class="sb-input sb-input--wide" type="email" name="email" required maxlength="191" list="sb-customer-list" autocomplete="off" spellcheck="false" data-sb-customer-email>
				<span class="sb-hint"><?php esc_html_e( 'Start typing to pick an existing customer. Existing customers keep their saved name and phone.', 'simple-booking' ); ?></span>
			</p>
			<div class="sb-field-row">
				<p class="sb-field">
					<label for="sb-b-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
					<input id="sb-b-name" class="sb-input sb-input--wide" type="text" name="name" required maxlength="191" autocomplete="off">
				</p>
				<p class="sb-field">
					<label for="sb-b-phone"><?php esc_html_e( 'Phone', 'simple-booking' ); ?></label>
					<input id="sb-b-phone" class="sb-input sb-input--wide" type="tel" name="phone" maxlength="50" autocomplete="off">
				</p>
			</div>
		</fieldset>

		<p class="sb-field">
			<label for="sb-b-service"><?php esc_html_e( 'Service', 'simple-booking' ); ?></label>
			<select id="sb-b-service" class="sb-input sb-input--wide" name="service_id" required>
				<?php foreach ( $services as $s ) : ?>
					<option value="<?php echo (int) $s['id']; ?>"><?php echo esc_html( sprintf( '%s (%d min, %s)', $s['name'], (int) $s['duration'], sb_price( $s['price'] ) ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php $staff_field( 'sb-b-staff' ); ?>
		<?php $when_fields( 'sb-b' ); ?>

		<div class="sb-field-row">
			<p class="sb-field">
				<label for="sb-b-repeat"><?php esc_html_e( 'Repeat', 'simple-booking' ); ?></label>
				<select id="sb-b-repeat" class="sb-input sb-input--wide" name="repeat_weeks">
					<option value="0"><?php esc_html_e( 'Does not repeat', 'simple-booking' ); ?></option>
					<option value="1"><?php esc_html_e( 'Every week', 'simple-booking' ); ?></option>
					<option value="2"><?php esc_html_e( 'Every 2 weeks', 'simple-booking' ); ?></option>
					<option value="4"><?php esc_html_e( 'Every 4 weeks', 'simple-booking' ); ?></option>
				</select>
			</p>
			<p class="sb-field" data-sb-repeat-count hidden>
				<label for="sb-b-repeat-count"><?php esc_html_e( 'Number of sessions', 'simple-booking' ); ?></label>
				<input id="sb-b-repeat-count" class="sb-input sb-input--wide" type="number" name="repeat_count" min="2" max="52" value="6">
			</p>
		</div>
		<p class="sb-hint" data-sb-repeat-count hidden><?php esc_html_e( 'Each session is at the same time. Dates that aren\'t free are skipped and listed after booking. The customer gets one email listing all sessions.', 'simple-booking' ); ?></p>

		<div class="sb-field-row">
			<p class="sb-field">
				<label for="sb-b-status"><?php esc_html_e( 'Status', 'simple-booking' ); ?></label>
				<select id="sb-b-status" class="sb-input sb-input--wide" name="status">
					<option value="confirmed"><?php esc_html_e( 'Confirmed', 'simple-booking' ); ?></option>
					<option value="pending"><?php esc_html_e( 'Pending', 'simple-booking' ); ?></option>
				</select>
			</p>
			<p class="sb-field sb-field--check">
				<label><input type="checkbox" name="notify" value="1" checked> <?php esc_html_e( 'Email the customer', 'simple-booking' ); ?></label>
			</p>
		</div>
		<?php
		sb_view( 'admin/views/partials/custom-field-inputs', [
			'fields'       => $fields,
			'id_prefix'    => 'sb-b-cf',
			'group_class'  => 'sb-field',
			'input_class'  => 'sb-input sb-input--wide',
			'use_required' => false,
		] );
		?>
		<p class="sb-field">
			<label for="sb-b-notes"><?php esc_html_e( 'Notes', 'simple-booking' ); ?></label>
			<textarea id="sb-b-notes" class="sb-input" name="notes" rows="2" maxlength="2000"></textarea>
		</p>

		<div class="sb-dialog__foot">
			<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></button>
			<button type="submit" class="sb-button"><?php esc_html_e( 'Book', 'simple-booking' ); ?></button>
		</div>
	</form>
</dialog>

<dialog id="sb-reschedule-dialog" class="sb-dialog" aria-labelledby="sb-reschedule-dialog-title">
	<form data-sb-action="sb_admin_reschedule" data-sb-redirect="" data-sb-slots>
		<div class="sb-dialog__head">
			<h2 id="sb-reschedule-dialog-title" data-new="<?php esc_attr_e( 'Reschedule', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Reschedule', 'simple-booking' ); ?>"><?php esc_html_e( 'Reschedule', 'simple-booking' ); ?></h2>
			<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</div>
		<input type="hidden" name="id" value="0">
		<input type="hidden" name="service_id" value="">
		<input type="hidden" name="current_time" value="">
		<p class="sb-field">
			<label for="sb-r-summary"><?php esc_html_e( 'Booking', 'simple-booking' ); ?></label>
			<input id="sb-r-summary" class="sb-input sb-input--wide" type="text" name="summary" readonly>
		</p>
		<?php $staff_field( 'sb-r-staff' ); ?>
		<?php $when_fields( 'sb-r' ); ?>
		<p class="sb-field sb-field--check">
			<label><input type="checkbox" name="notify" value="1" checked> <?php esc_html_e( 'Email the customer the new time', 'simple-booking' ); ?></label>
		</p>
		<div class="sb-dialog__foot">
			<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></button>
			<button type="submit" class="sb-button"><?php esc_html_e( 'Move booking', 'simple-booking' ); ?></button>
		</div>
	</form>
</dialog>
</div>
