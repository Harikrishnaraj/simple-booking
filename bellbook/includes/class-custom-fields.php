<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra questions on the booking form ("intake form"). The field list is a small option;
 * each booking stores its answers with the label as it was, so renaming or deleting a
 * field later doesn't change past bookings.
 */
class SB_Custom_Fields {

	private const OPTION_KEY = 'sb_custom_fields';
	private const MAX_LENGTH = 2000;

	public static function types(): array {
		return [
			'text'     => __( 'Short text', 'bellbook' ),
			'textarea' => __( 'Long text', 'bellbook' ),
			'select'   => __( 'Dropdown', 'bellbook' ),
			'checkbox' => __( 'Checkbox (yes/no)', 'bellbook' ),
			'date'     => __( 'Date', 'bellbook' ),
		];
	}

	/**
	 * @return array<int, array{id: string, label: string, type: string, required: bool, options: string[], services: int[]}>
	 */
	public static function all(): array {
		return array_values( (array) get_option( self::OPTION_KEY, [] ) );
	}

	/**
	 * Fields shown for a service (a field with no services is shown for all).
	 */
	public static function for_service( int $service_id ): array {
		return array_values( array_filter( self::all(), fn( $f ) => ! $f['services'] || in_array( $service_id, $f['services'], true ) ) );
	}

	/**
	 * Create (empty id) or update a field. Returns an error message, or '' on success.
	 */
	public static function save( array $in ): string {
		$label = sanitize_text_field( (string) ( $in['label'] ?? '' ) );
		$type  = sanitize_key( (string) ( $in['type'] ?? '' ) );
		if ( '' === $label || mb_strlen( $label ) > 191 ) {
			return __( 'Please enter a question.', 'bellbook' );
		}
		if ( ! isset( self::types()[ $type ] ) ) {
			return __( 'Please choose a field type.', 'bellbook' );
		}
		$options = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', preg_split( '/\R/', (string) ( $in['options'] ?? '' ) ) ) ) ) );
		if ( 'select' === $type && count( $options ) < 2 ) {
			return __( 'A dropdown needs at least two choices, one per line.', 'bellbook' );
		}

		$field = [
			'id'       => sanitize_key( (string) ( $in['id'] ?? '' ) ) ?: 'f' . strtolower( wp_generate_password( 8, false ) ),
			'label'    => $label,
			'type'     => $type,
			'required' => ! empty( $in['required'] ),
			'options'  => 'select' === $type ? $options : [],
			'services' => array_values( array_unique( array_filter( array_map( 'absint', (array) ( $in['services'] ?? [] ) ) ) ) ),
		];

		$fields = self::all();
		$index  = array_search( $field['id'], array_column( $fields, 'id' ), true );
		if ( false === $index ) {
			$fields[] = $field;
		} else {
			$fields[ $index ] = $field;
		}
		update_option( self::OPTION_KEY, $fields, false );
		return '';
	}

	public static function delete( string $id ): void {
		update_option( self::OPTION_KEY, array_values( array_filter( self::all(), fn( $f ) => $f['id'] !== $id ) ), false );
	}

	/**
	 * Move a field one place up (-1) or down (+1).
	 */
	public static function move( string $id, int $direction ): void {
		$fields = self::all();
		$from   = array_search( $id, array_column( $fields, 'id' ), true );
		$to     = false === $from ? -1 : $from + ( $direction < 0 ? -1 : 1 );
		if ( $to >= 0 && $to < count( $fields ) ) {
			[ $fields[ $from ], $fields[ $to ] ] = [ $fields[ $to ], $fields[ $from ] ];
			update_option( self::OPTION_KEY, $fields, false );
		}
	}

	/**
	 * Validate answers ($in['custom'][field id]) for a service.
	 *
	 * @return array<int, array{id: string, label: string, value: string}>|WP_Error answered fields only
	 */
	public static function answers( array $in, int $service_id, bool $enforce_required = true ): array|WP_Error {
		$given = (array) ( $in['custom'] ?? [] );
		$out   = [];
		foreach ( self::for_service( $service_id ) as $field ) {
			$raw = $given[ $field['id'] ] ?? '';
			$raw = is_array( $raw ) ? '' : (string) $raw;

			switch ( $field['type'] ) {
				case 'textarea':
					$value = sanitize_textarea_field( $raw );
					break;
				case 'checkbox':
					$value = '' !== $raw ? __( 'Yes', 'bellbook' ) : '';
					break;
				case 'select':
					$value = in_array( $raw, $field['options'], true ) ? $raw : '';
					break;
				case 'date':
					$date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $raw );
					$value = $date && $date->format( 'Y-m-d' ) === $raw ? $raw : '';
					break;
				default:
					$value = sanitize_text_field( $raw );
			}

			if ( mb_strlen( $value ) > self::MAX_LENGTH ) {
				/* translators: %s: question */
				return new WP_Error( 'custom_field', sprintf( __( 'Your answer to "%s" is too long.', 'bellbook' ), $field['label'] ) );
			}
			if ( '' === trim( $value ) ) {
				if ( $enforce_required && $field['required'] ) {
					/* translators: %s: question */
					return new WP_Error( 'custom_field', sprintf( __( 'Please answer "%s".', 'bellbook' ), $field['label'] ) );
				}
				continue;
			}
			$out[] = [ 'id' => $field['id'], 'label' => $field['label'], 'value' => $value ];
		}
		return $out;
	}

	/**
	 * Stored answers (bookings.custom_fields JSON) as "Label: value" lines.
	 */
	public static function as_text( ?string $json ): string {
		$answers = json_decode( (string) $json, true );
		if ( ! is_array( $answers ) ) {
			return '';
		}
		return implode( "\n", array_map( fn( $a ) => $a['label'] . ': ' . $a['value'], $answers ) );
	}
}
