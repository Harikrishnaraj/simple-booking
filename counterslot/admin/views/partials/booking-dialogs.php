<?php
/**
 * Add-booking and reschedule dialogs for the Bookings page.
 *
 * @var array $services  Active services
 * @var array $staff     Active staff, each with 'service_ids'
 * @var array $customers Existing customers (newest first)
 * @var array $fields    Custom fields
 * @var array $extras    Paid add-ons
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- included inside cslot_view(), so these are local variables.
$page_url    = admin_url( 'admin.php?page=sb-bookings' );
$staff_field = static function ( string $id ) use ( $staff ): void {
	?>
	<p class="sb-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Staff member', 'counterslot' ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" class="sb-input sb-input--wide" name="staff_id">
			<option value=""><?php esc_html_e( 'Any available', 'counterslot' ); ?></option>
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
			<label for="<?php echo esc_attr( $prefix ); ?>-date"><?php esc_html_e( 'Date', 'counterslot' ); ?></label>
			<input id="<?php echo esc_attr( $prefix ); ?>-date" class="sb-input sb-input--wide" type="date" name="booking_date" required min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
		</p>
		<p class="sb-field">
			<label for="<?php echo esc_attr( $prefix ); ?>-time"><?php esc_html_e( 'Time', 'counterslot' ); ?></label>
			<select id="<?php echo esc_attr( $prefix ); ?>-time" class="sb-input sb-input--wide" name="booking_time" required aria-describedby="<?php echo esc_attr( $prefix ); ?>-time-hint">
				<option value=""><?php esc_html_e( 'Pick a date first', 'counterslot' ); ?></option>
			</select>
		</p>
	</div>
	<p class="sb-hint" id="<?php echo esc_attr( $prefix ); ?>-time-hint"><?php esc_html_e( 'Only free times within your business hours are listed.', 'counterslot' ); ?></p>
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
	<form data-sb-action="cslot_admin_create_booking" data-sb-redirect="<?php echo esc_url( $page_url ); ?>" data-sb-slots>
		<div class="sb-dialog__head">
			<h2 id="sb-booking-dialog-title" data-new="<?php esc_attr_e( 'Book appointment', 'counterslot' ); ?>" data-edit="<?php esc_attr_e( 'Book appointment', 'counterslot' ); ?>"><?php esc_html_e( 'Book appointment', 'counterslot' ); ?></h2>
			<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'counterslot' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</div>
		<input type="hidden" name="id" value="0">

		<fieldset class="sb-fieldset">
			<legend class="sb-label"><?php esc_html_e( 'Customer', 'counterslot' ); ?></legend>
			<p class="sb-field">
				<label for="sb-b-email"><?php esc_html_e( 'Email', 'counterslot' ); ?></label>
				<input id="sb-b-email" class="sb-input sb-input--wide" type="email" name="email" required maxlength="191" list="sb-customer-list" autocomplete="off" spellcheck="false" data-sb-customer-email>
				<span class="sb-hint"><?php esc_html_e( 'Start typing to pick an existing customer. Existing customers keep their saved name and phone.', 'counterslot' ); ?></span>
			</p>
			<div class="sb-field-row">
				<p class="sb-field">
					<label for="sb-b-name"><?php esc_html_e( 'Name', 'counterslot' ); ?></label>
					<input id="sb-b-name" class="sb-input sb-input--wide" type="text" name="name" required maxlength="191" autocomplete="off">
				</p>
				<p class="sb-field">
					<label for="sb-b-phone"><?php esc_html_e( 'Phone', 'counterslot' ); ?></label>
					<input id="sb-b-phone" class="sb-input sb-input--wide" type="tel" name="phone" maxlength="50" autocomplete="off">
				</p>
			</div>
		</fieldset>

		<p class="sb-field">
			<label for="sb-b-service"><?php esc_html_e( 'Service', 'counterslot' ); ?></label>
			<select id="sb-b-service" class="sb-input sb-input--wide" name="service_id" required>
				<?php foreach ( $services as $s ) : ?>
					<option value="<?php echo (int) $s['id']; ?>"><?php echo esc_html( sprintf( '%s (%d min, %s)', $s['name'], (int) $s['duration'], cslot_price( $s['price'] ) ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php $staff_field( 'sb-b-staff' ); ?>
		<?php $when_fields( 'sb-b' ); ?>

		<div class="sb-field-row">
			<p class="sb-field">
				<label for="sb-b-repeat"><?php esc_html_e( 'Repeat', 'counterslot' ); ?></label>
				<select id="sb-b-repeat" class="sb-input sb-input--wide" name="repeat_weeks">
					<option value="0"><?php esc_html_e( 'Does not repeat', 'counterslot' ); ?></option>
					<option value="1"><?php esc_html_e( 'Every week', 'counterslot' ); ?></option>
					<option value="2"><?php esc_html_e( 'Every 2 weeks', 'counterslot' ); ?></option>
					<option value="4"><?php esc_html_e( 'Every 4 weeks', 'counterslot' ); ?></option>
				</select>
			</p>
			<p class="sb-field" data-sb-repeat-count hidden>
				<label for="sb-b-repeat-count"><?php esc_html_e( 'Number of sessions', 'counterslot' ); ?></label>
				<input id="sb-b-repeat-count" class="sb-input sb-input--wide" type="number" name="repeat_count" min="2" max="52" value="6">
			</p>
		</div>
		<p class="sb-hint" data-sb-repeat-count hidden><?php esc_html_e( 'Each session is at the same time. Dates that aren\'t free are skipped and listed after booking. The customer gets one email listing all sessions.', 'counterslot' ); ?></p>

		<div class="sb-field-row">
			<p class="sb-field">
				<label for="sb-b-status"><?php esc_html_e( 'Status', 'counterslot' ); ?></label>
				<select id="sb-b-status" class="sb-input sb-input--wide" name="status">
					<option value="confirmed"><?php esc_html_e( 'Confirmed', 'counterslot' ); ?></option>
					<option value="pending"><?php esc_html_e( 'Pending', 'counterslot' ); ?></option>
				</select>
			</p>
			<p class="sb-field sb-field--check">
				<label><input type="checkbox" name="notify" value="1" checked> <?php esc_html_e( 'Email the customer', 'counterslot' ); ?></label>
			</p>
		</div>
		<?php if ( $extras ) : ?>
			<fieldset class="sb-field">
				<legend class="sb-label"><?php esc_html_e( 'Extras', 'counterslot' ); ?></legend>
				<?php foreach ( $extras as $extra ) : ?>
					<label class="sb-check-label" data-sb-field data-services="<?php echo esc_attr( implode( ',', $extra['services'] ) ); ?>">
						<input type="checkbox" name="extras[]" value="<?php echo esc_attr( $extra['id'] ); ?>">
						<?php echo esc_html( $extra['name'] . ' (+' . cslot_price( $extra['price'] ) . ')' ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php endif; ?>
		<p class="sb-field">
			<label for="sb-b-coupon"><?php esc_html_e( 'Coupon code (optional)', 'counterslot' ); ?></label>
			<input id="sb-b-coupon" class="sb-input sb-input--wide" type="text" name="coupon" maxlength="50" autocomplete="off" spellcheck="false">
		</p>
		<?php
		cslot_view( 'admin/views/partials/custom-field-inputs', [
			'fields'       => $fields,
			'id_prefix'    => 'sb-b-cf',
			'group_class'  => 'sb-field',
			'input_class'  => 'sb-input sb-input--wide',
			'use_required' => false,
		] );
		?>
		<p class="sb-field">
			<label for="sb-b-notes"><?php esc_html_e( 'Notes', 'counterslot' ); ?></label>
			<textarea id="sb-b-notes" class="sb-input" name="notes" rows="2" maxlength="2000"></textarea>
		</p>

		<div class="sb-dialog__foot">
			<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'counterslot' ); ?></button>
			<button type="submit" class="sb-button"><?php esc_html_e( 'Book', 'counterslot' ); ?></button>
		</div>
	</form>
</dialog>

<dialog id="sb-reschedule-dialog" class="sb-dialog" aria-labelledby="sb-reschedule-dialog-title">
	<form data-sb-action="cslot_admin_reschedule" data-sb-redirect="" data-sb-slots>
		<div class="sb-dialog__head">
			<h2 id="sb-reschedule-dialog-title" data-new="<?php esc_attr_e( 'Reschedule', 'counterslot' ); ?>" data-edit="<?php esc_attr_e( 'Reschedule', 'counterslot' ); ?>"><?php esc_html_e( 'Reschedule', 'counterslot' ); ?></h2>
			<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'counterslot' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</div>
		<input type="hidden" name="id" value="0">
		<input type="hidden" name="service_id" value="">
		<input type="hidden" name="current_time" value="">
		<p class="sb-field">
			<label for="sb-r-summary"><?php esc_html_e( 'Booking', 'counterslot' ); ?></label>
			<input id="sb-r-summary" class="sb-input sb-input--wide" type="text" name="summary" readonly>
		</p>
		<?php $staff_field( 'sb-r-staff' ); ?>
		<?php $when_fields( 'sb-r' ); ?>
		<p class="sb-field sb-field--check">
			<label><input type="checkbox" name="notify" value="1" checked> <?php esc_html_e( 'Email the customer the new time', 'counterslot' ); ?></label>
		</p>
		<div class="sb-dialog__foot">
			<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'counterslot' ); ?></button>
			<button type="submit" class="sb-button"><?php esc_html_e( 'Move booking', 'counterslot' ); ?></button>
		</div>
	</form>
</dialog>

<dialog id="sb-payment-dialog" class="sb-dialog" aria-labelledby="sb-payment-dialog-title">
	<form data-sb-action="cslot_add_payment" data-sb-redirect="" data-sb-payment-form>
		<div class="sb-dialog__head">
			<h2 id="sb-payment-dialog-title" data-new="<?php esc_attr_e( 'Payments', 'counterslot' ); ?>" data-edit="<?php esc_attr_e( 'Payments', 'counterslot' ); ?>"><?php esc_html_e( 'Payments', 'counterslot' ); ?></h2>
			<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'counterslot' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</div>
		<input type="hidden" name="id" value="0">
		<p class="sb-field"><input class="sb-input sb-input--wide" type="text" name="summary" readonly aria-label="<?php esc_attr_e( 'Booking', 'counterslot' ); ?>"></p>
		<ul class="sb-payment-history" data-sb-payment-history></ul>
		<p><a class="sb-link" href="#" target="_blank" rel="noopener" data-sb-invoice-link><span class="dashicons dashicons-media-text" aria-hidden="true"></span><?php esc_html_e( 'Open invoice', 'counterslot' ); ?></a></p>
		<fieldset class="sb-fieldset">
			<legend class="sb-label"><?php esc_html_e( 'Record a payment', 'counterslot' ); ?></legend>
			<div class="sb-field-row">
				<p class="sb-field"><label for="sb-p-amount"><?php esc_html_e( 'Amount', 'counterslot' ); ?></label><input id="sb-p-amount" class="sb-input sb-input--wide" type="number" name="amount" step="0.01" required></p>
				<p class="sb-field"><label for="sb-p-method"><?php esc_html_e( 'Method', 'counterslot' ); ?></label>
					<select id="sb-p-method" class="sb-input sb-input--wide" name="method">
						<?php foreach ( CSlot_Payments::methods() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></p>
			</div>
			<div class="sb-field-row">
				<p class="sb-field"><label for="sb-p-date"><?php esc_html_e( 'Date', 'counterslot' ); ?></label><input id="sb-p-date" class="sb-input sb-input--wide" type="date" name="paid_at" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></p>
				<p class="sb-field"><label for="sb-p-note"><?php esc_html_e( 'Note (optional)', 'counterslot' ); ?></label><input id="sb-p-note" class="sb-input sb-input--wide" type="text" name="note" maxlength="191"></p>
			</div>
			<p class="sb-hint"><?php esc_html_e( 'Use a negative amount for a refund.', 'counterslot' ); ?></p>
		</fieldset>
		<div class="sb-dialog__foot">
			<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Close', 'counterslot' ); ?></button>
			<button type="submit" class="sb-button"><?php esc_html_e( 'Record payment', 'counterslot' ); ?></button>
		</div>
	</form>
</dialog>
</div>
