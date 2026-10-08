<?php
/**
 * First-run setup wizard: five steps on one form. admin.js shows one step at a time,
 * fills in the chosen industry's defaults and submits everything at the end.
 *
 * @var array  $presets    SB_Setup::presets()
 * @var array  $settings   current settings
 * @var array  $user       [ 'name' => , 'email' => ] of the current admin
 * @var string $page_url   existing booking page, or ''
 * @var bool   $rerun      true when setup was already done
 * @var string[] $existing  names of services that already exist, lowercased
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_locale;

$industry = isset( $presets[ $settings['industry'] ] ) ? $settings['industry'] : '';
$steps    = [
	1 => __( 'Your business', 'bellbook' ),
	2 => __( 'Opening hours', 'bellbook' ),
	3 => __( 'Your team', 'bellbook' ),
	4 => __( 'Services', 'bellbook' ),
	5 => __( 'Booking page', 'bellbook' ),
];
// Rupee for Indian sites, otherwise whatever is already set.
$currency = '$' === $settings['currency_symbol'] && ( 'Asia/Kolkata' === wp_timezone_string() || 'en_IN' === get_locale() || str_starts_with( get_locale(), 'hi' ) )
	? '₹'
	: $settings['currency_symbol'];
?>
<div class="wrap sb-app sb-setup">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Set up Bellbook', 'bellbook' ), 'theme' => $theme ] ); ?>

	<ol class="sb-steps" aria-label="<?php esc_attr_e( 'Setup steps', 'bellbook' ); ?>">
		<?php foreach ( $steps as $n => $label ) : ?>
			<li data-sb-step-label="<?php echo (int) $n; ?>" <?php echo 1 === $n ? 'aria-current="step"' : ''; ?>><span class="sb-steps__num"><?php echo (int) $n; ?></span><?php echo esc_html( $label ); ?></li>
		<?php endforeach; ?>
	</ol>

	<form class="sb-card sb-setup__form" data-sb-wizard novalidate>
		<?php if ( $rerun ) : ?>
			<p class="sb-muted"><?php esc_html_e( 'Running setup again updates your hours and wording, and adds only services, staff and a booking page that you don\'t have yet. Nothing is deleted.', 'bellbook' ); ?></p>
		<?php endif; ?>

		<fieldset class="sb-step" data-sb-step="1">
			<legend class="sb-step__title"><?php esc_html_e( 'What kind of business do you run?', 'bellbook' ); ?></legend>
			<p class="sb-muted"><?php esc_html_e( 'This fills in typical hours, services and wording. You can change all of it now or later.', 'bellbook' ); ?></p>
			<div class="sb-industries">
				<?php foreach ( $presets as $key => $p ) : ?>
					<label class="sb-industry">
						<input type="radio" name="industry" value="<?php echo esc_attr( $key ); ?>" required <?php checked( $industry, $key ); ?>
							data-preset="<?php echo esc_attr( wp_json_encode( [ 'days' => $p['days'], 'start' => $p['start'], 'end' => $p['end'], 'staff_label' => $p['staff_label'] ] ) ); ?>">
						<span class="sb-industry__name"><?php echo esc_html( $p['label'] ); ?></span>
						<span class="sb-industry__desc"><?php echo esc_html( $p['description'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="sb-field">
				<label for="sb-setup-name"><?php esc_html_e( 'Business name', 'bellbook' ); ?></label>
				<input id="sb-setup-name" class="sb-input--wide" type="text" name="business_name" required maxlength="191" value="<?php echo esc_attr( $settings['business_name'] ); ?>">
			</p>
		</fieldset>

		<fieldset class="sb-step" data-sb-step="2">
			<legend class="sb-step__title"><?php esc_html_e( 'When can customers book?', 'bellbook' ); ?></legend>
			<fieldset class="sb-field">
				<legend class="sb-label"><?php esc_html_e( 'Open on', 'bellbook' ); ?></legend>
				<div class="sb-days">
					<?php foreach ( SB_Settings::WEEK_DAYS as $day ) : ?>
						<label class="sb-check-label"><input type="checkbox" name="work_days[]" value="<?php echo esc_attr( $day ); ?>" data-sb-day <?php checked( in_array( $day, (array) $settings['work_days'], true ) ); ?>>
							<?php echo esc_html( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( (int) gmdate( 'w', strtotime( $day ) ) ) ) ); ?></label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<div class="sb-field-row">
				<p class="sb-field">
					<label for="sb-setup-start"><?php esc_html_e( 'Opens at', 'bellbook' ); ?></label>
					<input id="sb-setup-start" type="time" name="business_hours_start" required value="<?php echo esc_attr( $settings['business_hours_start'] ); ?>">
				</p>
				<p class="sb-field">
					<label for="sb-setup-end"><?php esc_html_e( 'Closes at', 'bellbook' ); ?></label>
					<input id="sb-setup-end" type="time" name="business_hours_end" required value="<?php echo esc_attr( $settings['business_hours_end'] ); ?>">
				</p>
				<p class="sb-field">
					<label for="sb-setup-slot"><?php esc_html_e( 'Offer a start time every', 'bellbook' ); ?></label>
					<select id="sb-setup-slot" name="slot_duration">
						<?php foreach ( [ 10, 15, 20, 30, 45, 60 ] as $m ) : ?>
							<?php /* translators: %d: minutes */ ?>
							<option value="<?php echo (int) $m; ?>" <?php selected( (int) $settings['slot_duration'], $m ); ?>><?php echo esc_html( sprintf( __( '%d minutes', 'bellbook' ), $m ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			</div>
			<p class="sb-hint"><?php echo esc_html( sprintf( /* translators: %s: timezone */ __( 'Times are in your site\'s timezone (%s). Each staff member can have their own hours later.', 'bellbook' ), wp_timezone_string() ) ); ?></p>
			<div class="sb-field-row">
				<p class="sb-field">
					<label for="sb-setup-currency"><?php esc_html_e( 'Currency symbol', 'bellbook' ); ?></label>
					<input id="sb-setup-currency" class="small-text" type="text" name="currency_symbol" maxlength="5" value="<?php echo esc_attr( $currency ); ?>">
				</p>
				<p class="sb-field">
					<label for="sb-setup-email"><?php esc_html_e( 'Send booking alerts to', 'bellbook' ); ?></label>
					<input id="sb-setup-email" class="regular-text" type="email" name="admin_email" spellcheck="false" required value="<?php echo esc_attr( $settings['admin_email'] ); ?>">
				</p>
			</div>
		</fieldset>

		<fieldset class="sb-step" data-sb-step="3">
			<legend class="sb-step__title"><?php esc_html_e( 'Who takes the appointments?', 'bellbook' ); ?></legend>
			<p class="sb-field">
				<label for="sb-setup-label"><?php esc_html_e( 'What customers call them', 'bellbook' ); ?></label>
				<input id="sb-setup-label" type="text" name="staff_label" maxlength="40" value="<?php echo esc_attr( $settings['staff_label'] ); ?>" placeholder="<?php esc_attr_e( 'Staff member', 'bellbook' ); ?>">
				<span class="sb-hint"><?php esc_html_e( 'Shown on the booking form, e.g. "Doctor" or "Stylist".', 'bellbook' ); ?></span>
			</p>
			<div class="sb-field-row">
				<p class="sb-field">
					<label for="sb-setup-staff-name"><?php esc_html_e( 'First person\'s name', 'bellbook' ); ?> <span class="sb-optional"><?php esc_html_e( '(optional)', 'bellbook' ); ?></span></label>
					<input id="sb-setup-staff-name" class="regular-text" type="text" name="staff_name" maxlength="191" value="<?php echo $rerun ? '' : esc_attr( $user['name'] ); ?>">
				</p>
				<p class="sb-field">
					<label for="sb-setup-staff-email"><?php esc_html_e( 'Their email', 'bellbook' ); ?></label>
					<input id="sb-setup-staff-email" class="regular-text" type="email" name="staff_email" spellcheck="false" value="<?php echo $rerun ? '' : esc_attr( $user['email'] ); ?>">
				</p>
			</div>
			<p class="sb-hint"><?php esc_html_e( 'If you work alone, that\'s you. Add more people, photos and their own hours on the Staff page later. Leave the name empty to skip.', 'bellbook' ); ?></p>
		</fieldset>

		<fieldset class="sb-step" data-sb-step="4">
			<legend class="sb-step__title"><?php esc_html_e( 'What can customers book?', 'bellbook' ); ?></legend>
			<p class="sb-muted"><?php esc_html_e( 'Untick what you don\'t offer, change names and durations, and add a price if you like. Leave the price empty if you prefer not to show one.', 'bellbook' ); ?></p>
			<?php foreach ( $presets as $key => $p ) : ?>
				<div class="sb-table-wrap" data-sb-services="<?php echo esc_attr( $key ); ?>" <?php echo $industry === $key || ( ! $industry && 'other' === $key ) ? '' : 'hidden'; ?>>
					<table class="sb-table sb-table--compact sb-setup__services">
						<thead>
							<tr>
								<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Offer', 'bellbook' ); ?></span></th>
								<th scope="col"><?php esc_html_e( 'Service', 'bellbook' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Minutes', 'bellbook' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Price', 'bellbook' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$rows = array_merge( $p['services'], [ [ '', '' ], [ '', '' ] ] ); // two blank rows for their own services
							foreach ( $rows as $i => [ $service, $minutes ] ) :
								$id = 'sb-svc-' . $key . '-' . $i;
								?>
								<?php
								// On a re-run, services that already exist are shown as added; other suggestions start unticked.
								$added = '' !== $service && in_array( mb_strtolower( $service ), $existing, true );
								?>
								<tr>
									<td><input type="checkbox" name="services[<?php echo esc_attr( $key . $i ); ?>][on]" value="1" <?php checked( $added || ( '' !== $service && ! $rerun ) ); ?>
										<?php echo $added ? 'disabled data-sb-fixed' : ''; ?>
										aria-label="<?php echo $added ? esc_attr__( 'Already added', 'bellbook' ) : esc_attr__( 'Offer this service', 'bellbook' ); ?>" data-sb-svc-on></td>
									<td><label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Service name', 'bellbook' ); ?></label>
										<input id="<?php echo esc_attr( $id ); ?>" type="text" name="services[<?php echo esc_attr( $key . $i ); ?>][name]" maxlength="191" value="<?php echo esc_attr( $service ); ?>"
											placeholder="<?php echo '' === $service ? esc_attr__( 'Add your own', 'bellbook' ) : ''; ?>" <?php echo $added ? 'disabled data-sb-fixed' : ''; ?> data-sb-svc-name>
										<?php if ( $added ) : ?><span class="sb-badge sb-badge--active"><?php esc_html_e( 'Added', 'bellbook' ); ?></span><?php endif; ?></td>
									<td><label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>-min"><?php esc_html_e( 'Duration in minutes', 'bellbook' ); ?></label>
										<input id="<?php echo esc_attr( $id ); ?>-min" class="small-text" type="number" min="5" step="5" <?php echo $added ? 'disabled data-sb-fixed' : ''; ?> name="services[<?php echo esc_attr( $key . $i ); ?>][duration]" value="<?php echo esc_attr( (string) ( $minutes ?: 30 ) ); ?>"></td>
									<td><label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>-price"><?php esc_html_e( 'Price', 'bellbook' ); ?></label>
										<input id="<?php echo esc_attr( $id ); ?>-price" class="small-text" type="number" min="0" step="0.01" <?php echo $added ? 'disabled data-sb-fixed' : ''; ?> name="services[<?php echo esc_attr( $key . $i ); ?>][price]" value="" placeholder="<?php echo esc_attr( $currency ); ?>"></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
			<p class="sb-hint"><?php esc_html_e( 'Group classes with limited places are set up on the Events page instead.', 'bellbook' ); ?></p>
		</fieldset>

		<fieldset class="sb-step" data-sb-step="5">
			<legend class="sb-step__title"><?php esc_html_e( 'Where will customers book?', 'bellbook' ); ?></legend>
			<?php if ( $page_url ) : ?>
				<p>
					<?php esc_html_e( 'You already have a booking page:', 'bellbook' ); ?>
					<a href="<?php echo esc_url( $page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $page_url ); ?></a>
				</p>
			<?php else : ?>
				<p class="sb-field sb-field--check">
					<label><input type="checkbox" name="create_page" value="1" checked> <?php esc_html_e( 'Create a page with the booking form', 'bellbook' ); ?></label>
				</p>
				<p class="sb-field">
					<label for="sb-setup-page-title"><?php esc_html_e( 'Page title', 'bellbook' ); ?></label>
					<input id="sb-setup-page-title" class="regular-text" type="text" name="page_title" maxlength="191" value="<?php esc_attr_e( 'Book an appointment', 'bellbook' ); ?>">
				</p>
				<p class="sb-hint"><?php esc_html_e( 'To put the form on another page instead, add the [bellbook] shortcode to it.', 'bellbook' ); ?></p>
			<?php endif; ?>
		</fieldset>

		<p class="sb-setup__error" role="alert" data-sb-wizard-error hidden></p>

		<div class="sb-form-actions sb-setup__nav">
			<button type="button" class="sb-button sb-button--secondary" data-sb-wizard-back hidden><?php esc_html_e( 'Back', 'bellbook' ); ?></button>
			<button type="button" class="sb-button" data-sb-wizard-next><?php esc_html_e( 'Next', 'bellbook' ); ?></button>
			<button type="submit" class="sb-button" data-sb-wizard-finish><?php esc_html_e( 'Finish setup', 'bellbook' ); ?></button>
			<?php if ( ! $rerun ) : ?>
				<button type="button" class="sb-button sb-button--ghost sb-setup__skip" data-sb-wizard-skip><?php esc_html_e( 'Skip setup', 'bellbook' ); ?></button>
			<?php endif; ?>
		</div>
	</form>

	<section class="sb-card sb-setup__done" data-sb-wizard-done hidden tabindex="-1" aria-labelledby="sb-setup-done-title">
		<h2 id="sb-setup-done-title" class="sb-step__title"><?php esc_html_e( 'You\'re ready to take bookings', 'bellbook' ); ?></h2>
		<p data-sb-wizard-summary></p>
		<div class="sb-form-actions">
			<a class="sb-button" href="#" target="_blank" rel="noopener" data-sb-wizard-page hidden><?php esc_html_e( 'View your booking page', 'bellbook' ); ?></a>
			<a class="sb-button sb-button--secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-dashboard' ) ); ?>"><?php esc_html_e( 'Go to the dashboard', 'bellbook' ); ?></a>
		</div>
		<h3 class="sb-card__title"><?php esc_html_e( 'Next, when you\'re ready', 'bellbook' ); ?></h3>
		<ul class="sb-setup__next">
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=sb-staff' ) ); ?>"><?php esc_html_e( 'Add staff photos and their own hours', 'bellbook' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=sb-services' ) ); ?>"><?php esc_html_e( 'Add descriptions and prices to services', 'bellbook' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=sb-notifications' ) ); ?>"><?php esc_html_e( 'Check the emails customers receive', 'bellbook' ); ?></a></li>
		</ul>
	</section>
</div>
