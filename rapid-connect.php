<?php
/**
 *
 * @package   Rapid_Connect
 * @author    Bradley Beddoes <bradleybeddoes@aaf.edu.au>
 * @license   GPL-3.0
 * @link      http://rapid.aaf.edu.au
 * @copyright 2013 Australian Access Federation
 *
 * @wordpress-plugin
 * Plugin Name: AAF Rapid Connect
 * Plugin URI:  http://rapid.aaf.edu.au
 * Description: Allows subscribers of the Australian Access Federation to rapidly create collaborative Wordpress sites.
 * Version:     0.0.1
 * Author:      Bradley Beddoes
 * Author URI:  http://www.bradleybeddoes.com
 * Text Domain: rapid-connect-en
 * License:     GPL-3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-rapid-connect.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Rapid_Connect', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Rapid_Connect', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Rapid_Connect', 'get_instance' ) );
