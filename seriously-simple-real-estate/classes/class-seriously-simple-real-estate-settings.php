<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Seriously_Simple_Real_Estate_Settings {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );

		// Register plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'add_settings_link' ) );
	}

	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=property', __( 'Property Settings', 'ss_realestate' ), __( 'Settings', 'ss_realestate' ), 'manage_options', 'property_settings', array( $this, 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="edit.php?post_type=property&page=property_settings">' . __( 'Settings', 'ss_realestate' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	public function register_settings() {

		// Add settings section
		add_settings_section( 'main_settings', __( 'Modify property settings', 'ss_realestate' ), array( $this, 'main_settings' ), 'property_settings' );

		// Add settings fields
		add_settings_field( 'property_search_form_title', __( 'Seatch form title:', 'ss_realestate' ), array( $this, 'property_search_form_title' ) , 'property_settings', 'main_settings' );

		// Register settings fields
		register_setting( 'property_settings', 'property_search_form_title', array( $this, 'validate_option' ) );

	}

	public function main_settings() { echo '<p>' . __( 'Change these options to customise how your properties are displayed.', 'ss_realestate' ) . '</p>'; }

	public function property_search_form_title() {

		$option = get_option( 'property_search_form_title' );

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="slug" type="text" name="property_search_form_title" value="' . $data . '"/>
				<label for="slug"><span class="description">' . __( 'The title of the property search form.', 'ss_realestate' ) . '</span></label>';
	}

	public function validate_option( $data ) {
		if( $data && strlen( $data ) > 0 && $data != '' ) {
			return $data;
		}
		return '';
	}

	public function settings_page() {

		echo '<div class="wrap">
				<div class="icon32" id="property_settings-icon"><br/></div>
				<h2>' . __( 'Property Settings', 'ss_realestate' ) . '</h2>
				<form method="post" action="options.php" enctype="multipart/form-data">';

				settings_fields( 'property_settings' );
				do_settings_sections( 'property_settings' );

			  echo '<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'ss_realestate' ) ) . '" />
					</p>
				</form>
			  </div>';
	}

}