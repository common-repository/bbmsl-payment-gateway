<?php

/**
 * WebhookController.php
 *
 * MVC controller concept for handling webhook notification requests
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Controllers\WebhookController
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      -
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Controllers;

use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Option;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\Log;
use BBMSL\Sdk\Utility;
use \WC_Order;

class WebhookController{

	/**
	 * Adaptor function for passing log data to the Logger.
	 * @param string $operation
	 * @param string $request_id
	 * @param array $info
	 * @return void
	 */
	private static function simpleLog( string $operation = '', string $request_id = '', array $info = [] ) {
		$base_info = array(
			'type'			=> $operation,
			'request_id'	=> $request_id,
		);
		Log::put( Log::TYPE_WEBHOOK, array(
			'validated'	=> false,
			'operation'	=> array_merge( $base_info, $info, $base_info ),
		) );
	}

	/**
	 * Webhook processor for the notification callback.
	 * @return array
	 */
	final public static function notification()
	{
		// decode request raw json
		$ev_payload = file_get_contents( 'php://input' );
		if( isset( $ev_payload ) && is_string( $ev_payload ) ) {
			$ev_payload = trim( $ev_payload );
			if( strlen( $ev_payload ) > 0 && Utility::isJson( $ev_payload ) ) {
				$payload = json_decode( $ev_payload, true );
			}
		}

		// assign a request id for log tracing
		$logging_request_id = sprintf( 'request_%s', bin2hex( random_bytes( 32 ) ) );
		
		// create log object
		$logging_request_method = '';
		if( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ) {
			$request_method = trim( $_SERVER['REQUEST_METHOD'] );
			if( strlen( $request_method ) > 0 ) {
				$logging_request_method = esc_attr( $request_method );
			}
		}
		Log::put( Log::TYPE_WEBHOOK, array(
			'request' => array(
				'id'		=> $logging_request_id,
				'method'	=> $logging_request_method,
				'payload'	=> array(
					'raw'	=> ( isset( $ev_payload ) ? $ev_payload : false ),
					'json'	=> ( isset( $payload ) ? $payload : false ),
				),
			),
		) );
		self::simpleLog( 'init_receive', $logging_request_id, array( 'raw' => $_REQUEST ) );

		// process the request
		if( isset( $payload ) && is_array( $payload ) && sizeof( $payload ) > 0 ) {

			// if no signature, throw missing
			if( !isset( $payload['signature'] ) || empty( $payload['signature'] ) ) {
				return array( esc_attr__( 'Missing signature.', 'bbmsl-gateway' ), 403 );
			}
			
			// create expected query string
			$signature = trim( $payload['signature'] );
			self::simpleLog( 'signature_read', $logging_request_id );
			$data = array_diff_key( $payload, array( 'signature' => null ) );
			$query_str = array();
			foreach( $data as $k => $row ) {
				$query_str[] = sprintf( '%s=%s', $k, $row );
			}
			sort($query_str);
			$query_str = implode( '&', $query_str );
			
			// create api instance
			$gateway = BBMSL::newApiCallInstance();
			self::simpleLog( 'api_instance', $logging_request_id );
			if( !$gateway ){
				return array( esc_attr__( 'Gateway Error', 'bbmsl-gateway' ), 500 );
			}
			
			$modes = BBMSL_SDK::getAcceptedModes();
			$verified_mode = null;
			foreach($modes as $mode => $config) {
				$public_key_content = $config['public_key'];
				$public_key_pem = sprintf("-----BEGIN PUBLIC KEY-----\n%s-----END PUBLIC KEY-----\n", chunk_split( $public_key_content, 64, "\n" ));
				if( openssl_verify( $query_str, base64_decode( $signature ), $public_key_pem, \OPENSSL_ALGO_SHA256 ) ) {
					self::simpleLog( 'signature_verified', $logging_request_id, array( 'mode' => $mode ) );
					$verified_mode = $mode;
					break;
				}
				$openssl_error = openssl_error_string();
				self::simpleLog( 'signature_failed', $logging_request_id, array( 'mode' => $mode, 'openssl' => $openssl_error, 'query_str' => $query_str, 'key' => $public_key_pem ) );
			}
			
			// reject if signature is invalid
			if( is_null( $verified_mode ) ) {
				return array( sprintf( esc_attr__( 'Invalid signature, validated with %s public key.', 'bbmsl-gateway' )."\n".$openssl_error, '' ), 403 );
			}
			
			// check merchant reference
			$order_reference = trim( $payload['merchantReference'] );
			self::simpleLog('order_reference', $logging_request_id, array( 'order_refeerence' => $order_reference ) );
			if( empty( $order_reference ) ) {
				/* translators: %d is the order ID concerned */
				return array( sprintf( esc_attr__( 'Order (#%s) not found.', 'bbmsl-gateway' ), $order_reference ), 404 );
			}

			// get order by merchant reference
			$order = BBMSL::getOrderByMerchantReference( $order_reference );
			if( !( $order instanceof WC_Order ) ) {
				/* translators: %d is the order ID concerned */
				return array( sprintf( esc_attr__( 'Failed to read order %s.', 'bbmsl-gateway' ), $order_reference ), 404 );
			}
			self::simpleLog('load_order', $logging_request_id, array( 'order' => $order ) );

			// update order status
			self::simpleLog( 'update_order_webhook', $logging_request_id, array( 'order' => $order ) );
			if( method_exists( $order, 'get_id' ) ) {
				$order_id = $order->get_id();
			}
			
			// ON PAID
			BBMSL::updateOrderStatus( $order, Option::get( Constants::PARAM_ORDER_STATUS_ON_PAID ) );
			if( method_exists( $order, 'payment_complete' ) ) {
				$order->payment_complete( $order_reference );
				$order->save();
			}
			
			update_post_meta( $order_id, Constants::META_LAST_WEBHOOK, json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
			self::simpleLog( 'update_order_webhook_success', $logging_request_id );
			return array( 'OK', 200 ); // must be in CAPS!!!
		}
		self::simpleLog('hello-ping', $logging_request_id, array() );
		return array( esc_attr__( 'Hello', 'bbmsl-gateway' ), 404 );
	}
}
