<?php
/**
 * Plugin Name:     Easy Digital Downloads - Cardsave Gateway
 * Plugin URI:      https://wordpress.org/plugins/edd-cardsave-gateway
 * Description:     Adds a payment gateway for Cardsave to Easy Digital Downloads
 * Version:         1.0.3
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-cardsave-gateway
 *
 * @package         EDD\Gateway\Cardsave
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( !class_exists( 'EDD_Cardsave_Gateway' ) ) {


	/**
	 * Main EDD_Cardsave_Gateway class
	 *
	 * @since       1.0.0
	 */
	class EDD_Cardsave_Gateway {


		/**
		 * @var         EDD_Cardsave_Gateway $instance The one true EDD_Cardsave_Gateway
		 * @since       1.0.0
		 */
		private static $instance;


		/**
		 * @var         bool $debugging Whether or not debugging is available
		 * @since       1.0.3
		 */
		public $debugging = false;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      self::$instance The one true EDD_Cardsave_Gateway
		 */
		public static function instance() {
			if( ! self::$instance ) {
				self::$instance = new EDD_Cardsave_Gateway();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();

				if( class_exists( 'S214_Debug' ) ) {
					if( edd_get_option( 'edd_getresponse_enable_debug', false ) ) {
						self::$instance->debugging = true;
					}
				}
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function setup_constants() {
			// Plugin version
			define( 'EDD_CARDSAVE_GATEWAY_VERSION', '1.0.3' );

			// Plugin path
			define( 'EDD_CARDSAVE_GATEWAY_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_CARDSAVE_GATEWAY_URL', plugin_dir_url( __FILE__ ) );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {
			require_once EDD_CARDSAVE_GATEWAY_DIR . 'includes/functions.php';
			require_once EDD_CARDSAVE_GATEWAY_DIR . 'includes/gateway.php';
		}


		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'EDD_Cardsave_Gateway_language_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-cardsave-gateway', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-cardsave-gateway/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-cardsave-gateway/ folder
				load_textdomain( 'edd-cardsave-gateway', $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-cardsave-gateway/languages/ folder
				load_textdomain( 'edd-cardsave-gateway', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-cardsave-gateway', false, $lang_dir );
			}
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Cardsave_Gateway
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Cardsave_Gateway The one true EDD_Cardsave_Gateway
 */
function EDD_Cardsave_Gateway_load() {
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'S214_EDD_Activation' ) ) {
			require_once 'includes/libraries/class.s214-edd-activation.php';
		}

		$activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();
	} else {
		return EDD_Cardsave_Gateway::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Cardsave_Gateway_load' );
