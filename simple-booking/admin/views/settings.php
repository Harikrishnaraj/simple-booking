<?php
/**
 * @var array  $settings
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_locale;
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Settings', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<form class="sb-card" data-sb-action="sb_save_settings">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="sb-business-name"><?php esc_html_e( 'Business name', 'simple-booking' ); ?></label></th>
				<td><input id="sb-business-name" class="regular-text" name="settings[business_name]" type="text" value="<?php echo esc_attr( $settings['business_name'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Business hours', 'simple-booking' ); ?></th>
				<td>
					<label for="sb-hours-start" class="screen-reader-text"><?php esc_html_e( 'Opening time', 'simple-booking' ); ?></label>
					<input id="sb-hours-start" name="settings[business_hours_start]" type="time" required value="<?php echo esc_attr( $settings['business_hours_start'] ); ?>">
					–
					<label for="sb-hours-end" class="screen-reader-text"><?php esc_html_e( 'Closing time', 'simple-booking' ); ?></label>
					<input id="sb-hours-end" name="settings[business_hours_end]" type="time" required value="<?php echo esc_attr( $settings['business_hours_end'] ); ?>">
					<p class="description"><?php echo esc_html( sprintf( __( 'Times are in the site timezone (%s).', 'simple-booking' ), wp_timezone_string() ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Working days', 'simple-booking' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Working days', 'simple-booking' ); ?></legend>
						<input type="hidden" name="settings[work_days][]" value="">
						<?php foreach ( SB_Settings::WEEK_DAYS as $day ) : ?>
							<label>
								<input type="checkbox" name="settings[work_days][]" value="<?php echo esc_attr( $day ); ?>" <?php checked( in_array( $day, (array) $settings['work_days'], true ) ); ?>>
								<?php echo esc_html( $wp_locale->get_weekday( (int) gmdate( 'w', strtotime( $day ) ) ) ); ?>
							</label><br>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-slot-duration"><?php esc_html_e( 'Slot interval (minutes)', 'simple-booking' ); ?></label></th>
				<td>
					<input id="sb-slot-duration" class="small-text" name="settings[slot_duration]" type="number" min="5" step="5" required value="<?php echo (int) $settings['slot_duration']; ?>">
					<p class="description"><?php esc_html_e( 'How often a new start time is offered, e.g. every 30 minutes.', 'simple-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-currency"><?php esc_html_e( 'Currency symbol', 'simple-booking' ); ?></label></th>
				<td><input id="sb-currency" class="small-text" name="settings[currency_symbol]" type="text" maxlength="5" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-admin-email"><?php esc_html_e( 'Notification email', 'simple-booking' ); ?></label></th>
				<td><input id="sb-admin-email" class="regular-text" name="settings[admin_email]" type="email" spellcheck="false" required value="<?php echo esc_attr( $settings['admin_email'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Emails', 'simple-booking' ); ?></th>
				<td>
					<?php
					echo wp_kses_post( sprintf(
						/* translators: %s: link to the Notifications page */
						__( 'Choose which emails are sent, edit their text and set up reminders on the <a href="%s">Notifications</a> page.', 'simple-booking' ),
						esc_url( admin_url( 'admin.php?page=sb-notifications' ) )
					) );
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-booking-page"><?php esc_html_e( 'Booking page', 'simple-booking' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages( [
						'name'              => 'settings[booking_page_id]',
						'id'                => 'sb-booking-page',
						'selected'          => (int) $settings['booking_page_id'],
						'show_option_none'  => __( 'Find automatically', 'simple-booking' ),
						'option_none_value' => 0,
					] );
					?>
					<p class="description"><?php esc_html_e( 'The page with the [simple_booking] form. Links in emails for customers to manage their booking open here.', 'simple-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Customer changes', 'simple-booking' ); ?></th>
				<td>
					<input type="hidden" name="settings[customer_changes]" value="0">
					<label><input type="checkbox" name="settings[customer_changes]" value="1" <?php checked( ! empty( $settings['customer_changes'] ) ); ?>> <?php esc_html_e( 'Let customers cancel or reschedule from the link in their emails', 'simple-booking' ); ?></label>
					<p>
						<label for="sb-cutoff"><?php esc_html_e( 'Up to', 'simple-booking' ); ?></label>
						<input id="sb-cutoff" class="small-text" type="number" min="0" step="1" name="settings[change_cutoff_hours]" value="<?php echo (int) $settings['change_cutoff_hours']; ?>">
						<?php esc_html_e( 'hours before the appointment', 'simple-booking' ); ?>
					</p>
					<p class="description"><?php esc_html_e( 'Customers can always open the link to see their booking. Add {manage_link} to your emails on the Notifications page.', 'simple-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sb-ip-header"><?php esc_html_e( 'Visitor IP comes from', 'simple-booking' ); ?></label></th>
				<td>
					<select id="sb-ip-header" name="settings[ip_header]">
						<?php foreach ( SB_Settings::ip_headers() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['ip_header'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Used to limit booking attempts to 5 per 10 minutes per visitor. Only change this if your site is behind Cloudflare or another proxy; otherwise visitors could fake their IP.', 'simple-booking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Uninstall', 'simple-booking' ); ?></th>
				<td>
					<input type="hidden" name="settings[delete_data_on_uninstall]" value="0">
					<label><input type="checkbox" name="settings[delete_data_on_uninstall]" value="1" <?php checked( ! empty( $settings['delete_data_on_uninstall'] ) ); ?>> <?php esc_html_e( 'Delete all bookings, customers, services and staff when the plugin is deleted', 'simple-booking' ); ?></label>
					<p class="description"><?php esc_html_e( 'This cannot be undone. Leave unticked to keep your data.', 'simple-booking' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save Settings', 'simple-booking' ) ); ?>
	</form>
</div>
