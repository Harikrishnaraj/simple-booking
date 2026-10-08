<?php
/**
 * Booking form for the [counterslot] shortcode.
 * Layout follows the "week strip + time slots" pattern (21st.dev Booking Slot Calendar).
 *
 * @var array $groups Active services grouped by category name ('' = no categories in use).
 * @var array $staff  Active staff, each with 'service_ids' and 'photo_url'.
 * @var array $fields Custom fields (shown per service).
 * @var array $locations Active locations, or [] when there's only one (no question).
 * @var array $extras    Paid add-ons (shown per service).
 * @var bool  $priced    Whether to show the price summary and coupon box.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- included inside cslot_view(), so these are local variables.
$uid = wp_unique_id( 'sb-' );
?>
<div class="sb-booking-wrapper" data-sb-booking>
	<h2 class="sb-booking-title"><?php esc_html_e( 'Book an Appointment', 'counterslot' ); ?></h2>

	<ol class="sb-step-indicator">
		<li class="sb-step-item active" aria-current="step"><?php esc_html_e( '1. Service', 'counterslot' ); ?></li>
		<li class="sb-step-item"><?php esc_html_e( '2. Day & Time', 'counterslot' ); ?></li>
		<li class="sb-step-item"><?php esc_html_e( '3. Your Details', 'counterslot' ); ?></li>
	</ol>

	<form class="sb-booking-form" novalidate>
		<input type="hidden" name="booking_date" value="">
		<input type="hidden" name="booking_time" value="">

		<fieldset class="sb-step">
			<legend class="sb-sr-only"><?php esc_html_e( 'Choose a service', 'counterslot' ); ?></legend>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-service"><?php esc_html_e( 'Service', 'counterslot' ); ?></label>
				<select id="<?php echo esc_attr( $uid ); ?>-service" class="sb-form-control" name="service_id" required>
					<?php foreach ( $groups as $group => $services ) : ?>
						<?php if ( '' !== $group ) : ?>
							<optgroup label="<?php echo esc_attr( $group ); ?>">
						<?php endif; ?>
						<?php foreach ( $services as $s ) : ?>
							<option value="<?php echo (int) $s['id']; ?>">
								<?php
								// Services without a price just show their length.
								echo esc_html( (float) $s['price'] > 0
									? sprintf(
										/* translators: 1: service name, 2: duration in minutes, 3: price */
										__( '%1$s (%2$d min, %3$s)', 'counterslot' ),
										$s['name'],
										(int) $s['duration'],
										cslot_price( $s['price'] )
									)
									: sprintf(
										/* translators: 1: service name, 2: duration in minutes */
										__( '%1$s (%2$d min)', 'counterslot' ),
										$s['name'],
										(int) $s['duration']
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

			<?php if ( $locations ) : ?>
				<div class="sb-form-group">
					<label for="<?php echo esc_attr( $uid ); ?>-location"><?php esc_html_e( 'Location', 'counterslot' ); ?></label>
					<select id="<?php echo esc_attr( $uid ); ?>-location" class="sb-form-control" name="location_id" required>
						<?php foreach ( $locations as $location ) : ?>
							<option value="<?php echo (int) $location['id']; ?>"><?php echo esc_html( $location['name'] . ( $location['address'] ? ' — ' . strtok( $location['address'], "\n" ) : '' ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<?php if ( $staff ) : ?>
				<div class="sb-form-group">
					<label for="<?php echo esc_attr( $uid ); ?>-staff"><?php echo esc_html( CSlot_Settings::staff_label() ); ?></label>
					<div class="sb-staff-pick">
						<img class="sb-staff-photo" src="" alt="" width="44" height="44" hidden>
						<select id="<?php echo esc_attr( $uid ); ?>-staff" class="sb-form-control" name="staff_id">
							<option value=""><?php esc_html_e( 'Any available', 'counterslot' ); ?></option>
							<?php foreach ( $staff as $member ) : ?>
								<option value="<?php echo (int) $member['id']; ?>" data-services="<?php echo esc_attr( implode( ',', $member['service_ids'] ) ); ?>" data-photo="<?php echo esc_url( $member['photo_url'] ); ?>" data-location="<?php echo (int) ( $member['location_id'] ?? 0 ); ?>"><?php echo esc_html( $member['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $extras ) : ?>
				<fieldset class="sb-form-group sb-extras">
					<legend class="sb-form-label"><?php esc_html_e( 'Extras', 'counterslot' ); ?></legend>
					<?php foreach ( $extras as $extra ) : ?>
						<label class="sb-check-label" data-sb-field data-services="<?php echo esc_attr( implode( ',', $extra['services'] ) ); ?>">
							<input type="checkbox" name="extras[]" value="<?php echo esc_attr( $extra['id'] ); ?>">
							<?php echo esc_html( $extra['name'] ); ?>
							<span class="sb-extra-price">+<?php echo esc_html( cslot_price( $extra['price'] ) ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<div class="sb-actions">
				<button type="button" class="sb-btn-primary" data-sb-go="2"><?php esc_html_e( 'Next', 'counterslot' ); ?></button>
			</div>
		</fieldset>

		<fieldset class="sb-step" hidden>
			<legend class="sb-form-label"><?php esc_html_e( 'Pick a day', 'counterslot' ); ?></legend>
			<div class="sb-days" role="group" aria-label="<?php esc_attr_e( 'Available days', 'counterslot' ); ?>"></div>

			<p class="sb-form-label" id="<?php echo esc_attr( $uid ); ?>-times"><?php esc_html_e( 'Pick a time', 'counterslot' ); ?></p>
			<div class="sb-slots-grid" role="group" aria-labelledby="<?php echo esc_attr( $uid ); ?>-times" aria-live="polite">
				<p class="sb-hint"><?php esc_html_e( 'Choose a day to see available times.', 'counterslot' ); ?></p>
			</div>

			<div class="sb-actions">
				<button type="button" class="sb-btn-secondary" data-sb-go="1"><?php esc_html_e( 'Back', 'counterslot' ); ?></button>
				<button type="button" class="sb-btn-primary" data-sb-go="3" disabled><?php esc_html_e( 'Next', 'counterslot' ); ?></button>
			</div>
		</fieldset>

		<fieldset class="sb-step" hidden>
			<legend class="sb-sr-only"><?php esc_html_e( 'Your details', 'counterslot' ); ?></legend>
			<p class="sb-summary" aria-live="polite"></p>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Name', 'counterslot' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-name" class="sb-form-control" name="name" type="text" required maxlength="191" autocomplete="name">
			</div>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'counterslot' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-email" class="sb-form-control" name="email" type="email" required maxlength="191" autocomplete="email" spellcheck="false">
			</div>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-phone"><?php esc_html_e( 'Phone (optional)', 'counterslot' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-phone" class="sb-form-control" name="phone" type="tel" maxlength="50" autocomplete="tel">
			</div>
			<?php
			cslot_view( 'admin/views/partials/custom-field-inputs', [
				'fields'       => $fields,
				'id_prefix'    => $uid . '-cf',
				'group_class'  => 'sb-form-group',
				'input_class'  => 'sb-form-control',
				'use_required' => true,
			] );
			?>
			<div class="sb-form-group">
				<label for="<?php echo esc_attr( $uid ); ?>-notes"><?php esc_html_e( 'Notes (optional)', 'counterslot' ); ?></label>
				<textarea id="<?php echo esc_attr( $uid ); ?>-notes" class="sb-form-control" name="notes" rows="3" maxlength="2000"></textarea>
			</div>
			<?php if ( $priced ) : ?>
				<div class="sb-form-group">
					<label for="<?php echo esc_attr( $uid ); ?>-coupon"><?php esc_html_e( 'Coupon code (optional)', 'counterslot' ); ?></label>
					<div class="sb-coupon">
						<input id="<?php echo esc_attr( $uid ); ?>-coupon" class="sb-form-control" name="coupon" type="text" maxlength="50" autocomplete="off" spellcheck="false">
						<button type="button" class="sb-btn-secondary" data-sb-apply-coupon><?php esc_html_e( 'Apply', 'counterslot' ); ?></button>
					</div>
					<p class="sb-coupon-error" role="alert" hidden></p>
				</div>
				<table class="sb-price-summary" aria-live="polite" data-sb-price><tbody></tbody></table>
				<p class="sb-hint"><?php esc_html_e( 'You pay at your appointment.', 'counterslot' ); ?></p>
			<?php endif; ?>
			<div class="sb-hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $uid ); ?>-website">Website</label>
				<input id="<?php echo esc_attr( $uid ); ?>-website" name="website" type="text" tabindex="-1" autocomplete="off">
			</div>

			<div class="sb-actions">
				<button type="button" class="sb-btn-secondary" data-sb-go="2"><?php esc_html_e( 'Back', 'counterslot' ); ?></button>
				<button type="submit" class="sb-btn-primary"><?php esc_html_e( 'Book Now', 'counterslot' ); ?></button>
			</div>
		</fieldset>
	</form>

	<div class="sb-message" role="status" aria-live="polite" tabindex="-1" hidden></div>
</div>
