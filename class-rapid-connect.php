<?php
/**
 * Rapid Connect.
 *
 * @package   Rapid_Connect
 * @author    Bradley Beddoes <bradleybeddoes@aaf.edu.au>
 * @license   GPL-3.0
 * @link      http://rapid.aaf.edu.au
 * @copyright 2013 Australian Access Federation
 */

include plugin_dir_path( __FILE__ ) . 'lib/JWT/Authentication/JWT.php';
use JWT\Authentication\JWT;

/**
 * Rapid_Connect.
 *
 * @package Rapid_Connect
 * @author  Bradley Beddoes <bradleybeddoes@aaf.edu.au>
 */
class Rapid_Connect {

	const VERSION = '0.1.0';
	protected $plugin_slug               = 'aaf-rapid-connect';
	protected static $instance           = null;
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
    add_action('admin_init', array( $this, 'plugin_admin_init'));

		$plugin_basename = plugin_basename( plugin_dir_path( __FILE__ ) . 'rapid-connect.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );


		// Define custom functionality.
    add_action('login_form', array( $this, 'login_rapid_connect'));

    //remove_all_filters('authenticate');
    add_filter('authenticate', array( $this, 'rapid_connect_authenticate'), 30, 3);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.1.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide  ) {
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
				}
				restore_current_blog();
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_deactivate();
				}
				restore_current_blog();
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.1.0
	 *
	 * @param	int	$blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) )
			return;

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.1.0
	 *
	 * @return	array|false	The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {
		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";
		return $wpdb->get_col( $sql );
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.1.0
	 */
	private static function single_activate() {
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 */
	private static function single_deactivate() {
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), self::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1.0
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_plugins_page(
			__( 'AAF Rapid Connect', $this->plugin_slug ),
			__( 'Menu', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    0.1.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'plugins.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

  public function plugin_admin_init() {
    register_setting( 'rapid_connect_options', 'rapid_connect_options', array( $this, 'rapid_connect_options_validate') );
    add_settings_section('rapid_connect_main', 'Registration', 'rapid_connect_main_section_text', 'rapid_connect');
    add_settings_section('rapid_connect_url', 'Security', 'rapid_connect_url_section_text', 'rapid_connect');
    add_settings_section('rapid_connect_options', 'Options', 'rapid_connect_options_section_text', 'rapid_connect');

    add_settings_field('callback', 'Callback URL', 'rapid_connect_callback_markup', 'rapid_connect', 'rapid_connect_main');
    add_settings_field('secret', 'Secret', 'rapid_connect_secret_markup', 'rapid_connect', 'rapid_connect_main');
    add_settings_field('url', 'Rapid Connect URL', 'rapid_connect_url_markup', 'rapid_connect', 'rapid_connect_url');
    add_settings_field('trusted_affiliations', 'Trusted Affiliations', 'rapid_connect_trusted_affiliations_markup', 'rapid_connect', 'rapid_connect_options');
  }

  public function rapid_connect_options_validate($input) {
    $options = get_option('rapid_connect_options');

    if(!$input['secret']) {
      $options['secret'] = $this->random_string();
    } else {
      $options['secret'] = trim($input['secret']);
    }
    $options['url'] = trim($input['url']);

    if($input['trusted_affiliations']) {
      $options['trusted_affiliations'] = explode(" ", $input['trusted_affiliations']);
    } else {
      $options['trusted_affiliations'] = null;
    }

    return $options;
  }

	/**
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    0.1.0
	 */
  public function login_rapid_connect() {
    $options = get_option('rapid_connect_options');
    $url = $options['url'];

    $default_login_markup=ob_get_clean();

    $rapid_login_markup='<div class="rapidlogin">
      <h2>Login with the AAF</h2>
      <br>
      <p>If your Organisation is a <a href="http://www.aaf.edu.au/subscribe/subscribers/">subscriber to the Australian Access Federation</a> you can seemlessly access this site.</p>
      <br>
      <p>Click on the Australian Access Federation button below and follow the directions provided to get started.</p>
      <br><center><a href="'.$url.'"><img title="Login with the Australian Access Federation" src="https://rapid.aaf.edu.au/aaf_service_223x54.png"/></a></center>
      </div>';

    $rapid_login_markup = $rapid_login_markup.'<br><br><h2>Login with a local account</h2><br>';
    $login_markup=str_replace('<form', $rapid_login_markup.'<form', $default_login_markup);

    echo $login_markup;
  }

	/**
	 *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    0.1.0
	 */
	public function rapid_connect_authenticate($user, $username, $password) {
    $jws = $_POST['assertion'];

    if($jws) {

      try {
        $options = get_option('rapid_connect_options');
        $secret = $options['secret'];
        $jwt = JWT::decode($jws, $secret);
      } catch(Exception $e) {
        error_log ($e);
        return new WP_Error('rapid_connect_invalid_jws', __('<strong>Error</strong>: The response from AAF Rapid Connect could not be validated.'));
      }

      $attr = $jwt->{'https://aaf.edu.au/attributes'};

      $trusted_affiliations = $options['trusted_affiliations'];
      if($trusted_affiliations) {
        $affiliations = explode(";", $attr->edupersonscopedaffiliation);

        if (count(array_intersect($trusted_affiliations, $affiliations)) < 1) {
          return new WP_Error('rapid_connect_access_control', __('<strong>Error</strong>: This account is not permitted access via the AAF due to local policy.'));
        }
      }


      $user_login = sha1($attr->edupersontargetedid);
      $user = new WP_User($user_login);

      if ( !$user->ID ) {
        $user = $this->rapid_connect_create_new_user($attr);
      } else {
        // Ensure this user is managed by Rapid Connect
        if ( !get_usermeta($user->ID, 'rapid_connect') ) {
          return new WP_Error('rapid_connect_invalid_login', __('<strong>Error</strong>: This account is not accessed via the AAF.'));
        }

        // Ensure personal information is current with source IdP
        $this->rapid_connect_update_user($user, $attr);
      }
    }
    return $user;
  }

  private function rapid_connect_create_new_user($attr) {
    require_once( ABSPATH . WPINC . '/registration.php' );

    $user_login = sha1($attr->edupersontargetedid);
    $user_id = wp_insert_user(array('user_login'=>$user_login));

    $user = new WP_User($user_id);

    if ( !$user->ID )
      return new WP_Error('rapid_connect_provisioning', __("<strong>Error</strong>: Your account could not be created. Please contact support."));

    update_user_meta($user->ID, 'rapid_connect', true);

    // Populate initial set of personal information
    $this->rapid_connect_update_user($user, $attr);

    return $user;
  }

  private function rapid_connect_update_user($user, $attr) {
    $user_data = array(
      'ID' => $user->ID,
    );
    $user_data['first_name'] = $attr->givenname;
    $user_data['last_name'] = $attr->surname;
    $user_data['display_name'] = $attr->displayname;
    $user_data['nickname'] = $attr->displayname;
    $user_data['user_nicename'] = $attr->displayname;
    $user_data['user_email'] = $attr->mail;

    wp_update_user($user_data);
  }

  private function random_string()
  {
    $character_set_array = array();
    $character_set_array[] = array('count' => 8, 'characters' => 'abcdefghijklmnopqrstuvwxyz');
    $character_set_array[] = array('count' => 8, 'characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $character_set_array[] = array('count' => 8, 'characters' => '0123456789');
    $character_set_array[] = array('count' => 8, 'characters' => '!@#$+-*&?:');
    $temp_array = array();
    foreach ($character_set_array as $character_set) {
        for ($i = 0; $i < $character_set['count']; $i++) {
            $temp_array[] = $character_set['characters'][rand(0, strlen($character_set['characters']) - 1)];
        }
    }
    shuffle($temp_array);
    return implode('', $temp_array);
  }

}
