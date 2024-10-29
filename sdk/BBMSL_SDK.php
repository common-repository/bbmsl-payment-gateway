<?php

/**
 * BBMSL_SDK.php
 *
 * The main SDK for powering communication between eshop and gateway endpoints.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\BBMSL_SDK
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      -
 * @deprecated -
 * @todo Co-organize order models to bring general support.
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use BBMSL\BBMSL as Core;
use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Option;
use BBMSL\Sdk\Utility;

class BBMSL_SDK
{
	private const API_TESTING_ENDPOINT			= 'https://payapi.sit.bbmsl.com/';
	private const API_PRODUCTION_ENDPOINT		= 'https://payapi.prod.bbmsl.com/';

    public const MODE_LEGACY_SANDBOX			= 'sandbox';
	public const MODE_TESTING					= 'testing';
	public const MODE_PRODUCTION				= 'production';

	public const MODE_DETAIL_KEY_ID				= 'id';
	public const MODE_DETAIL_KEY_LABEL			= 'label';
	public const MODE_DETAIL_KEY_ENDPOINT		= 'endpoint';
	public const MODE_DETAIL_KEY_PORTAL			= 'portal';
	public const MODE_DETAIL_KEY_PUBLIC_KEY		= 'public_key';

	private $_mode			= 'testing';
	private $_merchant_id	= null;

	/**
	 * Gateway supported mode definitions.
	 * @return array<array>
	 */
	final public static function getAcceptedModes()
	{
		return array(
			self::MODE_TESTING => array(
				self::MODE_DETAIL_KEY_ID			=> self::MODE_TESTING, 
				self::MODE_DETAIL_KEY_LABEL			=> __( 'Testing', 'bbmsl-gateway' ),
				self::MODE_DETAIL_KEY_ENDPOINT		=> self::API_TESTING_ENDPOINT,
				self::MODE_DETAIL_KEY_PORTAL		=> Constants::TESTING_PORTAL_LINK,
				self::MODE_DETAIL_KEY_PUBLIC_KEY	=> 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkDecXu4GFMxCqp4pjfwtN1nSQiV9kmdcBMnKq5IeLB6BYWOENqeY+JFftnNaxHOgnhOrbrl71D6G57G7rhNLClgBNerB7mINDBwvENkEVq6zNbJsjOJekJtTVkxs7KoBip44odCBmElCFrUsr0qOr10kzUzYHXXEUpTqQon3jDGm+EkFoNv3RLwn0ZWuwid5kuk6tZ0Xj3OxiKTrzXK2STjzJ8Q25e9CKbO03fpaMSpBRrkuA1NHRQoSO0ew6lGE4swQ+dseVbh+z7YFVUWqDyjJ6pB+F3p4vDniw4r9/rE+ikP0eLMg99vWDjuQbPtUHYaQtMYNSzrmcTkBCGkt6QIDAQAB',
			),
			self::MODE_PRODUCTION => array(
				self::MODE_DETAIL_KEY_ID			=> self::MODE_PRODUCTION, 
				self::MODE_DETAIL_KEY_LABEL			=> __( 'Production', 'bbmsl-gateway' ),
				self::MODE_DETAIL_KEY_ENDPOINT		=> self::API_PRODUCTION_ENDPOINT,
				self::MODE_DETAIL_KEY_PORTAL		=> Constants::PRODUCTION_PORTAL_LINK,
				self::MODE_DETAIL_KEY_PUBLIC_KEY	=> 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAi7pEo8/3ihkA+DHhmjlXyBZd+z6I4av1R9pGsArXtwlY41ynSQi8BAjNJMUJAK0f4QR6DX5VjlMEtoSBrlqBQlbeACZc/tXDMGLjgr2RyhM40ribIBjCVh+0rQl2gSvtZJRY6JX2oVp20Jh4SVOLiL1YdudS1OmRvIAkpPbe6YILagHdF1KZ35vPSVTEhuvquB3qdIO23pMco/GwW9x3S950/XnQ84Lxw5gNWzxunxrEAvSKICgy2I6VFAyi1x/UMem//k75op190TgGEzmr/Gf64IdRzqbenzCfQcWQBo1HwHF/s7nUKdT+Tu6Vv6P5mmNiKU7GYh/N2eVWC+v8eQIDAQAB',
			),
		);
	}

	/**
	 * Get the mode name aligned to class constants.
	 * @return array|bool
	 * @deprecated 1.0.18
	 */
	private function getMode()
	{
		return self::getModeInfo( $this->getModeCode() );
	}
	
	/**
	 * Checks if input is a mode accepted by thie class.
	 * @param string $mode
	 * @return bool
	 */
	final public static function isModeAccepted( string $mode = '' ) {
		return array_key_exists( $mode, self::getAcceptedModes() );
	}

	/**
	 * Return the mode details and configuration based on input mode name.
	 * @param string $target_mode
	 * @return array|bool
	 */
	final public static function getModeInfo( string $target_mode = '' ) {
		if( !self::isModeAccepted( $target_mode ) ) {
			return false;
		}
		return self::getAcceptedModes()[ $target_mode ];
	}
	
	/**
	 * Return specific mode detail based on input mode name and key.
	 * @param string $target_mode
	 * @param string $key id|label|endpoint|portal|public_key
	 * @return mixed
	 */
	final public static function getModeInfoDetail( string $target_mode = '', string $key = '' ){
		$info = self::getModeInfo( $target_mode );
		if( is_array( $info ) ) {
			if( array_key_exists( $key, $info ) ){
				return $info[$key];
			}
		}
		return false;
	}
	
	/**
	 * Returns the human readable name of the current mode.
	 * @return mixed
	 */
	final public function getModeName()
	{
		return self::getModeInfoDetail( $this->getModeCode(), self::MODE_DETAIL_KEY_LABEL );
	}
	
	/**
	 * Returns the API endpoint used by the current mode.
	 * @return mixed
	 */
	final public function getEndpoint()
	{
		return self::getModeInfoDetail( $this->getModeCode(), self::MODE_DETAIL_KEY_ENDPOINT );
	}
	
	/**
	 * Returns the public key used in current mode.
	 * @return mixed()
	{
	 */
	final public function getModePublicKey()
	{
		return self::getModeInfoDetail( $this->getModeCode(), self::MODE_DETAIL_KEY_PUBLIC_KEY );
	}

	/**
	 * Return the mode code that aligns with class constants and with legacy support.
	 * @return string
	 */
	final public function getModeCode()
	{
		$mode = $this->_mode;
		if( $mode === self::MODE_LEGACY_SANDBOX ) { $mode = self::MODE_TESTING; }
		return $mode;
	}

	/**
	 * Retrieve the private key for calling APIs based on the current mode settings.
	 * Return false if the current mode is not supported.
	 * @return string|bool
	 */
	private function getPrivateKeyByMode()
	{
		switch( $this->getModeCode() ){
			case self::MODE_PRODUCTION:
				return Option::get( Constants::PARAM_PRODUCTION_PRIVATE_KEY, '' );
			case self::MODE_LEGACY_SANDBOX:
			case self::MODE_TESTING:
				return Option::get( Constants::PARAM_TESTING_PRIVATE_KEY, '' );
			default:
				return false;
		}
	}

	/**
	 * Retrieve the official portal link based on the current mode settings.
	 * Return false if the current mode is not supported.
	 * @return bool|string
	 */
	final public function getPortalLink()
	{
		switch( $this->getModeCode() ){
			case self::MODE_PRODUCTION:
				return Constants::PRODUCTION_PORTAL_LINK;
			case self::MODE_LEGACY_SANDBOX:
			case self::MODE_TESTING:
				return Constants::TESTING_PORTAL_LINK;
			default:
				return false;
		}
	}

	/**
	 * Set the mode for current instance.
	 * Returns false if the input mode is not supported.
	 * @param string $mode
	 * @return bool|string
	 */
	final public function setGatewayMode( string $mode = '' ) {
		if(!self::isModeAccepted( $mode )){
			return false;
		}
		return $this->_mode = $mode;
	}

	/**
	 * Check and set the numeric merchant ID.
	 * Returns whether the set is success.
	 * @param mixed $merchant_id
	 * @return bool
	 */
	final public function setMerchantID( $merchant_id = null ) {
		if( isset( $merchant_id ) && Utility::isInt( $merchant_id ) ) {
			$merchant_id = intval( $merchant_id );
			if( $merchant_id > 0 ) {
				$this->_merchant_id = $merchant_id;
				return true;
			}
		}
		return false;
	}

	/**
	 * Retrieve the merchant ID in string format.
	 * @return string
	 */
	final public function getMerchantID()
	{
		return strval( $this->_merchant_id );
	}

	/**
	 * Primary function for making API request to the endpoint.
	 * @param string $method GET|POST.
	 * @param string $path The endpoint action to interact with.
	 * @param array $headers Additional headers.
	 * @param array $params The content to be posted in json.
	 * @throws \Exception
	 * @return mixed
	 */
	final public function makeRequest( string $method = 'GET', string $path = '', array $headers = array(), array $params = array() ) {
		// init
		$method = trim( $method );
		if( empty( $method ) ) {
			$method = 'GET';
		}
		if( isset( $path ) && is_string( $path ) ) {
			$path = trim( $path );
			if( empty( $path ) ) {
				return false;
			}
		}
		
		// check wordpress
		if( !( function_exists( 'wp_remote_request' ) &&
			function_exists( 'is_wp_error' ) &&
			function_exists( 'wp_remote_retrieve_response_code' ) &&
			function_exists( 'wp_remote_retrieve_body' ) ) ) {
			throw new \Exception('Expected WordPress functions not exist.');
		}

		// prepare headers
		$default_headers = array(
			'accepts'			=> 'application/json',
			'content-type'		=> 'application/json',
			'plugin'			=> 'wordpress',
			'plugin-version'	=>  Core::$version,
		);
		$headers = array_merge( $headers, $default_headers );

		// create payload
		$json = json_encode( $params, JSON_UNESCAPED_UNICODE );

		// create signature
		$sign = SSL::sign($this->getPrivateKeyByMode(), $json);
		if( empty( $sign ) ){
			throw new \Exception( esc_attr__( 'Failed to sign the request, please contact technical support.', 'bbmsl-gateway' ) );
		}

		// create post request body
		$payload = array(
			'request'	=> $json,
			'signature'	=> $sign,
		);
		$post = json_encode( $payload, JSON_UNESCAPED_UNICODE );

		// send the request
		$response = wp_remote_request( $this->getEndpoint() . $path, array(
			'method'				=> $method,
			'timeout'				=> 5,
			'redirection'			=> 5,
			'httpversion'			=> '2.0',
			'user-agent'			=> sprintf( 'BBMSL Payment Gateway WordPress Plugin version %s', Core::$version ),
			'reject_unsafe_urls'	=> true,
			'blocking'				=> true,
			'headers'				=> $headers,
			'cookies'				=> array(),
			'body'					=> ( isset( $post ) && is_string( $post ) && strlen( $post ) > 0 ? $post : null ),
			'compress'				=> false,
			'decompress'			=> true,
			'sslverify'				=> true,
			'stream'				=> false,
			'filename'				=> null,
			'limit_response_size'	=> null,
		) );

		if( !is_wp_error( $response ) ) {
			if( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				if( Utility::isJson( $body ) ) {
					$json = json_decode( $body, true );
					if( isset( $json['result'] ) ) {
						if( 200 == isset( $json['result']['code'] ) ) {
							if( isset( $json['data'] ) ) {
								return $json['data'];
							}
							return true;
						}
					}
					return $json;
				}
				return $body;
			}
		}
		
		throw new \Exception( esc_attr__( 'Order production failed, please contact technical support.', 'bbmsl-gateway' ) );
	}

	/**
	 * Creates the commn fields the goes along all API requests.
	 * @return array<string>
	 */
	private function newHostedPayload()
	{
		return array( 'merchantId' => $this->getMerchantID() );
	}

	/**
	 * Merge order id and order references in the request.
	 * @param array $payload
	 * @param mixed $order_id
	 * @param string $merchant_reference
	 * @return array|bool
	 */
	private static function coeaseOrderReferences( array &$payload = array(), ?string $order_id = '', string $merchant_reference = '' ) {
		if( isset( $order_id ) ) {
			$order_id = trim( $order_id );
			if( ! empty( $order_id ) ) {
				$payload['orderId'] = $order_id;
			}
		}
		if( isset( $merchant_reference ) ) {
			$merchant_reference = trim( $merchant_reference );
			if( ! empty( $merchant_reference ) ) {
				$payload['merchantReference'] = $merchant_reference;
			}
		}
		if( self::hasOrderReference( $payload ) ) {
			return $payload;
		}
		return false;
	}

	/**
	 * Check if the current payload has order reference.
	 * @param array $payload
	 * @return bool
	 */
	private static function hasOrderReference( array &$payload = array() ) {
		if( isset( $payload ) && is_array( $payload ) && sizeof( $payload ) > 0 ) {
			return(
				( isset( $payload['orderId'] ) && !empty( $payload['orderId'] ) ) || 
				( isset( $payload['merchantReference'] ) && !empty( $payload['merchantReference'] ) )
			);
		}
		return false;
	}

	/**
	 * Action function to void an order by order ID with merchant reference.
	 * @param string $order_id
	 * @param string $merchant_reference
	 * @return mixed
	 */
	final public function voidOrder( string $order_id = '', string $merchant_reference = '' ) {
		if( isset( $order_id ) && is_string( $order_id ) && 
			isset( $merchant_reference ) && is_string( $merchant_reference ) ) {
			$payload = $this->newHostedPayload();
			self::coeaseOrderReferences( $payload, $order_id, $merchant_reference );
			if( self::hasOrderReference( $payload ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/void/', array(), $payload );
			}
		}
		return false;
	}
	
	/**
	 * Action function to query the online version of the order.
	 * @param string $merchant_reference
	 * @throws \Exception
	 * @return mixed
	 */
	final public function queryOrder( string $merchant_reference = '' ) {
		if( isset( $merchant_reference ) && is_string( $merchant_reference ) ) {
			$payload = $this->newHostedPayload();
			self::coeaseOrderReferences( $payload, null, $merchant_reference );
			if( self::hasOrderReference( $payload ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/query/', array(), $payload );
			}else{
				throw new \Exception( 'Empty Order Reference in Query Order API call.' );
			}
		}
		return false;
	}
	
	/**
	 * Action function to refund an order by order ID with merchant reference and amount.
	 * @param string $order_id
	 * @param string $merchant_reference
	 * @param float $amount
	 * @return mixed
	 */
	final public function refundOrder( string $order_id = '', string $merchant_reference = '', float $amount = 0.0 ) {
		if( 
			isset( $order_id ) && is_string( $order_id ) && 
			isset( $merchant_reference ) && is_string( $merchant_reference ) &&
			isset( $amount ) && is_float( $amount ) > 0
		) {
			$payload = $this->newHostedPayload();
			$payload['amount'] = $amount;
			self::coeaseOrderReferences( $payload, $order_id, $merchant_reference );
			if( self::hasOrderReference( $payload ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/refund/', array(), $payload );
			}
		}
		return false;
	}

	/**
	 * Action function for establishing a recurring order. Currently reserved for update.
	 * @param float $amount
	 * @param string $merchant_reference
	 * @param string $parent_order_id
	 * @return mixed
	 */
	// final public function recurringOrder( float $amount = 0.0, string $merchant_reference = '', string $parent_order_id = '' ) {
	// 	if(
	// 		isset( $amount ) && is_float( $amount ) && 
	// 		isset( $merchant_reference ) && is_string( $merchant_reference ) && 
	// 		isset( $parent_order_id ) && is_string( $parent_order_id )
	// 	) {
	// 		$payload = $this->newHostedPayload();
	// 		$payload['amount'] = $amount;
	// 		$payload['merchantReference'] = trim( $merchant_reference );
	// 		$payload['parentOrderId'] = trim( $parent_order_id );
	// 		if( $amount > 0 && ( ! empty( $payload['merchantReference'] ) ) && ( !empty( $payload['parentOrderId'] ) ) ) {
	// 			return $this->makeRequest( 'POST', 'hosted-checkout/recurring/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }
}