<?php

/**
 * PaymentGateway.php
 *
 * WooCommerce compatible payment method class.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\PaymentGateway
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      -
 * @deprecated -
 * @todo Use generalised SDK calls once ready
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use \WC_Payment_Gateway;
use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Option;
use BBMSL\Plugin\Setup;
use BBMSL\Plugin\WordPress;
use BBMSL\Plugin\WooCommerce;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\Webhook;

class PaymentGateway extends WC_Payment_Gateway
{	
	public function __construct()
	{
		$this->id					= Constants::GATEWAY_ID;
		$this->icon					= apply_filters( 'woocommerce_bbmsl_icon', BBMSL::getLogoURL( true ) );
		$this->has_fields			= false;
		$this->method_title			= __( 'BBMSL', 'bbmsl-gateway' );
		$this->method_description	= self::getDescription();
		$this->supports[] 			= 'refunds';
		
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		// Define user set variables
		$this->title = Option::get( Constants::PARAM_GATEWAY_DISPLAY_NAME, $this->method_title );
		if( WordPress::currentLanguage() == Constants::LANGUAGE_TC ) {
			$this->title = Option::get( Constants::PARAM_GATEWAY_DISPLAY_NAME_TC, $this->title );
		}
		
		$this->description = Option::get( Constants::PARAM_GATEWAY_DESCRIPTION, $this->method_description, true );
		if( WordPress::currentLanguage() == Constants::LANGUAGE_TC ) {
			$this->description = Option::get( Constants::PARAM_GATEWAY_DESCRIPTION_TC, $this->description, true );
		}
		
		$this->description .= Setup::showMethodLogosHTML();
		
		// Actions
		add_action( sprintf( 'woocommerce_update_options_payment_gateways_%d', $this->id ), array( $this, 'process_admin_options' ) );
		add_action( sprintf( 'woocommerce_thankyou_%s', $this->id ), array( Setup::class, 'setupThankYouPage' ) );
	}
	
	public static function getDescription()
	{
		return esc_attr__( 'Online payment solution for Hong Kong merchants. Supports Visa, Master, AMEX, Alipay, Wechat Pay, Apple Pay, Google Pay.', 'bbmsl-gateway' );
	}

	public function generate_settings_html( $form_fields = array(), $echo = true) {
		return WooCommerce::generateSettingsHTML();
	}
	
	public function process_payment( $order_id ) {
		if( $order = BBMSL::getOrder( $order_id ) ) {
			// check order currency
			if( method_exists( $order, 'get_currency' ) ) {
				$currency = $order->get_currency();
			}
			if( !$currency) {
				throw new \Exception( esc_attr__( 'Failed to validate order currency.', 'bbmsl-gateway' ) );
			}

			// process order line items
			$line_items = BBMSL::getOrderLineItems( $order );
			if( !( isset( $line_items ) && is_array( $line_items ) && sizeof( $line_items ) > 0) ) {
				throw new \Exception( esc_attr__( 'Cart items cannot be added to payment order. Please try refreshing to see if the existing cart is still vaild for checkout.', 'bbmsl-gateway' ) );
			}

			// build callback links settings
			$callback_urls	= array();
			$fallback_url	= BBMSL::getFallbackUrl();
			$webhook_url	= Webhook::getEndpoint();
			if( method_exists( $this, 'get_return_url' ) ) {
				$success_url = $this->get_return_url( $order );
			}
			if( !empty( $success_url ) ) {
				$callback_urls['success'] = $success_url;
			}
			if( !empty( $fallback_url ) ) {
				$callback_urls['fail'] = $fallback_url;
				$callback_urls['cancel'] = $fallback_url;
			}
			if( !empty( $webhook_url ) ) {
				$callback_urls['notify'] = $webhook_url;
			}
			
			$merchant_id					= BBMSL::getMerchantID();
			$merchant_reference				= $order->get_order_key();
			$param_show_language_tools		= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_LANGUAGE_TOOLS ) );
			$param_show_gateway_brand		= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_GATEWAY_BRAND ) );
			$param_show_order_detail		= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_ORDER_DETAIL ) );
			$param_show_email				= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_EMAIL ) );
			$param_show_merchant_reference	= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_MERCHANT_REFERENCE ) );
			$param_show_order_id			= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_ORDER_ID ) );
			$param_show_result_page			= Utility::checkBoolean( Option::get( Constants::PARAM_SHOW_RESULT_PAGE ) );
			$param_theme_color				= Option::get( Constants::PARAM_SHOW_CUSTOM_THEME_COLOR );
			$param_button_background_color	= Option::get( Constants::PARAM_SHOW_CUSTOM_BUTTON_BG_COLOR );
			$param_button_font_color		= Option::get( Constants::PARAM_SHOW_CUSTOM_BUTTON_FT_COLOR );

			$payload = array(
				'merchantId'			=> $merchant_id,
				'amount'				=> $order->get_total(),
				'merchantReference'		=> $merchant_reference,
				'callbackUrl'			=> $callback_urls, // callback urls
				'currency'				=> $currency,
				'isRecurring'			=> 0,
				'lineItems'				=> $line_items,
				'showLang'				=> $param_show_language_tools,
				'showPoweredBy'			=> $param_show_gateway_brand,
				'showOrderDetail'		=> $param_show_order_detail,
				'showEmail'				=> $param_show_email,
				'showMerchantReference'	=> $param_show_merchant_reference,
				'showOrderId'			=> $param_show_order_id,
				'showResultPage'		=> $param_show_result_page,
			);

			$color_params = array(
				'themeColor'			=> $param_theme_color,
				'buttonBackgroundColor'	=> $param_button_background_color,
				'buttonFontColor'		=> $param_button_font_color,
			);
			foreach( $color_params as $k => $row ) {
				if( !empty( $row ) && Utility::isColor( $row ) ) {
					$payload[ $k ] = $row;
				}
			}

			// adjust methods
			$compiled_methods = BBMSL::getCompiledMethods();
			if( isset( $compiled_methods ) && is_string( $compiled_methods ) && strlen( $compiled_methods ) > 0 ) {
				$payload['paymentMethods'] = $compiled_methods;
			} 

			if( $expiry = BBMSL::getExpiryNow() ) {
				$payload['expiryTime'] = $expiry;
			}else{
				$payload['expiryTime'] = date( 'c', strtotime( '+24 hours' ) );
			}
			
			// start bbmsl flow
			if( $gateway = BBMSL::newApiCallInstance() ) {
				$result = $gateway->makeRequest( 'POST', 'hosted-checkout/create/', array(), $payload );
				if( isset( $result ) && is_array( $result ) && sizeof( $result ) > 0 ) {
					// ON CREATE
					BBMSL::updateOrderStatus( $order, Option::get( Constants::PARAM_ORDER_STATUS_ON_CREATE ) );
					$order_id = $order->get_id();
					if( function_exists( 'update_post_meta' ) ) {
						update_post_meta( $order_id, Constants::META_ORDERING_MODE, $gateway->getModeCode() );
						update_post_meta( $order_id, Constants::META_MERCHANT_ID, $merchant_id );
						update_post_meta( $order_id, Constants::META_MERCHANT_REF, $merchant_reference );
						update_post_meta( $order_id, Constants::META_ORDER_ID, $order_id );
						update_post_meta( $order_id, Constants::META_CREATE_ORDER, json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					}
					if( function_exists( 'add_post_meta' ) ) {
						add_post_meta( $order_id, Constants::META_LAST_WEBHOOK, json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					}
					if( isset( $result['message'] ) && is_string( $result['message'] ) ) {
						$error_message = trim( $result['message'] );
						if( 'success' !== strtolower( $error_message ) ) {
							if( strlen( $error_message ) > 0 ) {
								$error_message_compare = strtolower( trim( $error_message ) );
								if( 'no public key' == $error_message_compare ) {
									throw new \Exception( esc_attr__( '[Error - No Public Key] For further assist, please contact customer service.', 'bbmsl-gateway' ) );
								}else if( $error_message_compare == 'invalid signature' ) {
									throw new \Exception( esc_attr__( '[Error - Invalid Key] For further assist, please contact customer service.', 'bbmsl-gateway' ) );
								}
								/* translators: %s is the returned gateway error */
								throw new \Exception( sprintf( esc_attr__( 'Gateway Error: %s', 'bbmsl-gateway' ), $error_message) );
							}
							throw new \Exception( esc_attr__( 'An error has occured.', 'bbmsl-gateway' ) );
						}
					}
					if( isset( $result['checkoutUrl'] ) ) {
						$success_url = trim( $result['checkoutUrl'] );
						update_post_meta( $order_id, Constants::META_CHECKOUT_LINK, $success_url );
						wc_reduce_stock_levels( $order );
						wc_empty_cart();
						return array(
							'result' 	=> 'success',
							'redirect'	=> $success_url,
						);
					}
				}
				throw new \Exception( esc_attr__( 'Failed to obtain checkout session.', 'bbmsl-gateway' ) );
			}
			throw new \Exception( esc_attr__( 'Failed to initiate gateway.', 'bbmsl-gateway' ) );
		}
		throw new \Exception( esc_attr__( 'Failed to get order instance.', 'bbmsl-gateway' ) );
	}

	public function process_refund( $order_id, $amount = null, $reason = '') {
		return true;
	}
}