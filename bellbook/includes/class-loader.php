<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects actions and registers them with WordPress in one go.
 */
class SB_Loader {

	private array $actions = [];

	public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = [ $hook, [ $component, $callback ], $priority, $accepted_args ];
	}

	public function run(): void {
		foreach ( $this->actions as [ $hook, $callback, $priority, $accepted_args ] ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
