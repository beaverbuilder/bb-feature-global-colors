<?php
/*
Plugin Name: Beaver Builder Global Colours
Description: This is a feature test plugin
Author: <Simon>
Version: 1.0
*/
class BB_Global_colours {
	function __construct() {
		add_action( 'after_setup_theme', function() {
			define( 'FL_BUILDER_FIELD_CONNECTIONS_DIR', __DIR__ . '/extensions/fl-builder-field-connections/' );
			define( 'FL_BUILDER_FIELD_CONNECTIONS_URL', plugins_url( '/extensions/fl-builder-field-connections/', __FILE__ ) );
			require_once FL_BUILDER_FIELD_CONNECTIONS_DIR . 'classes/class-fl-page-data.php';
			require_once FL_BUILDER_FIELD_CONNECTIONS_DIR . 'classes/class-fl-builder-field-connections.php';
		});
	}
}
new BB_Global_colours;
