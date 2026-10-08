<?php
/**
 * @var array          $types     SB_Notifications::types()
 * @var array          $templates key => saved template
 * @var string         $current   key of the template being edited
 * @var int|false      $next_run  next reminder cron run (timestamp)
 * @var string         $page_url
 * @var string         $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$groups = [
	'customer' => __( 'To customer', 'counterslot' ),
	'staff'    => __( 'To staff & admin', 'counterslot' ),
];
$t        = $templates[ $current ];
$edit_url = add_query_arg( 'email', $current, $page_url );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Notifications', 'counterslot' ), 'theme' => $theme ] ); ?>

	<div class="sb-split">
		<nav class="sb-card sb-split__form" aria-label="<?php esc_attr_e( 'Emails', 'counterslot' ); ?>">
			<?php foreach ( $groups as $audience => $heading ) : ?>
				<h2 class="sb-card__title sb-group-title"><?php echo esc_html( $heading ); ?></h2>
				<ul class="sb-categories">
					<?php foreach ( $types as $key => $type ) : ?>
						<?php
						if ( $type['audience'] !== $audience ) {
							continue;
						}
						$on = ! empty( $templates[ $key ]['enabled'] );
						?>
						<li class="<?php echo $key === $current ? 'is-current' : ''; ?>">
							<a href="<?php echo esc_url( add_query_arg( 'email', $key, $page_url ) ); ?>" <?php echo $key === $current ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $type['label'] ); ?>
								<span class="sb-badge sb-badge--<?php echo $on ? 'active' : 'inactive'; ?>"><?php echo $on ? esc_html__( 'On', 'counterslot' ) : esc_html__( 'Off', 'counterslot' ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</nav>

		<section class="sb-card sb-split__list">
			<form data-sb-action="sb_save_template" data-sb-redirect="<?php echo esc_url( $edit_url ); ?>" data-sb-template>
				<input type="hidden" name="key" value="<?php echo esc_attr( $current ); ?>">

				<div class="sb-card__head">
					<div>
						<h2 class="sb-card__title"><?php echo esc_html( $types[ $current ]['label'] ); ?></h2>
						<p class="sb-hint"><?php echo esc_html( $types[ $current ]['when'] ); ?></p>
					</div>
					<label class="sb-switch">
						<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $t['enabled'] ) ); ?>>
						<span class="sb-switch__track" aria-hidden="true"></span>
						<?php esc_html_e( 'Send this email', 'counterslot' ); ?>
					</label>
				</div>

				<?php if ( 'customer_reminder' === $current ) : ?>
					<p class="sb-field">
						<label for="sb-t-hours"><?php esc_html_e( 'Send this many hours before the appointment', 'counterslot' ); ?></label>
						<input id="sb-t-hours" class="sb-input sb-input--short" type="number" name="hours" min="1" max="168" step="1" required value="<?php echo (int) $t['hours']; ?>">
						<span class="sb-hint">
							<?php
							esc_html_e( 'Reminders are checked every hour by WordPress\'s scheduler, which runs when someone visits the site. Bookings made inside this window get no reminder.', 'counterslot' );
							if ( $next_run ) {
								echo ' ' . esc_html( sprintf( __( 'Next check: %s.', 'counterslot' ), wp_date( get_option( 'time_format' ), $next_run ) ) );
							}
							?>
						</span>
					</p>
				<?php elseif ( 'admin_new' === $current ) : ?>
					<p class="sb-hint">
						<?php
						echo wp_kses_post( sprintf(
							/* translators: 1: email address, 2: link to the Settings page */
							__( 'Goes to %1$s. Change it in <a href="%2$s">Settings</a>.', 'counterslot' ),
							'<strong>' . esc_html( SB_Settings::get_settings()['admin_email'] ) . '</strong>',
							esc_url( admin_url( 'admin.php?page=sb-settings' ) )
						) );
						?>
					</p>
				<?php endif; ?>

				<p class="sb-field">
					<label for="sb-t-subject"><?php esc_html_e( 'Subject', 'counterslot' ); ?></label>
					<input id="sb-t-subject" class="sb-input sb-input--wide" type="text" name="subject" required value="<?php echo esc_attr( $t['subject'] ); ?>" data-sb-placeholder-target>
				</p>

				<p class="sb-field">
					<label for="sb-t-body"><?php esc_html_e( 'Message', 'counterslot' ); ?></label>
					<textarea id="sb-t-body" class="sb-input sb-input--wide sb-mono" name="body" rows="14" required data-sb-placeholder-target><?php echo esc_textarea( $t['body'] ); ?></textarea>
					<span class="sb-hint"><?php esc_html_e( 'Plain text; line breaks are kept and basic HTML is allowed. A line is left out when all its placeholders are empty, e.g. "With: {staff_name}" when no staff member is assigned.', 'counterslot' ); ?></span>
				</p>

				<fieldset class="sb-field">
					<legend class="sb-label"><?php esc_html_e( 'Placeholders', 'counterslot' ); ?></legend>
					<p class="sb-hint"><?php esc_html_e( 'Click to insert at the cursor in the subject or message.', 'counterslot' ); ?></p>
					<div class="sb-chips">
						<?php foreach ( SB_Notifications::placeholders() as $code => $label ) : ?>
							<button type="button" class="sb-chip" data-sb-insert="<?php echo esc_attr( $code ); ?>" title="<?php echo esc_attr( $label ); ?>"><code translate="no"><?php echo esc_html( $code ); ?></code></button>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<div class="sb-form-actions">
					<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'counterslot' ); ?></button>
					<button type="button" class="sb-button sb-button--secondary" data-sb-test-template>
						<?php
						/* translators: %s: the admin's email address */
						echo esc_html( sprintf( __( 'Save & send test to %s', 'counterslot' ), wp_get_current_user()->user_email ) );
						?>
					</button>
				</div>
			</form>
		</section>
	</div>
</div>
