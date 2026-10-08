<?php
/**
 * Custom field inputs, shared by the public booking form and the admin booking dialog.
 * Each field lists its services in data-services; JavaScript hides and disables fields
 * that don't apply to the chosen service (disabled fields aren't validated or sent).
 *
 * @var array  $fields       SB_Custom_Fields::all()
 * @var string $id_prefix    unique per form
 * @var string $group_class  wrapper class
 * @var string $input_class  class for inputs
 * @var bool   $use_required whether required fields get the HTML required attribute
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
foreach ( $fields as $field ) :
	$id       = $id_prefix . '-' . $field['id'];
	$name     = 'custom[' . $field['id'] . ']';
	$required = $use_required && $field['required'] ? 'required' : '';
	?>
	<div class="<?php echo esc_attr( $group_class ); ?>" data-sb-field data-services="<?php echo esc_attr( implode( ',', $field['services'] ) ); ?>">
		<?php if ( 'checkbox' === $field['type'] ) : ?>
			<label class="sb-check-label">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php echo esc_attr( $required ); ?>>
				<?php echo esc_html( $field['label'] ); ?>
			</label>
		<?php else : ?>
			<label for="<?php echo esc_attr( $id ); ?>">
				<?php echo esc_html( $field['label'] ); ?>
				<?php if ( ! $field['required'] ) : ?>
					<span class="sb-optional"><?php esc_html_e( '(optional)', 'counterslot' ); ?></span>
				<?php endif; ?>
			</label>
			<?php if ( 'textarea' === $field['type'] ) : ?>
				<textarea id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $input_class ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3" maxlength="2000" <?php echo esc_attr( $required ); ?>></textarea>
			<?php elseif ( 'select' === $field['type'] ) : ?>
				<select id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $input_class ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $required ); ?>>
					<option value=""><?php esc_html_e( 'Choose…', 'counterslot' ); ?></option>
					<?php foreach ( $field['options'] as $option ) : ?>
						<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $input_class ); ?>" name="<?php echo esc_attr( $name ); ?>" type="<?php echo 'date' === $field['type'] ? 'date' : 'text'; ?>" <?php echo 'text' === $field['type'] ? 'maxlength="2000"' : ''; ?> <?php echo esc_attr( $required ); ?>>
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
