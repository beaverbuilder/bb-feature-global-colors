<?php

/**
 * @since 2.6
 */
class FLBuilderCoreFieldConnections {

	/**
 * Cache data for field connections menus.
 *
 * @since 1.0
 * @access private
 * @var array $menu_data
 */
	static private $menu_data = array();

	/**
	 * An array of cached settings that have been connected.
	 *
	 * @since 1.0
	 * @access private
	 * @var array $connected_settings
	 */
	static private $connected_settings = array();

	/**
	 * An array of cached page data object keys.
	 *
	 * @since 1.0
	 * @access private
	 * @var array $page_data_object_keys
	 */
	static private $page_data_object_keys = array();


	public static function init() {

		add_filter( 'fl_builder_register_settings_form', __CLASS__ . '::global_options', 9, 2 );

		add_filter( 'fl_builder_node_settings', __CLASS__ . '::connect_node_settings', 10, 2 );

		//	if ( ! class_exists( 'FLThemeBuilderFieldConnections' ) ) {
			add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_scripts' );
			add_action( 'wp_footer', __CLASS__ . '::js_templates' );
			add_action( 'fl_builder_before_control', __CLASS__ . '::render_connection', 10, 4 );
		//	}
		
		FLBuilderAJAX::add_action( 'get_connection_color', __CLASS__ . '::get_connection_color', array( 'property' ) );
	}

	public static function enqueue_scripts() {
		$slug = 'fl-theme-builder-field-connections';

		if ( FLBuilderModel::is_builder_active() ) {

			wp_enqueue_style( $slug, FL_BUILDER_FIELD_CONNECTIONS_URL . 'css/' . $slug . '.css', array(), FL_BUILDER_VERSION );
			wp_enqueue_style( 'tether', FL_BUILDER_FIELD_CONNECTIONS_URL . 'css/tether.min.css', array(), FL_BUILDER_VERSION );

			wp_enqueue_script( $slug, FL_BUILDER_FIELD_CONNECTIONS_URL . 'js/' . $slug . '.js', array( 'jquery' ), FL_BUILDER_VERSION );
			wp_enqueue_script( 'tether', FL_BUILDER_FIELD_CONNECTIONS_URL . 'js/tether.min.js', array( 'jquery' ), FL_BUILDER_VERSION );
		}
	}

	public static function js_templates() {
		if ( FLBuilderModel::is_builder_active() ) {
			include FL_BUILDER_FIELD_CONNECTIONS_DIR . 'includes/field-connection-js-templates.php';
		}
	}

	public static function render_connection( $name, $value, $field, $settings ) {
		global $post;

		if ( ! isset( $field['connections'] ) || ! $field['connections'] ) {
			return;
		}

		$properties = FLBuilderCoreFieldConnectionsData::get_properties();
		$objects    = self::get_page_data_object_keys( $post->ID );
		$connection = false;
		$form       = false;
		$menu_data  = self::get_menu_data( $objects, $field['connections'] );

		if ( isset( $settings->connections ) ) {

			$settings->connections = (array) $settings->connections;

			if ( isset( $settings->connections[ $name ] ) ) {

				if ( is_string( $settings->connections[ $name ] ) ) {
					$settings->connections[ $name ] = json_decode( $settings->connections[ $name ] );
				}
				if ( is_object( $settings->connections[ $name ] ) && in_array( $settings->connections[ $name ]->object, $objects ) ) {
					$connection = $settings->connections[ $name ];
				}
			}
		}

		if ( $connection ) {

			if ( ! isset( $properties[ $connection->object ][ $connection->property ] ) ) {
				$connection = false;
			} elseif ( ! self::property_supports_post_type( $properties[ $connection->object ][ $connection->property ] ) ) {
				$connection = false;
			} else {
				$property = FLBuilderCoreFieldConnectionsData::get_property( $connection->object, $connection->property );
				$form     = $property['form'] ? $property['form']['id'] : false;
			}
		}

		if ( ! empty( $menu_data ) ) {
			include FL_BUILDER_FIELD_CONNECTIONS_DIR . 'includes/field-connection.php';
		}
	}

	public static function get_page_data_object_keys( $post_id ) {
		if ( isset( self::$page_data_object_keys[ $post_id ] ) ) {
			return self::$page_data_object_keys[ $post_id ];
		}

		$layout_type = get_post_meta( $post_id, '_fl_theme_layout_type', true );

		if ( 'singular' == $layout_type ) {
			$keys = array( 'post', 'site' );
		} else {
			$keys = array( 'archive', 'post', 'site' );
		}

		self::$page_data_object_keys[ $post_id ] = $keys;

		return $keys;
	}

	public static function get_menu_data( $objects = array( 'site' ), $connections = array() ) {
		global $post;

		$cache_key = implode( '_', $objects ) . '_' . implode( '_', $connections );

		if ( isset( self::$menu_data[ $cache_key ] ) ) {
			return self::$menu_data[ $cache_key ];
		}

		$groups     = FLBuilderCoreFieldConnectionsData::get_groups();
		$properties = FLBuilderCoreFieldConnectionsData::get_properties();
		$menu       = array();

		// Add groups to the menu data.
		foreach ( $groups as $group_key => $group ) {
			$menu[ $group_key ] = array(
				'label'      => $group['label'],
				'properties' => array(
					'archive' => array(),
					'post'    => array(),
					'site'    => array(),
				),
			);
		}

		// Add properties to the menu data.
		foreach ( $objects as $object ) {

			foreach ( $properties[ $object ] as $key => $data ) {

				if ( ! self::property_supports_post_type( $data ) ) {
					continue;
				} elseif ( is_array( $data['type'] ) ) {
					if ( 0 === count( array_intersect( $data['type'], $connections ) ) ) {
						continue;
					}
				} elseif ( 'all' != $data['type'] && ! in_array( $data['type'], $connections ) ) {
					continue;
				}

				$menu[ $data['group'] ]['properties'][ $data['object'] ][ $key ] = $data;
			}
		}

		// Remove any empty groups from the menu data.
		foreach ( $menu as $group_key => $group ) {

			$no_archive = 0 === count( $group['properties']['archive'] ); // @codingStandardsIgnoreLine
			$no_post    = 0 === count( $group['properties']['post'] ); // @codingStandardsIgnoreLine
			$no_site    = 0 === count( $group['properties']['site'] ); // @codingStandardsIgnoreLine

			if ( $no_archive && $no_post && $no_site ) {
				unset( $menu[ $group_key ] );
			}
		}

		self::$menu_data[ $cache_key ] = $menu;
		return $menu;
	}

	public static function get_color( $key = false ) {
		$global_settings = FLBuilderModel::get_global_settings();
		$key             = str_replace( 'global_color_', '', $key );
		return $global_settings->{$key};
	}
	
	public static function get_connection_color( $property ) {
		echo FLBuilderColor::hex_or_rgb( FLBuilderCoreFieldConnections::get_color( $property ) );
		exit();
	}

	public static function property_supports_post_type( $property ) {
		global $post;

		if ( 'post' == $property['object'] && 'all' != $property['post_type'] ) {
			if ( is_array( $property['post_type'] ) && ! in_array( $post->post_type, $property['post_type'] ) ) {
				return false;
			} elseif ( $post->post_type != $property['post_type'] ) {
				return false;
			}
		}

		return true;
	}

	static public function render_label( $connection = false ) {
		if ( ! $connection ) {
			return;
		}

		$properties = FLBuilderCoreFieldConnectionsData::get_properties();

		echo $properties[ $connection->object ][ $connection->property ]['label'];
	}

	public static function connect_node_settings( $settings, $node ) {
		global $post, $wp_the_query;
		$repeater = array();
		$nested   = array();

		// Get the connection cache key.
		if ( is_object( $wp_the_query->post ) && 'fl-theme-layout' === $wp_the_query->post->post_type ) {
			$cache_key = $node->node;
		} else {
			$cache_key = $post && isset( $post->ID ) ? $node->node . '_' . $post->ID : $node->node;
		}
		// check for bb loop
		if ( isset( self::$in_post_grid_loop ) ) {
			if ( self::$in_post_grid_loop && $post && isset( $post->ID ) ) {
				$cache_key = $node->node . '_' . $post->ID;
			}
		}

		/**
		 * @since 1.3.1
		 * @see fl_themer_builder_connect_node_settings_cache_key
		 */
		$cache_key = apply_filters( 'fl_themer_builder_connect_node_settings_cache_key', $cache_key, $settings, $node );

		// Gather any repeater or nested settings.
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) && count( $value ) && isset( $value[0]->connections ) ) {
				$repeater[] = $key;
			} elseif ( is_object( $value ) && isset( $value->connections ) ) {
				$nested[] = $key;
			}
		}
			// Return if we don't have connections.
		if ( ! isset( $settings->connections ) && empty( $repeater ) && empty( $nested ) ) {
			return $settings;
		}
			// Return if connecting isn't allowed right now.
		if ( ! self::is_connecting_allowed() ) {
			return $settings;
		}
			// Return cached connections?
		if ( isset( self::$connected_settings[ $cache_key ] ) ) {
			return self::$connected_settings[ $cache_key ];
		}

		// Connect the main settings object.
		$settings = self::connect_settings( $settings );

		// Connect any repeater settings.
		foreach ( $repeater as $key ) {
			for ( $i = 0; $i < count( $settings->$key ); $i++ ) {
				$settings->{ $key }[ $i ] = self::connect_settings( $settings->{ $key }[ $i ] );
			}
		}

		// Connect any nested settings.
		foreach ( $nested as $key ) {
			$settings->{ $key } = self::connect_settings( $settings->{ $key } );
		}

		// Cache the connected settings.
		self::$connected_settings[ $cache_key ] = $settings;
		return $settings;
	}

	static public function connect_settings( $settings ) {
		global $post;

		// Return if we don't have connections.
		if ( ! isset( $settings->connections ) ) {
			return $settings;
		}

		// Loop through the settings and connect them.
		foreach ( $settings->connections as $key => $data ) {

			if ( is_string( $data ) ) {
				$data = json_decode( $data );
			}

			if ( ! empty( $data ) && is_object( $data ) ) {

				$property       = FLBuilderCoreFieldConnectionsData::get_property( $data->object, $data->property );
				$data->settings = isset( $data->settings ) ? $data->settings : null;

				if ( ! $property ) {
					continue;
				} elseif ( isset( $property['placeholder'] ) && is_object( $post ) && 'fl-theme-layout' == $post->post_type ) {
					$settings->{ $key } = $property['placeholder'];
				} else {
					$settings->{ $key } = FLBuilderCoreFieldConnectionsData::get_value( $data->object, $data->property, $data->settings );
				}

				if ( 'photo' == $data->field ) {

					if ( is_array( $settings->{ $key } ) ) {
						$settings->{ $key . '_src' } = $settings->{ $key }['url'];
						$settings->{ $key }          = $settings->{ $key }['id'];
					} else {
						$settings->{ $key . '_src' } = $settings->{ $key };
						$settings->{ $key }          = -1;
					}
				}
			}
		}

		return $settings;
	}

	static public function is_connecting_allowed() {
		if ( defined( 'DOING_AJAX' ) ) {

			if ( FLBuilderModel::is_builder_active() ) {

				$action = 'fl_builder_before_render_ajax_layout';

				if ( doing_action( $action ) || did_action( $action ) ) {
					return true;
				} else {
					return false;
				}
			}
		}

		return ! is_admin();
	}

	static public function global_options( $form, $id ) {

		if ( 'global' == $id ) {
			$form['tabs']['colors'] = array(
				'title'       => 'Colors',
				'description' => 'Some custom colors.',
				'sections'    => array(
					'global_colors' => array(
						'title'  => 'Colours',
						'fields' => self::get_color_fields(),
					),
				),
			);
		}
		return $form;
	}

	public static function get_color_fields() {

		$fields = array();

		$colors = apply_filters( 'fl_global_colors', array(
			'color_primary'   => __( 'Primary Color', 'fl-builder' ),
			'color_secondary' => __( 'Secondary Color', 'fl-builder' ),
			'color_3'         => __( 'Color #3', 'fl-builder' ),
			'color_4'         => __( 'Color #4', 'fl-builder' ),
			'color_5'         => __( 'Color #5', 'fl-builder' ),
		) );

		foreach ( $colors as $key => $label ) {
			$fields[ $key ] = array(
				'label' => $label,
				'type'  => 'color',
			);
			FLBuilderCoreFieldConnectionsData::add_site_property( 'global_color_' . $key, array(
				'label'  => $label,
				'group'  => 'site',
				'type'   => 'color',
				'getter' => 'FLBuilderCoreFieldConnections::get_color',
			) );
		}
		return $fields;
	}
}

FLBuilderCoreFieldConnections::init();
