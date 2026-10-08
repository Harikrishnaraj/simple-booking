<?php
/**
 * [counterslot_events]: upcoming events, each with places left and a registration form.
 *
 * @var array $events SB_Events::get_all( true )
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$date_fmt = get_option( 'date_format' );
?>
<div class="sb-events" data-sb-events>
	<?php if ( ! $events ) : ?>
		<p class="sb-booking-wrapper"><?php esc_html_e( 'There are no upcoming events at the moment.', 'counterslot' ); ?></p>
	<?php endif; ?>
	<?php foreach ( $events as $e ) : ?>
		<?php
		$left = max( 0, (int) $e['capacity'] - (int) $e['taken'] );
		$uid  = 'sb-ev-' . (int) $e['id'];
		?>
		<article class="sb-booking-wrapper sb-event-card" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
			<div class="sb-event-card__date" aria-hidden="true">
				<span class="sb-day-month"><?php echo esc_html( mysql2date( 'M', $e['event_date'] ) ); ?></span>
				<span class="sb-day-num"><?php echo esc_html( mysql2date( 'j', $e['event_date'] ) ); ?></span>
			</div>
			<div class="sb-event-card__body">
				<h3 class="sb-booking-title" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo esc_html( $e['name'] ); ?></h3>
				<p class="sb-event-card__meta">
					<?php echo esc_html( mysql2date( 'l, ' . $date_fmt, $e['event_date'] ) . ' · ' . substr( $e['start_time'], 0, 5 ) . '–' . substr( $e['end_time'], 0, 5 ) ); ?>
					<?php if ( $e['location_name'] ) : ?><br><?php echo esc_html( $e['location_name'] . ( $e['location_address'] ? ', ' . strtok( $e['location_address'], "\n" ) : '' ) ); ?><?php endif; ?>
					<?php if ( $e['staff_name'] ) : ?><br><?php echo esc_html( sprintf( __( 'With %s', 'counterslot' ), $e['staff_name'] ) ); ?><?php endif; ?>
				</p>
				<?php if ( $e['description'] ) : ?>
					<p><?php echo nl2br( esc_html( $e['description'] ) ); ?></p>
				<?php endif; ?>
				<p class="sb-event-card__price">
					<strong><?php echo (float) $e['price'] > 0 ? esc_html( sb_price( $e['price'] ) ) : esc_html__( 'Free', 'counterslot' ); ?></strong>
					·
					<?php echo $left ? esc_html( sprintf( _n( '%d place left', '%d places left', $left, 'counterslot' ), $left ) ) : esc_html__( 'Full', 'counterslot' ); ?>
				</p>

				<?php if ( $left ) : ?>
					<details class="sb-event-register">
						<summary class="sb-btn-primary"><?php esc_html_e( 'Register', 'counterslot' ); ?></summary>
						<form class="sb-event-form" novalidate>
							<input type="hidden" name="event_id" value="<?php echo (int) $e['id']; ?>">
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
							<div class="sb-form-group">
								<label for="<?php echo esc_attr( $uid ); ?>-spots"><?php esc_html_e( 'Places', 'counterslot' ); ?></label>
								<input id="<?php echo esc_attr( $uid ); ?>-spots" class="sb-form-control sb-spots" name="spots" type="number" min="1" max="<?php echo (int) min( 20, $left ); ?>" value="1" required>
							</div>
							<div class="sb-hp" aria-hidden="true">
								<label for="<?php echo esc_attr( $uid ); ?>-website">Website</label>
								<input id="<?php echo esc_attr( $uid ); ?>-website" name="website" type="text" tabindex="-1" autocomplete="off">
							</div>
							<button type="submit" class="sb-btn-primary"><?php esc_html_e( 'Confirm registration', 'counterslot' ); ?></button>
							<?php if ( (float) $e['price'] > 0 ) : ?>
								<p class="sb-hint"><?php esc_html_e( 'You pay at the event.', 'counterslot' ); ?></p>
							<?php endif; ?>
						</form>
					</details>
				<?php endif; ?>
				<div class="sb-message" role="status" aria-live="polite" tabindex="-1" hidden></div>
			</div>
		</article>
	<?php endforeach; ?>
</div>
