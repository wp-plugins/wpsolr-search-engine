<?php

/**
 * Base class for all WPSOLR extensions.
 * An extension is an encapsulation of a plugin that (if configured) might extend some features of WPSOLR.
 */
class WpSolrExtensions {

	/*
    * Private constants
    */
	const _CONFIG_EXTENSION_CLASS_NAME = 'config_extension_class_name';
	const _CONFIG_PLUGIN_CLASS_NAME = 'config_plugin_class_name';
	const _CONFIG_EXTENSION_FILE_PATH = 'config_extension_file_path';
	const _CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH = 'config_extension_admin_options_file_path';
	const _CONFIG_OPTIONS = 'config_extension_options';
	const _CONFIG_OPTIONS_DATA = 'data';
	const _CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME = 'is_active_field';

	const _SOLR_OR_OPERATOR = ' OR ';
	const _SOLR_AND_OPERATOR = ' AND ';

	const _METHOD_CUSTOM_QUERY = 'set_custom_query';

	/*
	 * Public constants
	 */
	// Extension: Groups
	const EXTENSION_GROUPS = 'Groups';

	// Extension: s2member
	const EXTENSION_S2MEMBER = 'S2Member';


	// Action to add custom query fields to a Solr select query
	const ACTION_SOLR_ADD_QUERY_FIELDS = 'wpsolr_action_solr_add_query_fields';

	// Filter to add custom infos to a document returned by Solr
	const FILTER_SOLR_DOCUMENT_ADD_GROUPS = 'wpsolr_filter_solr_document';

	// Filter on a document custom fields
	const FILTER_SOLR_DOCUMENT_CUSTOM_FIELD = 'wpsolr_filter_solr_document_custom_fields';

	/*
	 * Extensions configuration
	 */
	private static $extensions_array = array(
		self::EXTENSION_GROUPS   =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'WpSolrGroups',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'Groups_WordPress',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'groups/wpsolr-groups.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'groups/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_groups_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_S2MEMBER =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'WpSolrS2Member',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'c_ws_plugin__s2member_utils_s2o',
				self::_CONFIG_EXTENSION_FILE_PATH               => 's2member/wpsolr-s2member.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 's2member/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_s2member_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			)
	);

	/*
	 * Array of active extension objects
	 */
	private $extension_objects = array();

	/**
	 * Constructor.
	 */
	function __construct() {

		// Instantiate active extensions.
		$this->extension_objects = $this->instantiate_active_extension_objects();

	}

	/**
	 * Instantiate all active extension classes
	 *
	 * @return array extension objects instantiated
	 */
	private function instantiate_active_extension_objects() {

		$extension_objects = array();

		foreach ( $this->get_extensions_active() as $extension_class_name ) {

			$extension_objects[] = new $extension_class_name();
		}

		return $extension_objects;
	}

	/**
	 * Returns all extension class names which plugins are active. And load them.
	 *
	 * @return array[string]
	 */
	public function get_extensions_active() {
		$results = array();

		foreach ( self::$extensions_array as $key => $class ) {

			if ( $this->require_once_wpsolr_extension( $key, false ) ) {

				$results[] = $class[ self::_CONFIG_EXTENSION_CLASS_NAME ];
			}
		}

		return $results;
	}

	/**
	 * Include the admin options extension file.
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public static function require_once_wpsolr_extension_admin_options( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Called from admin: we active the extension, whatever.
		require_once plugin_dir_path( __FILE__ ) . $extension_config_array[ self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH ];

	}

	/**
	 * Is the extension's plugin active ?
	 *
	 * @param $extension
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Is extension's plugin installed and activated ?
		return class_exists( $extension_config_array[ self::_CONFIG_PLUGIN_CLASS_NAME ] );
	}

	public static function update_custom_field_capabilities( $custom_field_name ) {

		// Get options contening custom fields
		$array_wdm_solr_form_data = get_option( 'wdm_solr_form_data' );

		// is extension active checked in options ?
		$extension_is_active = self::is_extension_option_activate( self::EXTENSION_GROUPS );


		if ( $extension_is_active
		     && ! self::get_custom_field_capabilities( $custom_field_name )
		     && isset( $array_wdm_solr_form_data )
		     && isset( $array_wdm_solr_form_data['cust_fields'] )
		) {

			$custom_fields = explode( ',', $array_wdm_solr_form_data['cust_fields'] );

			$custom_field_capabilities = $custom_fields[ $custom_field_name ];

			if ( ! isset( $custom_field_capabilities ) ) {

				$custom_fields[ $custom_field_name ] = $custom_field_name;

				$custom_fields_str = implode( ',', $custom_fields );

				$array_wdm_solr_form_data['cust_fields'] = $custom_fields_str;

				update_option( 'wdm_solr_form_data', $array_wdm_solr_form_data );
			}
		}
	}

	/**
	 * Is the extension activated ?
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public static function is_extension_option_activate( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = get_option( $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		if ( isset( $extension_options_array ) && isset( $extension_options_array[ $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) {
			return true;
		}

		return false;
	}

	public
	static function get_custom_field_capabilities(
		$custom_field_name
	) {

		// Get custom fields selected for indexing
		$array_options     = get_option( 'wdm_solr_form_data' );
		$array_cust_fields = explode( ',', $array_options['cust_fields'] );

		if ( ! is_array( $array_cust_fields ) ) {
			return false;
		}

		return false !== array_search( $custom_field_name, $array_cust_fields );
	}


	/*
	 * If extension is active, check its custom field in indexing options
	 */

	/**
	 * Include the extension file.
	 * If called from admin, always do.
	 * Else, do it if the extension options say so, and the extension's plugin is activated.
	 *
	 * @param string $extension
	 * @param bool $is_admin
	 *
	 * @return bool
	 */
	public static function require_once_wpsolr_extension( $extension, $is_admin = false ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		if ( $is_admin ) {
			// Called from admin: we active the extension, whatever.
			require_once plugin_dir_path( __FILE__ ) . $extension_config_array[ self::_CONFIG_EXTENSION_FILE_PATH ];

			return true;
		}

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = get_option( $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		if ( ! isset( $extension_options_array ) || ! isset( $extension_options_array[ $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) {
			return false;
		}

		// Is extension's plugin installed and activated ?
		$result = class_exists( $extension_config_array[ self::_CONFIG_PLUGIN_CLASS_NAME ] );

		if ( $result ) {
			// Load extension's plugin
			require_once plugin_dir_path( __FILE__ ) . $extension_config_array[ self::_CONFIG_EXTENSION_FILE_PATH ];
		}

		return $result;
	}

	/**
	 * Get the option data of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	protected function get_option_data( $extension ) {

		return get_option( self::$extensions_array[ $extension ][ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );
	}

}