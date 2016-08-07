<?php
/**
 * Gateway Functions
 *
 * @package         EDD\Gateway\Cardsave\Gateway
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       1.0.2
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_cardsave_gateway_add_settings_section( $sections ) {
	$sections['cardsave'] = __( 'Cardsave', 'edd-cardsave-gateway' );

	return $sections;
}
add_filter( 'edd_settings_sections_gateways', 'edd_cardsave_gateway_add_settings_section' );


/**
 * Register settings
 *
 * @since       1.0.0
 * @param       array $settings The existing plugin settings
 * @param       array The modified plugin settings array
 */
function edd_cardsave_gateway_register_settings( $settings ) {
	$new_settings = array(
		'cardsave' => apply_filters( 'edd_cardsave_gateway_settings', array(
			array(
				'id'   => 'edd_cardsave_gateway_settings',
				'name' => '<strong>' . __( 'Cardsave Settings', 'edd-cardsave-gateway' ) . '</strong>',
				'desc' => __( 'Configure your Cardsave Gateway settings', 'edd-cardsave-gateway' ),
				'type' => 'header'
			),
			array(
				'id'   => 'edd_cardsave_gateway_merchant_id',
				'name' => __( 'Merchant ID', 'edd-cardsave-gateway' ),
				'desc' => __( 'Enter your Cardsave Gateway Merchant ID (found under <a href="https://mms.cardsaveonlinepayments.com/Default.aspx" target="_blank">Gateway Account Admin</a>)', 'edd-cardsave-gateway' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_cardsave_gateway_password',
				'name' => __( 'Password', 'edd-cardsave-gateway' ),
				'desc' => __( 'Enter your Cardsave Gateway Password (found under <a href="https://mms.cardsaveonlinepayments.com/Default.aspx" target="_blank">Gateway Account Admin</a>)', 'edd-cardsave-gateway' ),
				'type' => 'text'
			)
		) )
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_gateways', 'edd_cardsave_gateway_register_settings', 1 );


/**
 * Add debug option if the S214 Debug plugin is enabled
 *
 * @since       1.0.3
 * @param       array $settings The current settings
 * @return      array $settings The updated settings
 */
function edd_cardsave_gateway_add_debug( $settings ) {
	if( class_exists( 'S214_Debug' ) ) {
		$debug_setting[] = array(
			'id'   => 'edd_cardsave_gateway_debugging',
			'name' => '<strong>' . __( 'Debugging', 'edd-cardsave-gateway' ) . '</strong>',
			'desc' => '',
			'type' => 'header'
		);

		$debug_setting[] = array(
			'id'   => 'edd_cardsave_gateway_enable_debug',
			'name' => __( 'Enable Debug', 'edd-cardsave-gateway' ),
			'desc' => sprintf( __( 'Log plugin errors. You can view errors %s.', 'edd-cardsave-gateway' ), '<a href="' . admin_url( 'tools.php?page=s214-debug-logs' ) . '">' . __( 'here', 'edd-cardsave-gateway' ) . '</a>' ),
			'type' => 'checkbox'
		);

		$settings = array_merge( $settings, $debug_setting );
	}

	return $settings;
}
add_filter( 'edd_cardsave_gateway_settings', 'edd_cardsave_gateway_add_debug' );


/**
 * Register our new gateway
 *
 * @since       1.0.0
 * @param       array $gateways The current gateway list
 * @return      array $gateways The updated gateway list
 */
function edd_cardsave_gateway_register_gateway( $gateways ) {
	$gateways['cardsave'] = array(
		'admin_label'    => 'Cardsave',
		'checkout_label' => __( 'Credit Card', 'edd-cardsave-gateway' )
	);

	return $gateways;
}
add_filter( 'edd_payment_gateways', 'edd_cardsave_gateway_register_gateway' );


/**
 * Process payment submission
 *
 * @since       1.0.0
 * @param       array $purchase_data The data for a specific purchase
 * @return      void
 */
function edd_cardsave_gateway_process_payment( $purchase_data ) {
	$errors = edd_get_errors();

	if( ! $errors ) {
		try{
			$headers = array(
				'SOAPAction:https://www.thepaymentgateway.net/CardDetailsTransaction',
				'Content-Type: text/xml; charset = utf-8',
				'Connection: close'
			);

			$amount = number_format( $purchase_data['price'] * 100, 0 );
			$err    = false;

			if( ! $purchase_data['card_info']['card_name'] ) {
				edd_set_error( 'authorize_error', __( 'Error: Card name is required. Please try again.', 'edd-cardsave-gateway' ) );
				$err = true;
			}

			if( ! $purchase_data['card_info']['card_number'] ) {
				edd_set_error( 'authorize_error', __( 'Error: Card number is required. Please try again.', 'edd-cardsave-gateway' ) );
				$err = true;
			}

			if( ! $purchase_data['card_info']['card_exp_month'] || !$purchase_data['card_info']['card_exp_year'] ) {
				edd_set_error( 'authorize_error', __( 'Error: Card expiration is required. Please try again.', 'edd-cardsave-gateway' ) );
				$err = true;
			}

			if( ! $purchase_data['card_info']['card_cvc'] ) {
				edd_set_error( 'authorize_error', __( 'Error: Card CVC is required. Please try again.', 'edd-cardsave-gateway' ) );
				$err = true;
			}

			if( $err ) {
				edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
			}

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

			$gateway = 1;
			$domain  = 'cardsaveonlinepayments.com';
			$port    = '4430';
			$attempt = 1;
			$success = false;

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

				$ret     = curl_exec( $curl );
				$err     = curl_errno( $curl );
				$rethead = curl_getinfo( $curl );

				curl_close( $curl );
				$curl = null;

				if( $err == 0 ) {
					$code    = edd_cardsave_gateway_get_xml_value( 'StatusCode', $ret, '[0-9]+' );
					$success = false;

					if( is_numeric( $code ) ) {
						if( $code != 30 ) {
							$apimessage     = edd_cardsave_gateway_get_xml_value( 'Message', $ret, '.+' );
							$apiauthcode    = edd_cardsave_gateway_get_xml_value( 'AuthCode', $ret, '.+' );
							$apicrossref    = edd_cardsave_gateway_get_crossreference( $ret );
							$apiaddrnumeric = edd_cardsave_gateway_get_xml_value( 'AddressNumericCheckResult', $ret, '.+' );
							$apipostcode    = edd_cardsave_gateway_get_xml_value( 'PostCodeCheckResult', $ret, '.+' );
							$apicv2         = edd_cardsave_gateway_get_xml_value( 'CV2CheckResult', $ret, '.+' );
							$api3dsauth     = edd_cardsave_gateway_get_xml_value( 'ThreeDSecureAuthenticationCheckResult', $ret, '.+' );

							if( $code == 0 ) {
								$payment_data = array(
									'price'        => $purchase_data['price'],
									'date'         => $purchase_data['date'],
									'user_email'   => $purchase_data['user_email'],
									'purchase_key' => $purchase_data['purchase_key'],
									'currency'     => edd_get_currency(),
									'downloads'    => $purchase_data['downloads'],
									'cart_details' => $purchase_data['cart_details'],
									'user_info'    => $purchase_data['user_info'],
									'status'       => 'pending'
								);

								$payment = edd_insert_payment( $payment_data );

								if( $payment ) {
									$success = true;

									edd_insert_payment_note( $payment, sprintf( __( 'Cardsave Gateway Transaction ID: %s', 'edd-cardsave-gateway' ), $apiauthcode ) );
									edd_update_payment_status( $payment, 'publish' );
									edd_send_to_success_page();
								} else {
									$response .= __( 'Payment could not be recorded.', 'edd-cardsave-gateway' );

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
								$PreviousTransactionResult     = null;

								if( preg_match( '#<PreviousTransactionResult>(.+)</PreviousTransactionResult>#iU', $ret, $soapPreviousTransactionResult ) ) {
									$PreviousTransactionResult = $soapPreviousTransactionResult[1];
									$PreviousMessage           = edd_cardsave_gateway_get_xml_value( 'Message', $PreviousTransactionResult, '.+' );
									$PreviousStatusCode        = edd_cardsave_gateway_get_xml_value( 'StatusCode', $PreviousTransactionResult, '.+' );
								}

								if( $PreviousStatusCode == 0 ) {
									$apimessage = $PreviousMessage;

									$payment_data = array(
										'price'        => $purchase_data['price'],
										'date'         => $purchase_data['date'],
										'user_email'   => $purchase_data['user_email'],
										'purchase_key' => $purchase_data['purchase_key'],
										'currency'     => edd_get_currency(),
										'downloads'    => $purchase_data['downloads'],
										'cart_details' => $purchase_data['cart_details'],
										'user_info'    => $purchase_data['user_info'],
										'status'       => 'pending'
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

				if( ! $success ) {
					edd_record_gateway_error( __( 'Cardsave Gateway Error', 'edd-cardsave-gateway' ), $response, 0 );

					if( edd_getresponse()->debugging ) {
						s214_debug_log_error( 'Gateway Error', $response, 'EDD Cardsave Gateway' );
					}

					edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-cardsave-gateway' ) );
					edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
				}
			}
		} catch( Exception $e ) {
			edd_record_gateway_error( __( 'Cardsave Gateway Error', 'edd-cardsave-gateway' ), print_r( $e, true ), 0 );

			if( edd_getresponse()->debugging ) {
				s214_debug_log_error( 'Gateway Error', print_r( $e, true ), 'EDD Cardsave Gateway' );
			}

			edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-cardsave-gateway' ) );
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		}
	} else {
		edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
	}
}
add_action( 'edd_gateway_cardsave', 'edd_cardsave_gateway_process_payment' );


/**
 * Output form errors
 *
 * @since       1.0.0
 * @return      void
 */
function edd_cardsave_gateway_errors_div() {
	echo '<div id="edd-cardsave-errors"></div>';
}
add_action( 'edd_after_cc_fields', 'edd_cardsave_gateway_errors_div', 999 );