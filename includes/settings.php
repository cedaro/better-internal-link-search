<?php
/**
 * Class for registering settings.
 *
 * @package Better_Internal_Link_Search
 *
 * @since 1.2.0
 */
class Better_Internal_Link_Search_Settings {
	/**
	 * Load the plugin settings.
	 *
	 * @since 1.2.0
	 */
	function load() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}
	
	/**
	 * Register plugin settings.
	 *
	 * Adds a setting to enable the text selected in the editor to be searched
	 * automatically. This was the default behavior in versions prior to
	 * version 1.1.2, but it can cause a delay on large sites.
	 *
	 * @since 1.1.2
	 */
	public static function register_settings() {
		register_setting( 'writing', 'better_internal_link_search' );
		
		add_settings_section( 'better-internal-link-search', __( 'Internal Linking', 'better-internal-link-search-i18n' ), '__return_null', 'writing' );

		add_settings_field(
			'extensions',
			__( 'Automatic Search', 'better-internal-link-search-i18n' ),
			array( __CLASS__, 'automatic_internal_link_search_field' ),
			'writing',
			'better-internal-link-search'
		);
	}
	
	/**
	 * Automatic search setting field.
	 *
	 * @since 1.1.2
	 */
	public static function automatic_internal_link_search_field() {
		$settings = self::get_settings();
		?>
		<input type="checkbox" name="better_internal_link_search[automatically_search_selection]" id="better-internal-link-search-automatically-search-selection" value="yes"<?php checked( $settings['automatically_search_selection'], 'yes' ); ?>>
		<label for="better-internal-link-search-automatically-search-selection"><?php _e( 'Automatically search for text selected in the editor when opening the internal link manager?', 'better-internal-link-search-i18n' ); ?></label>
		<?php
	}
	
	/**
	 * Retrieve the plugin settings.
	 *
	 * @since 1.1.2
	 */
	public static function get_settings() {
		$settings = wp_parse_args( (array) get_option( 'better_internal_link_search' ), array(
			'automatically_search_selection' => 'no'
		) );
		
		return $settings;
	}
}
?>