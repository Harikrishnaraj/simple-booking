<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Activator {

	public static function activate(): void {
		SB_Database::create_tables();
	}
}
