<?php
/**
 * Booking form for the [simple_booking] shortcode.
 * Layout follows the "week strip + time slots" pattern (21st.dev Booking Slot Calendar).
 *
 * @var array $groups Active services grouped by category name ('' = no categories in use).
 * @var array $staff  Active staff, each with 'service_ids' and 'photo_url'.
 * @var array $fields Custom fields (shown per service).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$uid = wp_unique_id( 'sb-' );
?>
<div class="sb-booking-wrapper" data-sb-booking>
	<h2 class="sb-booking-title"><?php esc_html_e( 'Book an Appointment', 'simple-booking' ); ?></h2>

	<ol class="sb-step-indicator">
		<li class="sb-step-item active" aria-current="step"><?php esc_html_e( '1. Service', 'simple-booking' ); ?></li>
		<li class="sb-step-item"><?php esc_html_e( '2. Day & Time', 'simple-booking' ); ?></li>
		<li class="sb-step-item"><?php esc_html_e( '3. Your Details', 'simple-booking' ); ?></li>
	</ol>

	<form class="sb-booking-form" novalidate>
		<input type="hidden" name="booking_date" value="">
		<input type="hidden" name="booking_time" value="">

		<fieldset class="sb-step">
			<legend class="sb-sr-only"><?php esc_html_e( 'Choose a service', 'simple-booking' ); ?></legend>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-service"><?php esc_html_e( 'Service', 'simple-booking' ); ?></label>
				<select id="<?php echo esc_attr( $uid ); ?>-service" class="sb-form-control" name="service_id" required>
					<?php foreach ( $groups as $group => $services ) : ?>
						<?php if ( '' !== $group ) : ?>
							<optgroup label="<?php echo esc_attr( $group ); ?>">
						<?php endif; ?>
						<?php foreach ( $services as $s ) : ?>
							<option value="<?php echo (int) $s['id']; ?>">
								<?php
								echo esc_html( sprintf(
									/* translators: 1: service name, 2: duration in minutes, 3: price */
									__( '%1$s (%2$d min, %3$s)', 'simple-booking' ),
									$s['name'],
									(int) $s['duration'],
									sb_price( $s['price'] )
								) );
								?>
							</option>
						<?php endforeach; ?>
						<?php if ( '' !== $group ) : ?>
							</optgroup>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( $staff ) : ?>
				<div class="sb-form-group">
					<label for="<?php echo esc_attr( $uid ); ?>-staff"><?php esc_html_e( 'Staff member', 'simple-booking' ); ?></label>
					<div class="sb-staff-pick">
						<img class="sb-staff-photo" src="" alt="" width="44" height="44" hidden>
						<select id="<?php echo esc_attr( $uid ); ?>-staff" class="sb-form-control" name="staff_id">
							<option value=""><?php esc_html_e( 'Any available', 'simple-booking' ); ?></option>
							<?php foreach ( $staff as $member ) : ?>
								<option value="<?php echo (int) $member['id']; ?>" data-services="<?php echo esc_attr( implode( ',', $member['service_ids'] ) ); ?>" data-photo="<?php echo esc_url( $member['photo_url'] ); ?>"><?php echo esc_html( $member['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<div class="sb-actions">
				<button type="button" class="sb-btn-primary" data-sb-go="2"><?php esc_html_e( 'Next', 'simple-booking' ); ?></button>
			</div>
		</fieldset>

		<fieldset class="sb-step" hidden>
			<legend class="sb-form-label"><?php esc_html_e( 'Pick a day', 'simple-booking' ); ?></legend>
			<div class="sb-days" role="group" aria-label="<?php esc_attr_e( 'Available days', 'simple-booking' ); ?>"></div>

			<p class="sb-form-label" id="<?php echo esc_attr( $uid ); ?>-times"><?php esc_html_e( 'Pick a time', 'simple-booking' ); ?></p>
			<div class="sb-slots-grid" role="group" aria-labelledby="<?php echo esc_attr( $uid ); ?>-times" aria-live="polite">
				<p class="sb-hint"><?php esc_html_e( 'Choose a day to see available times.', 'simple-booking' ); ?></p>
			</div>

			<div class="sb-actions">
				<button type="button" class="sb-btn-secondary" data-sb-go="1"><?php esc_html_e( 'Back', 'simple-booking' ); ?></button>
				<button type="button" class="sb-btn-primary" data-sb-go="3" disabled><?php esc_html_e( 'Next', 'simple-booking' ); ?></button>
			</div>
		</fieldset>

		<fieldset class="sb-step" hidden>
			<legend class="sb-sr-only"><?php esc_html_e( 'Your details', 'simple-booking' ); ?></legend>
			<p class="sb-summary" aria-live="polite"></p>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-name" class="sb-form-control" name="name" type="text" required maxlength="191" autocomplete="name">
			</div>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'simple-booking' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-email" class="sb-form-control" name="email" type="email" required maxlength="191" autocomplete="email" spellcheck="false">
			</div>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-phone"><?php esc_html_e( 'Phone (optional)', 'simple-booking' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-phone" class="sb-form-control" name="phone" type="tel" maxlength="50" autocomplete="tel">
			</div>
			<?php
			sb_view( 'admin/views/partials/custom-field-inputs', [
				'fields'       => $fields,
				'id_prefix'    => $uid . '-cf',
				'group_class'  => 'sb-form-group',
				'input_class'  => 'sb-form-control',
				'use_required' => true,
			] );
			?>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-notes"><?php esc_html_e( 'Notes (optional)', 'simple-booking' ); ?></label>
				<textarea id="<?php echo esc_attr( $uid ); ?>-notes" class="sb-form-control" name="notes" rows="3" maxlength="2000"></textarea>
			</div>
			<div class="sb-hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $uid ); ?>-website">Website</label>
				<input id="<?php echo esc_attr( $uid ); ?>-website" name="website" type="text" tabindex="-1" autocomplete="off">
			</div>

			<div class="sb-actions">
				<button type="button" class="sb-btn-secondary" data-sb-go="2"><?php esc_html_e( 'Back', 'simple-booking' ); ?></button>
				<button type="submit" class="sb-btn-primary"><?php esc_html_e( 'Book Now', 'simple-booking' ); ?></button>
			</div>
		</fieldset>
	</form>

	<div class="sb-message" role="status" aria-live="polite" tabindex="-1" hidden></div>
</div>
