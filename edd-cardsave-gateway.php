<?php
/**
 * Plugin Name:     Easy Digital Downloads - Cardsave Gateway
 * Plugin URI:      https://easydigitaldownloads.com/extensions/cardsave-gateway
 * Description:     Adds a payment gateway for Cardsave to Easy Digital Downloads
 * Version:         1.0.0
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-cardsave-gateway
 *
 * @package         EDD\Gateway\Cardsave
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


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
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      self::$instance The one true EDD_Cardsave_Gateway
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Cardsave_Gateway();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
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
            define( 'EDD_CARDSAVE_GATEWAY_VERSION', '1.0.0' );

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
			// Include helper functions
			require_once( EDD_CARDSAVE_GATEWAY_DIR . '/includes/functions.php' );
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Edit plugin metalinks
			add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

			// Handle licensing
			if( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'Cardsave Gateway', EDD_CARDSAVE_GATEWAY_VERSION, 'Daniel J Griffiths' );
			}

			// Register settings
			add_filter( 'edd_settings_gateways', array( $this, 'settings' ), 1 );

			// Add the gateway
			add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ) );

			// Process payment
			add_action( 'edd_gateway_cardsave', array( $this, 'process_payment' ) );

			// Display errors
			add_action( 'edd_after_cc_fields', array( $this, 'errors_div' ), 999 );
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
            $locale     = apply_filters( 'plugin_locale', get_locale(), '' );
            $mofile     = sprintf( '%1$s-%2$s.mo', 'edd-cardsave-gateway', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-cardsave-gateway/' . $mofile;

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


        /**
         * Modify plugin metalinks
         *
         * @access      public
         * @since       1.0.0
         * @param       array $links The current links array
         * @param       string $file A specific plugin table entry
         * @return      array $links The modified links array
         */
        public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array(
                    '<a href="http://support.ghost1227.com/forums/forum/plugin-support/edd-cardsave-gateway/" target="_blank">' . __( 'Support Forum', 'edd-cardsave-gateway' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://support.ghost1227.com/section/edd-cardsave-gateway/" target="_blank">' . __( 'Docs', 'edd-cardsave-gateway' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
		}


		/**
		 * Register settings
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $settings The existing plugin settings
		 * @param		array The modified plugin settings array
		 */
		public function settings( $settings ) {
			$new_settings = array(
				array(
					'id'	=> 'edd_cardsave_gateway_settings',
					'name'	=> '<strong>' . __( 'Cardsave Gateway Settings', 'edd-cardsave-gateway' ) . '</strong>',
					'desc'	=> __( 'Configure your Cardsave Gateway settings', 'edd-cardsave-gateway' ),
					'type'	=> 'header'

				),
				array(
					'id'	=> 'edd_cardsave_gateway_merchant_id',
					'name'	=> __( 'Merchant ID', 'edd-cardsave-gateway' ),
					'desc'	=> __( 'Enter your Cardsave Gateway Merchant ID (found under <a href="https://mms.cardsaveonlinepayments.com/Default.aspx" target="_blank">Gateway Account Admin</a>)', 'edd-cardsave-gateway' ),
					'type'	=> 'text'
				),
				array(
					'id'	=> 'edd_cardsave_gateway_password',
					'name'	=> __( 'Password', 'edd-cardsave-gateway' ),
					'desc'	=> __( 'Enter your Cardsave Gateway Password (found under <a href="https://mms.cardsaveonlinepayments.com/Default.aspx" target="_blank">Gateway Account Admin</a>)', 'edd-cardsave-gateway' ),
					'type'	=> 'text'
				)
			);

			return array_merge( $settings, $new_settings );
		}


		/**
		 * Register our new gateway
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $gateways The current gateway list
		 * @return		array $gateways The updated gateway list
		 */
		public function register_gateway( $gateways ) {
			$gateways['cardsave'] = array(
				'admin_label'		=> 'Cardsave',
				'checkout_label'	=> __( 'Credit Card', 'edd-cardsave-gateway' )
			);

			return $gateways;
		}


		/**
		 * Process payment submission
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $purchase_data The data for a specific purchase
		 * @return		void
		 */
		public function process_payment( $purchase_data ) {
			$errors = edd_get_errors();

			if( !$errors ) {

				try{

					$headers	= array(
						'SOAPAction:https://www.thepaymentgateway.net/CardDetailsTransaction',
						'Content-Type: text/xml; charset = utf-8',
						'Connection: close'
					);

					$amount = edd_sanitize_amount( number_format( $purchase_data['price'] * 100, 0 ) );

					$xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<CardDetailsTransaction xmlns="https://www.thepaymentgateway.net/">
<PaymentMessage>
<MerchantAuthentication MerchantID="' . edd_get_option( 'edd_cardsave_gateway_merchant_id', '' ) . '" Password="' . edd_get_option( 'edd_cardsave_gateway_password', '' ) . '" />
<TransactionDetails Amount="' . $amount . '" CurrencyCode="' . edd_cardsave_gateway_convert_currency( edd_get_currency() ) . '">
<MessageDetails TransactionType="SALE" />
<OrderID>' . $purchase_data['purchase_key'] . '</OrderID>
<OrderDescription>' . edd_cardsave_gateway_clean( edd_cardsave_gateway_build_summary( $purchase_data ), 50 ) . '</OrderDescription>
<TransactionControl>
<EchoCardType>TRUE</EchoCardType>
<EchoAVSCheckResult>TRUE</EchoAVSCheckResult>
<EchoCV2CheckResult>TRUE</EchoCV2CheckResult>
<EchoAmountReceived>TRUE</EchoAmountReceived>
<DuplicateDelay>20</DuplicateDelay>
<CustomVariables>
<GenericVariable Name="MyInputVariable" Value="Ping" />
</CustomVariables>
</TransactionControl>
</TransactionDetails>
<CardDetails>
<CardName>' . edd_cardsave_gateway_clean( $purchase_data['card_info']['card_name'], 100 ) . '</CardName>
<CardNumber>' . $purchase_data['card_info']['card_number'] . '</CardNumber>
<StartDate Month="" Year="" />
<ExpiryDate Month="' . $purchase_data['card_info']['card_exp_month'] . '" Year="' . date( 'y', $purchase_data['card_info']['card_exp_year'] ) . '" />
<CV2>' . $purchase_data['card_info']['card_cvc'] . '</CV2>
<IssueNumber></IssueNumber>
</CardDetails>
<CustomerDetails>
<BillingAddress>
<Address1>' . edd_cardsave_gateway_clean( $purchase_data['card_info']['card_address'], 100 ) . '</Address1>
<Address2>' . edd_cardsave_gateway_clean( $purchase_data['card_info']['card_address_2'], 50 ) . '</Address2>
<Address3></Address3>
<City>' . edd_cardsave_gateway_clean( $purchase_data['card_info']['card_city'], 50 ) . '</City>
<State>' . edd_cardsave_gateway_clean( $purchase_data['card_info']['card_state'], 50 ) . '</State>
<PostCode>' . edd_cardsave_gateway_clean( $purchase_data['card_info']['card_zip'], 50 ) . '</PostCode>
<CountryCode></CountryCode>
</BillingAddress>
<EmailAddress>' . edd_cardsave_gateway_clean( $purchase_data['user_email'], 100 ) . '</EmailAddress>
<PhoneNumber></PhoneNumber>
<CustomerIPAddress>' . edd_get_ip() . '</CustomerIPAddress>
</CustomerDetails>
</PaymentMessage>
</CardDetailsTransaction>
</soap:Body>
</soap:Envelope>';

					$gateway	= 1;
					$domain		= 'cardsaveonlinepayments.com';
					$port		= '4430';
					$attempt	= 1;
					$success	= false;

					while( !$success && $gateway <= 3 && $attempt <= 3 ) {

						$url = 'https://gw' . $gateway . '.' . $domain . ':' . $port . '/';

						// Initialize curl
						$curl = curl_init();

						curl_setopt( $curl, CURLOPT_HEADER, false );
						curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
						curl_setopt( $curl, CURLOPT_POST, true );
						curl_setopt( $curl, CURLOPT_URL, $url );
						curl_setopt( $curl, CURLOPT_POSTFIELDS, $xml );
						curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
						curl_setopt( $curl, CURLOPT_ENCODING, 'UTF-8' );
						curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );

						$ret		= curl_exec( $curl );
						$err		= curl_errno( $curl );
						$rethead	= curl_getinfo( $curl );

						curl_close( $curl );
						$curl = null;

						if( $err == 0 ) {
							$code = edd_cardsave_gateway_get_xml_value( 'StatusCode', $ret, '[0-9]+' );
							$success = false;

							if( is_numeric( $code ) ) {

								if( $code != 30 ) {
									$apimessage		= edd_cardsave_gateway_get_xml_value( 'Message', $ret, '.+' );
									$apiauthcode	= edd_cardsave_gateway_get_xml_value( 'AuthCode', $ret, '.+' );
									$apicrossref	= edd_cardsave_gateway_get_crossreference( $ret );
									$apiaddrnumeric	= edd_cardsave_gateway_get_xml_value( 'AddressNumericCheckResult', $ret, '.+' );
									$apipostcode	= edd_cardsave_gateway_get_xml_value( 'PostCodeCheckResult', $ret, '.+' );
									$apicv2			= edd_cardsave_gateway_get_xml_value( 'CV2CheckResult', $ret, '.+' );
									$api3dsauth		= edd_cardsave_gateway_get_xml_value( 'ThreeDSecureAuthenticationCheckResult', $ret, '.+' );

									if( $code == 0 ) {
										$payment_data = array(
											'price'			=> $purchase_data['price'],
											'date'			=> $purchase_data['date'],
											'user_email'	=> $purchase_data['user_email'],
											'purchase_key'	=> $purchase_data['purchase_key'],
											'currency'		=> edd_get_currency(),
											'downloads'		=> $purchase_data['downloads'],
											'cart_details'	=> $purchase_data['cart_details'],
											'user_info'		=> $purchase_data['user_info'],
											'status'		=> 'pending'
										);

										$payment = edd_insert_payment( $payment_data );

										if( $payment ) {
											$success = true;

											edd_insert_payment_note( $payment, sprintf( __( 'Cardsave Gateway Transaction ID: %s', 'edd-cardsave-gateway' ), $apiauthcode ) );
											edd_update_payment_status( $payment, 'publish' );
											edd_send_to_success_page();
										} else {
											edd_set_error( 'authorize_error', __( 'Error: Your payment could not be recorded. Please try again.', 'edd-cardsave-gateway' ) );
											edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
										}
									} elseif( $code == 3 ) {
										$response = __( 'Unable to process your payment at this time', 'edd-cardsave-gateway' );
									} elseif( $code == 4 ) {
										$response = __( 'Card referred', 'edd-cardsave-gateway' );
									} elseif( $code == 5 ) {
										$response = __( 'Payment failed: ', 'edd-cardsave-gateway' );

										if( $apiaddrnumeric == 'FAILED' ) {
											$response .= __( 'Billing address check failed.', 'edd-cardsave-gateway' ) . ' ';
										}

										if( $apipostcode == 'FAILED' ) {
											$response .= __( 'Billing zip code check failed.', 'edd-cardsave-gateway' ) . ' ';
										}

										if( $apicv2 == 'FAILED' ) {
											$response .= __( 'The CVC code you entered is incorrect.', 'edd-cardsave-gateway' ) . ' ';
										}

										if( $api3dsauth == 'FAILED' ) {
											$response .= __( 'Your bank declined the transaction.', 'edd-cardsave-gateway' ) . ' ';
										}

										if( $apimessage == 'Card declined' || $apimessage == 'Card referred' ) {
											$response .= __( 'Your bank declined the transaction.', 'edd-cardsave-gateway' ) . ' ';
										}
									} elseif( $code == 20 ) {
										$soapPreviousTransactionResult = null;
										$PreviousTransactionResult = null;

										if( preg_match( '#<PreviousTransactionResult>(.+)</PreviousTransactionResult>#iU', $ret, $soapPreviousTransactionResult ) ) {
											$PreviousTransactionResult = $soapPreviousTransactionResult[1];

											$PreviousMessage = edd_cardsave_gateway_get_xml_value( 'Message', $PreviousTransactionResult, '.+' );
											$PreviousStatusCode = edd_cardsave_gateway_get_xml_value( 'StatusCode', $PreviousTransactionResult, '.+' );
										}

										if( $PreviousStatusCode == 0 ) {
											$apimessage = $PreviousMessage;

											$payment_data = array(
												'price'			=> $purchase_data['price'],
												'date'			=> $purchase_data['date'],
												'user_email'	=> $purchase_data['user_email'],
												'purchase_key'	=> $purchase_data['purchase_key'],
												'currency'		=> edd_get_currency(),
												'downloads'		=> $purchase_data['downloads'],
												'cart_details'	=> $purchase_data['cart_details'],
												'user_info'		=> $purchase_data['user_info'],
												'status'		=> 'pending'
											);

											$payment = edd_insert_payment( $payment_data );

											if( $payment ) {
												$success = true;

												edd_insert_payment_note( $payment, sprintf( __( 'Cardsave Gateway Transaction ID: %s', 'edd-cardsave-gateway' ), $result->id ) );
												edd_update_payment_status( $payment, 'publish' );
												edd_send_to_success_page();
											} else {
												edd_set_error( 'authorize_error', __( 'Error: Your payment could not be recorded. Please try again.', 'edd-cardsave-gateway' ) );
												edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
											}
										} else {
											$message = $PreviousMessage;

											$response = __( 'Your payment was not successful', 'edd-cardsave-gateway' );
										}

										if( !$success ) {
											edd_record_gateway_error( __( 'Cardsave Gateway Error', 'edd-cardsave-gateway' ), print_r( $response, true ), 0 );
											edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-cardsave-gateway' ) );
											edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
										}
									} else {
										edd_record_gateway_error( __( 'Cardsave Gateway Error', 'edd-cardsave-gateway' ), print_r( $apimessage, true ), 0 );
										edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-cardsave-gateway' ) );
										edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
									}
								}
							}
						}

						// Increment the transaction attempt
						if( $attempt <= 2 ) {
							$attempt++;
						} else {
							$attempt = 1;
							$gateway++;
						}

						if( !$success ) {
							edd_record_gateway_error( __( 'Cardsave Gateway Error', 'edd-cardsave-gateway' ), $response, 0 );
							edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-cardsave-gateway' ) );
							edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
						}
					}
				} catch( Exception $e ) {
					edd_record_gateway_error( __( 'Cardsave Gateway Error', 'edd-cardsave-gateway' ), print_r( $e, true ), 0 );
					edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-cardsave-gateway' ) );
					edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
				}
			} else {
				edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
			}
		}


		/**
		 * Output form errors
		 *
		 * @since		1.0.0
		 * @access		public
		 * @return		void
		 */
		public function errors_div() {
			echo '<div id="edd-cardsave-errors"></div>';
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
	if( !class_exists( 'Easy_Digital_Downloads' ) ) {
		deactivate_plugins( __FILE__ );
		unset( $_GET['activate'] );

		// Display notice
		add_action( 'admin_notices', 'EDD_Cardsave_Gateway_missing_edd_notice' );
	} else {
	    return EDD_Cardsave_Gateway::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Cardsave_Gateway_load' );

/**
 * We need Easy Digital Downloads... if it isn't present, notify the user!
 *
 * @since		1.0.0
 * @return		void
 */
function EDD_Cardsave_Gateway_missing_edd_notice() {
	$active_plugins = get_option( 'active_plugins' );
	print_r($active_plugins);
	echo '<div class="error"><p>' . __( 'Cardsave Gateway requires Easy Digital Downloads! Please install it to continue!', 'edd-cardsave-gateway' ) . '</p></div>';
}
