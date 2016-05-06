<?php
/**
 * Helper functions
 *
 * @package         EDD\Gateway\Cardsave\Functions
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get XML value
 *
 * @since       1.0.0
 * @param       object $XMLElement
 * @param       array $XML
 * @param       string $pattern
 * @return      mixed
 */
function edd_cardsave_gateway_get_xml_value( $XMLElement, $XML, $pattern ) {
	$soapArray = null;

	if( preg_match( '#<' . $XMLElement . '>(' . $pattern . ')</' . $XMLElement . '>#iU', $XML, $soapArray ) ) {
		$return = $soapArray[1];
	} else {
		$return = $XMLElement . ' ' . __( 'not found', 'edd-cardsave-gateway' );
	}

	return $return;
}


/**
 * Get crossreference
 *
 * @since       1.0.0
 * @param       array $XML
 * @return      mixed
 */
function edd_cardsave_gateway_get_crossreference( $XML ) {
	$soapArray = null;

	if( preg_match( '#<TransactionOutputData CrossReference="(.+)">#iU', $XML, $soapArray ) ) {
		$return = $soapArray[1];
	} else {
		$return = __( 'No data found', 'edd-cardsave-gateway' );
	}

	return $return;
}


/**
 * Strip invalid characters
 *
 * @since       1.0.0
 * @param       string $string
 * @return      string $string
 */
function edd_cardsave_gateway_strip_invalid( $string ) {
	$replace      = array( '<', '&' );
	$replace_with = array( '', '&amp;' );
	$string       = str_replace( $replace, $replace_with, $string );

	return $string;
}


/**
 * Remove/encode restricted values
 *
 * @since       1.0.0
 * @param       string $string
 * @param       int $limit
 * @return      string $string
 */
function edd_cardsave_gateway_clean( $string, $limit ) {
	$replace = array( '#', '\\', '>', '<', '\"', '[', ']' );
	$string  = str_replace( $replace, '', $string );
	$string  = htmlspecialchars( $string );
	$string  = substr( $string, 0, $limit );

	return $string;
}


/**
 * Translate gateway error codes
 *
 * @since       1.0.0
 * @param       array $detail
 * @return      mixed $message
 */
function edd_cardsave_gateway_error( $detail ) {
	if( $detail == 'Invalid card type' ) {
		$message = __( 'Invalid card type/number', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Passed variable (PaymentMessage.CardDetails.CV2) has an invalid value' ) {
		$message = __( 'Invalid CV2 number', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Required variable (PaymentMessage.CardDetails.CardNumber) is missing' ) {
		$message = __( 'No card number entered', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Passed variable (PaymentMessage.CardDetails.CardNumber) has an invalid value' ) {
		$message = __( 'Invalid card number', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Required variable (PaymentMessage.CardDetails.ExpiryDate.Month) is missing' ) {
		$message = __( 'Expiry month missing', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Required variable (PaymentMessage.CardDetails.ExpiryDate.Year) is missing' ) {
		$message = __( 'Expiry year missing', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Passed variable (PaymentMessage.CardDetails.ExpiryDate.Year) has an invalid value' ) {
		$message = __( 'Invalid expiry year', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Passed variable (PaymentMessage.CardDetails.ExpiryDate.Month) has an invalid value' ) {
		$message = __( 'Invalid expiry month', 'edd-cardsave-gateway' );
	} elseif( $detail == 'Passed variable (PaymentMessage.CardDetails.IssueNumber) has an invalid value' ) {
		$message = __( 'Invalid issue number', 'edd-cardsave-gateway' );
	} else {
		$message = $detail;
	}

	return $message;
}


/**
 * Convert currency to code
 *
 * @since       1.0.0
 * @param       string $currency
 * @return      int $code
 */
function edd_cardsave_gateway_convert_currency( $currency ) {
	if( $currency = 'USD' ) {
		$code = 840;
	} elseif( $currency = 'EUR' ) {
		$code = 978;
	} elseif( $currency = 'GBP' ) {
		$code = 826;
	} elseif( $currency = 'AUD' ) {
		$code = 36;
	} elseif( $currency = 'BRL' ) {
		$code = 986;
	} elseif( $currency = 'CAD' ) {
		$code = 124;
	} elseif( $currency = 'CZK' ) {
		$code = 203;
	} elseif( $currency = 'DKK' ) {
		$code = 208;
	} elseif( $currency = 'HKD' ) {
		$code = 344;
	} elseif( $currency = 'HUF' ) {
		$code = 348;
	} elseif( $currency = 'ILS' ) {
		$code = 376;
	} elseif( $currency = 'JPY' ) {
		$code = 392;
	} elseif( $currency = 'MYR' ) {
		$code = 458;
	} elseif( $currency = 'MXN' ) {
		$code = 484;
	} elseif( $currency = 'NZD' ) {
		$code = 554;
	} elseif( $currency = 'NOK' ) {
		$code = 578;
	} elseif( $currency = 'PHP' ) {
		$code = 608;
	} elseif( $currency = 'PLN' ) {
		$code = 985;
	} elseif( $currency = 'SGD' ) {
		$code = 702;
	} elseif( $currency = 'SEK' ) {
		$code = 752;
	} elseif( $currency = 'CHF' ) {
		$code = 756;
	} elseif( $currency = 'TWD' ) {
		$code = 999;
	} elseif( $currency = 'THB' ) {
		$code = 999;
	} elseif( $currency = 'INR' ) {
		$code = 356;
	} elseif( $currency = 'TRY' ) {
		$code = 999;
	} elseif( $currency = 'RIAL' ) {
		$code = 364;
	} elseif( $currency = 'RUB' ) {
		$code = 643;
	} else {
		$code = 999;
	}

	return $code;
}


/**
 * Create purchase summary
 *
 * @since       1.0.0
 * @param       array $purchase_data
 * @return      string $summary
 */
function edd_cardsave_gateway_build_summary( $purchase_data ) {
	$cart_items = isset( $purchase_data['cart_details'] ) ? maybe_unserialize( $purchase_data['cart_details'] ) : false;
	$summary    = '';

	if( $cart_items ) {
		foreach( $cart_items as $key => $cart_item ) {
			$id       = $cart_item['id'];
			$summary .= get_the_title( $id ) . ', ';
		}
	}

	return $summary;
}
