<?php

/**
 * BBMSL.php
 *
 * Framework facing functions for user experience.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\BBMSL
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      -
 * @deprecated -
 * @todo Co-organize order models to bring general support.
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Option;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\SSL;
use BBMSL\Plugin\Notice;
use \WC_Order_Item_Product;
use \WC_Order;

defined( 'ABSPATH' ) or exit;
final class BBMSL
{
	protected static $_instance = null;

	public const POSTED_KEY = 'BBMSL';
	public const NONCE_KEY = '_bbmsl_nonce';
	
	/**
	 * Get the singleton instance, create if not exsits.
	 * @return mixed
	 */
	final public static function instance()
	{
		if( !isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Generate sequential image HTML string for enabled payment methods.
	 * @return string
	 */
	final public static function getMethodLogoHTML()
	{
		$image_html = array();
		foreach( self::getCoeasedMethods() as $method ) {
			$image_html[] = sprintf( '<img class="logo" src="%s%s" />', BBMSL_PLUGIN_BASE_URL, $method['logo'] );
		}
		return implode( $image_html );
	}

	/**
	 * Obtain the resource link for the official BBMSL logo.
	 * @param bool $color
	 * @return string
	 */
	final public static function getLogoURL( bool $color = false )
	{
		if( $color ) {
			return sprintf( '%spublic/images/logo-full-color.min.svg', BBMSL_PLUGIN_BASE_URL );
		}
		return sprintf( '%spublic/images/logo-white.min.svg', BBMSL_PLUGIN_BASE_URL );
	}

	/**
	 * Check and return the gateway mode, update the setting whenever necessary.
	 * @return string
	 */
	final public static function ensureGatewayMode()
	{
		if( Option::has( Constants::PARAM_GATEWAY_MODE ) ) {
			$mode = Option::get( Constants::PARAM_GATEWAY_MODE, BBMSL_SDK::MODE_TESTING );
			
			// legacy rectification
			if( $mode === BBMSL_SDK::MODE_LEGACY_SANDBOX ) {
				Option::update( Constants::PARAM_GATEWAY_MODE, BBMSL_SDK::MODE_TESTING );
				$mode = BBMSL_SDK::MODE_TESTING;
			}

			if( BBMSL_SDK::isModeAccepted( $mode ) ) {
				return $mode;
			} else {
				/* translators: %s is the previous mode code before failing over */
				Notice::flash( sprintf( esc_attr__( 'Mode (%s) is not accepted, please try again.', 'bbmsl-gateway' ), $mode ), Notice::TYPE_WARNING );
			}
			Option::update( Constants::PARAM_GATEWAY_MODE, BBMSL_SDK::MODE_TESTING );
		}
		return BBMSL_SDK::MODE_TESTING;
	}

	/**
	 * Check if the current gateway settings allows for proper transactions.
	 * @return bool
	 */
	final public static function ready()
	{
		$mode = self::ensureGatewayMode();
		switch( $mode ){
			case BBMSL_SDK::MODE_PRODUCTION:
				return	Option::has( Constants::PARAM_PRODUCTION_MERCHANT_ID ) &&
					SSL::check_str( Option::get( Constants::PARAM_PRODUCTION_PUBLIC_KEY ) ) &&
					SSL::check_str( Option::get( Constants::PARAM_PRODUCTION_PRIVATE_KEY ) );
			case BBMSL_SDK::MODE_TESTING:
				return	Option::has( Constants::PARAM_TESTING_MERCHANT_ID ) &&
					SSL::check_str( Option::get( Constants::PARAM_TESTING_PUBLIC_KEY ) ) &&
					SSL::check_str( Option::get( Constants::PARAM_TESTING_PRIVATE_KEY ) );
		}
		return false;
	}

	/**
	 * Obtain the merchant ID from user settings.
	 * @return mixed
	 */
	final public static function getMerchantID()
	{
		$mode = self::ensureGatewayMode();
		if( $mode == BBMSL_SDK::MODE_PRODUCTION ) {
			return	Option::get( Constants::PARAM_PRODUCTION_MERCHANT_ID );
		}else if( $mode == BBMSL_SDK::MODE_TESTING ) {
			return	Option::get( Constants::PARAM_TESTING_MERCHANT_ID );
		}
		return false;
	}

	/**
	 * Get the submitted options it raw form.
	 * @return array|bool
	 */
	final public static function getRawPostedPayloads()
	{
		if( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return false;
		}
		if( !( isset( $_POST ) && is_array( $_POST ) && sizeof( $_POST ) > 0 ) ) {
			return false;
		}
		if( isset( $_POST[self::NONCE_KEY] ) && is_string( $_POST[self::NONCE_KEY] ) ) {
			$nounce = trim( $_POST[self::NONCE_KEY] );
			if( strlen( $nounce ) > 0 && function_exists( 'wp_verify_nonce' ) ) {
				$verify = wp_verify_nonce( $nounce, 'bbmsl-plugin' );
				if( $verify) {
					if( isset( $_POST[self::POSTED_KEY] ) && is_array( $_POST[self::POSTED_KEY] ) && sizeof( $_POST[self::POSTED_KEY] ) > 0 ) {
						return $_POST[self::POSTED_KEY];
					}
				}
				add_action( 'admin_notices', array( Notice::class, 'nonceExpiration' ) );
			}
		}
		return false;
	}
	
	// GATEWAY PAYMENT METHDOS
	final public static function getCoeasedMethods()
	{
		$available_methods = Constants::getPaymentMethodDetails();
		$preference = self::getMethodPreference();
		return array_replace( array_flip( $preference ), $available_methods );
	}
	
	final public static function getCompiledMethods()
	{
		$methods = self::getCoeasedMethods();
		$preference = self::getMethodPreference();
		$methods = array_intersect_key( $methods, array_flip( $preference ) );
		if( isset( $methods ) && is_array( $methods ) && sizeof( $methods ) > 0 ) {
			$methods = array_column( $methods, 'code' );
			if( isset( $methods ) && is_array( $methods ) && sizeof( $methods ) > 0 ) {
				return implode(',', array_values( array_unique( array_filter( array_map( 'trim', $methods ) ) ) ) );
			}
		}
		return false;
	}

	final public static function hasSelectedMethod( string $method = '' ) {
		if( !in_array( $method, Constants::getPaymentMethods(), true) ) { return false; }
		return in_array( $method, self::getMethodPreference(), true );
	}

	final public static function getMethodPreference()
	{
		$option = Option::get( Constants::PARAM_GATEWAY_METHODS );
		if( ( !empty( $option ) ) && Utility::isJson( $option ) ) {
			$option = json_decode( $option, true );

			// patch for wechat_pay to wechat_pay_hk
			if( in_array( Constants::LEGACY_PAY_METHOD_WECHAT, $option ) ) {
				if( in_array( Constants::PAY_METHOD_WECHATPAY, $option ) ) {
					$option = array_diff( $option, array( Constants::LEGACY_PAY_METHOD_WECHAT ) );
				} else {
					$index = array_search( Constants::LEGACY_PAY_METHOD_WECHAT, $option, true );
					if( $index !== false ) {
						$option[ $index ] = Constants::PAY_METHOD_WECHATPAY;
					}
				}
			}

			return $option;
		}
		return [];
	}

	final public static function getEnabledWooCommerceOrderColumns()
	{
		$option = Option::get( Constants::PARAM_WC_ORDER_COLUMNS );
		if( ( !empty( $option ) ) && Utility::isJson( $option ) ) {
			$option = json_decode( $option, true );
			return array_intersect( $option, array_keys( Constants::getWooCommerceColumns() ) );
		}
		return [];
	}

	// ORDER FUNCTIONS
	final public static function getOrder( $order_id = '' ) {
		if( empty( $order_id ) ) { return false; }
		if( !is_scalar( $order_id ) ) { return false; }
		if( !function_exists( 'wc_get_order' ) ) { return false; }
		return wc_get_order( $order_id );
	}

	final public static function getOrderID( string $order_reference = '' ) {
		if( empty( $order_reference ) ) { return false; }
		if( !is_scalar( $order_reference ) ) { return false; }
		if( !function_exists( 'wc_get_order_id_by_order_key' ) ) { return false; }
		return wc_get_order_id_by_order_key( $order_reference );
	}

	final public static function getOrderLineItems( WC_Order $order = null ) {
		$line_items = array();
		if( is_null( $order ) ) { return array(); }
		if( !method_exists( $order, 'get_items' ) ) { return array(); }
		$cart_items = $order->get_items();
		if( isset( $cart_items ) && is_iterable( $cart_items ) && sizeof( $cart_items ) > 0 ) {
			foreach( $cart_items as $k => $item) {
				if( empty( $item ) ) { continue; }
				if( !( $item instanceof WC_Order_Item_Product ) ) { continue; }
				if( !method_exists( $item, 'get_quantity' ) ) { continue; }
				$item_quantity = intval( $item->get_quantity() );
				if( !method_exists( $item, 'get_name' ) ) { continue; }
				$name = trim( $item->get_name() );
				if( !method_exists( $item, 'get_subtotal' ) ) { continue; }
				$unit_price = doubleval( $item->get_subtotal() / $item_quantity );				
				if( !( $item_quantity > 0 ) ) { continue; }
				$line_items[] = array(
					'quantity' => $item_quantity,
					'priceData' => array(
						'unitAmount' => $unit_price,
						'name' => $name,
					),
				);
			}
		}
		return $line_items;
	}

	final public static function checkOrderID( int $order_id = 0 ) {
		return $order_id > 0;
	}

	final public static function getOrderMetaByID( int $order_id = 0 ) {
		if( !self::checkOrderID( $order_id ) ) { return false; }
		if( !function_exists( 'get_post_meta' ) ) { return false; }
		$meta = get_post_meta( $order_id, Constants::META_CREATE_ORDER, true );
		if( empty( $meta) ) { return false; }
		$metadata = json_decode( $meta, true );
		if( isset( $metadata ) && is_array( $metadata ) ) {
			return $metadata;
		}
		return false;
	}

	final public static function getCheckoutURLByID( int $order_id = 0 ) {
		$metadata = self::getOrderMetaByID( $order_id );
		if( $metadata === false ) { return false; }
		if( !( is_array( $metadata ) && sizeof( $metadata ) > 0 ) ) { return false; }
		if( !( isset( $metadata['checkoutUrl'] ) ) ) { return false; }
		if( !( is_scalar( $metadata['checkoutUrl'] ) ) ) { return false; }
		if( empty( $metadata['checkoutUrl'] ) ) { return false; }
		return trim( $metadata['checkoutUrl'] . '' );
	}

	final public static function getOrderingModeByID( int $order_id = 0 ) {
		if( !self::checkOrderID( $order_id ) ) { return false; }
		if( !function_exists( 'get_post_meta' ) ) { return false; }
		$mode = get_post_meta( $order_id, Constants::META_ORDERING_MODE, true );
		if( BBMSL_SDK::isModeAccepted( $mode ) ) {
			return $mode;
		}
		return BBMSL_SDK::MODE_TESTING;
	}

	final public static function matchOrderingMode( int $order_id = 0 ) {
		if( !self::checkOrderID( $order_id ) ) { return true; }
		return self::getOrderingModeByID( $order_id ) == self::ensureGatewayMode();
	}

	final public static function getFallbackUrl()
	{
		$fallback_functions = array( 'wc_get_checkout_url', 'wc_get_cart_url', 'get_home_url' );
		foreach( $fallback_functions as $function) {
			if( function_exists( $function ) ) {
				$fallback_url = trim( $function() );
				if( strlen( $fallback_url ) > 0 ) {
					return $fallback_url;
				}
			}
		}
		return false;
	}

	final public static function getOrdeIdByID( int $order_id = 0 ) {
		$metadata = self::getOrderMetaByID( $order_id );
		if( $metadata === false ) { return null; }
		return self::getGatewayOrderID( $metadata );
	}

	final public static function getMerchantReferenceByID( int $order_id = 0 ) {
		if( function_exists( 'get_post_meta' ) ) {
			$metadata = get_post_meta( $order_id, Constants::META_MERCHANT_REF, true );
			if( $metadata !== false ) { return $metadata; }
		}
		$metadata = self::getOrderMetaByID( $order_id );
		if( $metadata === false ) { return null; }
		return self::getMerchantReference( $metadata );
	}

	final public static function getOrderByMerchantReference( string $merchant_reference = '' ) {
		$order_id = self::getOrderID( $merchant_reference );
		if( !is_scalar( $order_id ) ) { return false; }
		return self::getOrder( $order_id );
	}

	final public static function getMerchantReference( array $metadata = array() ) {
		if( !( is_array( $metadata ) && sizeof( $metadata ) > 0 ) ) { return null; }
		if( !( isset( $metadata['order'] ) ) ) { return null; }
		if( !( is_array( $metadata['order'] ) && sizeof( $metadata['order'] ) > 0 ) ) { return null; }
		if( empty( $metadata['order']['merchantReference'] ) ) { return null; }
		return trim( $metadata['order']['merchantReference'] . '' );
	}
	
	final public static function getGatewayOrderID( array $metadata = array() ) {
		if( !( is_array( $metadata ) && sizeof( $metadata ) > 0 ) ) { return null; }
		if( !( isset( $metadata['order'] ) ) ) { return null; }
		if( !( is_array( $metadata['order'] ) && sizeof( $metadata['order'] ) > 0 ) ) { return null; }
		if( empty( $metadata['order']['id'] ) ) { return null; }
		return trim( $metadata['order']['id'] . '' );
	}

	final public static function getOrderPlainMetadata( int $order_id = 0 ) {
		if( !self::checkOrderID( $order_id ) ) { return false; }
		$order = BBMSL::getOrder( $order_id );
		if( !method_exists( $order, 'get_data' ) ) { return false; }
		$data = $order->get_data();
		if( !( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) && sizeof( $data['meta_data'] ) > 0 ) ) { return false; }
		$metadata_collection = $data['meta_data'];
		if( !( isset( $metadata_collection ) && is_array( $metadata_collection ) && sizeof( $metadata_collection ) > 0 ) ) { return false; }
		$metadata = array_map( function( $e ) {
			if( !method_exists( $e, 'get_data' ) ) { return false; }
			$data = $e->get_data();
			if( !( isset( $data['value'] ) && is_string( $data['value'] ) && Utility::isJson( $data['value'] ) ) ) { return false; }
			$value = json_decode( $data['value'], true );
			if( !is_array( $value ) ) { return false; }
			return $value;
		}, $metadata_collection );
		return array_filter( $metadata );
	}

	final public static function getHoldOrderMinutes()
	{
		$minutes = Option::get( 'woocommerce_hold_stock_minutes' );
		if( empty( $minutes ) ) { return 0; }
		if( !is_numeric( $minutes ) ) { return 0; }
		return intval($minutes);
	}

	final public static function getExpiryNow()
	{
		$delay_minutes = self::getHoldOrderMinutes();
		if( $delay_minutes > 0 ) {
			$time = strtotime( sprintf( '+%d minutes', $delay_minutes ) );
			return date( 'c', $time );
		}
		return false;
	}

	final public static function updateOrderStatus($order, string $status, string $comment = '' ) {
		if( empty( $order ) ) { return false; }
		if( method_exists( $order, 'update_status' ) ) { 
			if( !$order->update_status( $status, $comment ) ) {
				return false;
			};
		} else if( method_exists( $order, 'set_status' ) ) { 
			if( !$order->set_status( $status ) ) {
				return false;
			};
		}
		if( !method_exists( $order, 'save' ) ) { return false; }
		if( !$order->save() ) { return false; }
		return true;
	}
	
	// WEBHOOK FUNCTIONS
	final public static function newApiCallInstance()
	{
		$mode = self::ensureGatewayMode();
		$gateway = false;
		if( $mode == BBMSL_SDK::MODE_PRODUCTION ) {
			$gateway = new BBMSL_SDK();
			$gateway->setGatewayMode( BBMSL_SDK::MODE_PRODUCTION );
			$gateway->setMerchantID( Option::get( Constants::PARAM_PRODUCTION_MERCHANT_ID ) );
		}else if( $mode == BBMSL_SDK::MODE_TESTING ) {
			$gateway = new BBMSL_SDK();
			$gateway->setGatewayMode( BBMSL_SDK::MODE_TESTING );
			$gateway->setMerchantID( Option::get( Constants::PARAM_TESTING_MERCHANT_ID ) );
		}
		return $gateway;
	}

	final public static function statusVoidable( string $order_status = '' ) {
		return in_array( $order_status, array( 'OPEN', 'SUCCESS' ), true );
	}

	final public static function statusRefundable( string $order_status = '' ) {
		return in_array( $order_status, array( 'SETTLED' ), true );
	}


	// Operation functions
	final public static function getOrderOnlineStatus( int $order_id = 0 ) {
		$merchant_reference = self::getMerchantReferenceByID( $order_id );
		if( empty( $merchant_reference ) ) {
			return false;
		}
		if( !self::ready() ) {
			return false;
		}
		$gateway = self::newApiCallInstance();
		$query_result = $gateway->queryOrder( $merchant_reference );
		update_post_meta( $order_id, Constants::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
		if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
			if( array_key_exists( 'message', $query_result ) ) {
				$success = 'success' === strtolower( trim( $query_result['message'] ) );
				if( !$success ) {
					throw new \Exception( esc_attr( $query_result['message'] ) );
				}
				
				if( array_key_exists( 'order', $query_result ) ) {
					$order_info = $query_result['order'];
					if( array_key_exists( 'status', $order_info ) ) {
						return strtoupper( trim( $order_info['status'] ) );
					}
				}
			}
		}
		return false;
	}
}