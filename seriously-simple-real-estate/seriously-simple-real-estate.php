<?php
/*
 * Plugin Name: Seriously Simple Real Estate
 * Version: 1.0
 * Plugin URI: http://www.woothemes.com/
 * Description: A demo WordPress plugin for Software Developer's Journal
 * Author: Hugh Lashbrooke & Jeffrey Pearce
 * Author URI: http://www.woothemes.com/
 * Requires at least: 3.0
 * Tested up to: 3.6
 *
 * @package WordPress
 * @author Hugh Lashbrooke & Jeffrey Pearce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include plugin class files
require_once( 'classes/class-seriously-simple-real-estate.php' );
require_once( 'classes/class-seriously-simple-real-estate-settings.php' );

// Instantiate necessary classes
global $ssre;
$ssre = new Seriously_Simple_Real_Estate( __FILE__ );
$ssre_settings = new Seriously_Simple_Real_Estate_Settings( __FILE__ );