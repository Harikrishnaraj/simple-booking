<?php
/**
 * @var array  $settings
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- included inside cslot_view(), so these are local variables.
global $wp_locale;
?>
<div class="wrap sb-app">
	<?php cslot_view( 'admin/views/partials/header', [ 'title' => __( 'Settings', 'counterslot' ), 'theme' => $theme ] ); ?>

	<form class="sb-card" data-sb-action="cslot_save_settings">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="sb-business-name"><?php esc_html_e( 'Business name', 'counterslot' ); ?></label></th>
				<td>
					<input id="sb-business-name" class="regular-text" name="settings[business_name]" type="text" value="<?php echo esc_attr( $settings['business_name'] ); ?>">
					<p class="description"><a href="<?php echo esc_url( CSlot_Setup::url() ); ?>"><?php esc_html_e( 'Run the setup wizard again', 'counterslot' ); ?></a></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-staff-label"><?php esc_html_e( 'Staff are called', 'counterslot' ); ?></label></th>
				<td>
					<input id="sb-staff-label" class="regular-text" name="settings[staff_label]" type="text" maxlength="40" value="<?php echo esc_attr( $settings['staff_label'] ); ?>" placeholder="<?php esc_attr_e( 'Staff member', 'counterslot' ); ?>">
					<p class="description"><?php esc_html_e( 'The word customers see on the booking form, e.g. "Doctor", "Stylist" or "Tutor".', 'counterslot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Business hours', 'counterslot' ); ?></th>
				<td>
					<label for="sb-hours-start" class="screen-reader-text"><?php esc_html_e( 'Opening time', 'counterslot' ); ?></label>
					<input id="sb-hours-start" name="settings[business_hours_start]" type="time" required value="<?php echo esc_attr( $settings['business_hours_start'] ); ?>">
					–
					<label for="sb-hours-end" class="screen-reader-text"><?php esc_html_e( 'Closing time', 'counterslot' ); ?></label>
					<input id="sb-hours-end" name="settings[business_hours_end]" type="time" required value="<?php echo esc_attr( $settings['business_hours_end'] ); ?>">
					<p class="description"><?php echo esc_html( sprintf( /* translators: timezone name, e.g. Asia/Kolkata */ __( 'Times are in the site timezone (%s).', 'counterslot' ), wp_timezone_string() ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Working days', 'counterslot' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Working days', 'counterslot' ); ?></legend>
						<input type="hidden" name="settings[work_days][]" value="">
						<?php foreach ( CSlot_Settings::WEEK_DAYS as $day ) : ?>
							<label>
								<input type="checkbox" name="settings[work_days][]" value="<?php echo esc_attr( $day ); ?>" <?php checked( in_array( $day, (array) $settings['work_days'], true ) ); ?>>
								<?php echo esc_html( $wp_locale->get_weekday( (int) gmdate( 'w', strtotime( $day ) ) ) ); ?>
							</label><br>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-slot-duration"><?php esc_html_e( 'Slot interval (minutes)', 'counterslot' ); ?></label></th>
				<td>
					<input id="sb-slot-duration" class="small-text" name="settings[slot_duration]" type="number" min="5" step="5" required value="<?php echo (int) $settings['slot_duration']; ?>">
					<p class="description"><?php esc_html_e( 'How often a new start time is offered, e.g. every 30 minutes.', 'counterslot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-currency"><?php esc_html_e( 'Currency symbol', 'counterslot' ); ?></label></th>
				<td><input id="sb-currency" class="small-text" name="settings[currency_symbol]" type="text" maxlength="5" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-admin-email"><?php esc_html_e( 'Notification email', 'counterslot' ); ?></label></th>
				<td><input id="sb-admin-email" class="regular-text" name="settings[admin_email]" type="email" spellcheck="false" required value="<?php echo esc_attr( $settings['admin_email'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Emails', 'counterslot' ); ?></th>
				<td>
					<?php
					echo wp_kses_post( sprintf(
						/* translators: %s: link to the Notifications page */
						__( 'Choose which emails are sent, edit their text and set up reminders on the <a href="%s">Notifications</a> page.', 'counterslot' ),
						esc_url( admin_url( 'admin.php?page=sb-notifications' ) )
					) );
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-business-address"><?php esc_html_e( 'Business address', 'counterslot' ); ?></label></th>
				<td><textarea id="sb-business-address" class="regular-text" rows="3" name="settings[business_address]"><?php echo esc_textarea( $settings['business_address'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Printed on invoices.', 'counterslot' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-tax-id"><?php esc_html_e( 'Tax number', 'counterslot' ); ?></label></th>
				<td><input id="sb-tax-id" class="regular-text" type="text" name="settings[tax_id]" maxlength="50" value="<?php echo esc_attr( $settings['tax_id'] ); ?>">
					<p class="description"><?php esc_html_e( 'E.g. your GSTIN, printed on invoices.', 'counterslot' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-invoice-prefix"><?php esc_html_e( 'Invoice number prefix', 'counterslot' ); ?></label></th>
				<td><input id="sb-invoice-prefix" class="small-text" type="text" name="settings[invoice_prefix]" maxlength="10" value="<?php echo esc_attr( $settings['invoice_prefix'] ); ?>">
					<p class="description"><?php esc_html_e( 'Invoices are numbered in order the first time each is opened, e.g. INV-0001.', 'counterslot' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-booking-page"><?php esc_html_e( 'Booking page', 'counterslot' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages( [
						'name'              => 'settings[booking_page_id]',
						'id'                => 'sb-booking-page',
						'selected'          => (int) $settings['booking_page_id'],
						'show_option_none'  => esc_html__( 'Find automatically', 'counterslot' ),
						'option_none_value' => 0,
					] );
					?>
					<p class="description"><?php esc_html_e( 'The page with the [counterslot] form. Links in emails for customers to manage their booking open here.', 'counterslot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Customer changes', 'counterslot' ); ?></th>
				<td>
					<input type="hidden" name="settings[customer_changes]" value="0">
					<label><input type="checkbox" name="settings[customer_changes]" value="1" <?php checked( ! empty( $settings['customer_changes'] ) ); ?>> <?php esc_html_e( 'Let customers cancel or reschedule from the link in their emails', 'counterslot' ); ?></label>
					<p>
						<label for="sb-cutoff"><?php esc_html_e( 'Up to', 'counterslot' ); ?></label>
						<input id="sb-cutoff" class="small-text" type="number" min="0" step="1" name="settings[change_cutoff_hours]" value="<?php echo (int) $settings['change_cutoff_hours']; ?>">
						<?php esc_html_e( 'hours before the appointment', 'counterslot' ); ?>
					</p>
					<p class="description"><?php esc_html_e( 'Customers can always open the link to see their booking. Add {manage_link} to your emails on the Notifications page.', 'counterslot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-ip-header"><?php esc_html_e( 'Visitor IP comes from', 'counterslot' ); ?></label></th>
				<td>
					<select id="sb-ip-header" name="settings[ip_header]">
						<?php foreach ( CSlot_Settings::ip_headers() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['ip_header'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Used to limit booking attempts to 5 per 10 minutes per visitor. Only change this if your site is behind Cloudflare or another proxy; otherwise visitors could fake their IP.', 'counterslot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Uninstall', 'counterslot' ); ?></th>
				<td>
					<input type="hidden" name="settings[delete_data_on_uninstall]" value="0">
					<label><input type="checkbox" name="settings[delete_data_on_uninstall]" value="1" <?php checked( ! empty( $settings['delete_data_on_uninstall'] ) ); ?>> <?php esc_html_e( 'Delete all bookings, customers, services and staff when the plugin is deleted', 'counterslot' ); ?></label>
					<p class="description"><?php esc_html_e( 'This cannot be undone. Leave unticked to keep your data.', 'counterslot' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save Settings', 'counterslot' ) ); ?>
	</form>
</div>
