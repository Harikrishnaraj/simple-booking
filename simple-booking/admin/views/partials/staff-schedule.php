<?php
/**
 * Working hours and days off fields for the staff form.
 *
 * @var array|null $editing The staff member being edited
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_locale;
$staff_mgr = new SB_Staff();
$schedule  = $editing ? $staff_mgr->schedule( $editing ) : null;
$days_off  = $editing ? $staff_mgr->days_off( $editing ) : [];
$settings  = SB_Settings::get_settings();
?>
<fieldset class="form-field" data-sb-schedule>
	<legend><?php esc_html_e( 'Working hours', 'simple-booking' ); ?></legend>
	<label><input type="radio" name="schedule_custom" value="0" <?php checked( null === $schedule ); ?>>
		<?php
		/* translators: 1: opening time, 2: closing time */
		echo esc_html( sprintf( __( 'Business hours (%1$s–%2$s on working days)', 'simple-booking' ), $settings['business_hours_start'], $settings['business_hours_end'] ) );
		?>
	</label><br>
	<label><input type="radio" name="schedule_custom" value="1" <?php checked( null !== $schedule ); ?>> <?php esc_html_e( 'Custom hours', 'simple-booking' ); ?></label>

	<table class="sb-schedule" <?php echo null === $schedule ? 'hidden' : ''; ?>>
		<?php foreach ( SB_Settings::WEEK_DAYS as $day ) : ?>
			<?php
			// Unsaved custom schedules start from the business hours.
			$range = null === $schedule ? SB_Staff::business_hours_on( $day ) : ( $schedule[ $day ] ?? null );
			$label = $wp_locale->get_weekday( (int) gmdate( 'w', strtotime( $day ) ) );
			$key   = sanitize_key( $day );
			?>
			<tr>
				<th scope="row">
					<label title="<?php echo esc_attr( $label ); ?>"><input type="checkbox" name="schedule[<?php echo esc_attr( $day ); ?>][on]" value="1" <?php checked( null !== $range ); ?> aria-label="<?php echo esc_attr( sprintf( __( 'Works on %s', 'simple-booking' ), $label ) ); ?>"> <span aria-hidden="true"><?php echo esc_html( $wp_locale->get_weekday_abbrev( $label ) ); ?></span></label>
				</th>
				<td>
					<label class="screen-reader-text" for="sb-sch-<?php echo esc_attr( $key ); ?>-start"><?php echo esc_html( sprintf( __( '%s start', 'simple-booking' ), $label ) ); ?></label>
					<input id="sb-sch-<?php echo esc_attr( $key ); ?>-start" type="time" name="schedule[<?php echo esc_attr( $day ); ?>][start]" value="<?php echo esc_attr( $range[0] ?? $settings['business_hours_start'] ); ?>">
					–
					<label class="screen-reader-text" for="sb-sch-<?php echo esc_attr( $key ); ?>-end"><?php echo esc_html( sprintf( __( '%s end', 'simple-booking' ), $label ) ); ?></label>
					<input id="sb-sch-<?php echo esc_attr( $key ); ?>-end" type="time" name="schedule[<?php echo esc_attr( $day ); ?>][end]" value="<?php echo esc_attr( $range[1] ?? $settings['business_hours_end'] ); ?>">
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</fieldset>

<fieldset class="form-field" data-sb-days-off>
	<legend><?php esc_html_e( 'Days off', 'simple-booking' ); ?></legend>
	<p class="description"><?php esc_html_e( 'Holidays or leave. Leave "to" empty for a single day.', 'simple-booking' ); ?></p>
	<div class="sb-days-off">
		<?php foreach ( array_merge( $days_off, [ [ 'from' => '', 'to' => '' ] ] ) as $off ) : ?>
			<div class="sb-days-off__row">
				<input type="date" name="days_off[from][]" value="<?php echo esc_attr( $off['from'] ); ?>" aria-label="<?php esc_attr_e( 'From', 'simple-booking' ); ?>">
				–
				<input type="date" name="days_off[to][]" value="<?php echo esc_attr( $off['to'] === $off['from'] ? '' : $off['to'] ); ?>" aria-label="<?php esc_attr_e( 'To (optional)', 'simple-booking' ); ?>">
				<button type="button" class="sb-icon-button sb-icon-button--small sb-icon-button--danger" data-sb-remove-row aria-label="<?php esc_attr_e( 'Remove', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
		<?php endforeach; ?>
	</div>
	<button type="button" class="sb-button sb-button--secondary sb-button--small" data-sb-add-row><?php esc_html_e( 'Add day off', 'simple-booking' ); ?></button>
</fieldset>
