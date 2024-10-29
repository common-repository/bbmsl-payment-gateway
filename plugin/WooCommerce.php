<?php

/**
 * WooCommerce.php
 *
 * All WooCommerce related operations.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Plugin\WooCommerce
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      -
 * @deprecated -
 * @todo Generalize class public functions for unified future eCommerce integrations.
 * @todo Co-organize order models to bring WooCommerce native function call support.
 */

declare( strict_types = 1 );
namespace BBMSL\Plugin;

use BBMSL\BBMSL as Core;
use BBMSL\Bootstrap\Constants;
use BBMSL\Bootstrap\Plugin;
use BBMSL\Controllers\RefundController;
use BBMSL\Plugin\Setup;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\PaymentGateway;
use BBMSL\Sdk\Utility;
use WC_Order_Refund;

class WooCommerce
{
	/**
	 * Register all handling for WooCommerce related actions and hooks.
	 * Returns true if the initialization completes successfully.
	 * @return bool
	 */
	final static public function init()
	{
		self::doInstallGuide();
		if( function_exists( 'is_admin' ) ? is_admin() : true) {
			add_action( 'admin_enqueue_scripts', function() {
				if( self::isActive() && post_type_exists( 'shop_order' ) && WordPress::isScreens( array( 'woocommerce_page_wc-settings', 'shop_order' ) ) ) {
					wp_enqueue_style( 'bbmsl-bootstrap',		BBMSL_PLUGIN_BASE_URL . 'public/css/bootstrap-grid.min.css', array (), Core::$version, 'all' );
					wp_enqueue_style( 'bbmsl-coloris',			BBMSL_PLUGIN_BASE_URL . 'public/plugins/coloris/coloris.min.css', array (), Core::$version, 'all' );
					wp_enqueue_style( 'jquery-ui' );
					wp_enqueue_script( 'jquery' );
					wp_enqueue_script( 'jquery-ui-sortable' );
					wp_enqueue_script( 'bbmsl-settings', 			BBMSL_PLUGIN_BASE_URL . 'public/js/woocommerce_payment_settings/scripts.js', array(), Core::$version, true );
					wp_enqueue_script( 'bbmsl-tinymce',				BBMSL_PLUGIN_BASE_URL . 'public/plugins/tinymce/tinymce.min.js', array(), Core::$version, true );
					wp_enqueue_script( 'bbmsl-coloris',				BBMSL_PLUGIN_BASE_URL . 'public/plugins/coloris/coloris.min.js', array(), Core::$version, true );
					wp_enqueue_script( 'bbmsl-tinymce-config', 		BBMSL_PLUGIN_BASE_URL . 'public/js/woocommerce_payment_settings/tinymce.js', array( 'bbmsl-tinymce' ), Core::$version, true );
					wp_enqueue_script( 'bbmsl-sortable-methods',	BBMSL_PLUGIN_BASE_URL . 'public/js/woocommerce_payment_settings/sortable_methods.js', array( 'jquery', 'jquery-ui-sortable' ), Core::$version, true );
				}
			}, Plugin::PRIORITY );
			add_filter( 'woocommerce_gateway_title',				array( self::class, 'setupPluginGatewayTitle' ), Plugin::PRIORITY, 2 );
			add_filter( 'woocommerce_gateway_description',			array( self::class, 'setupPluginGatewayDescription' ), Plugin::PRIORITY, 2 );
			add_filter( 'woocommerce_bbmsl_icon',					array( self::class, 'setupPluginGatewayIcon' ), Plugin::PRIORITY, 2 );
			add_action( 'woocommerce_email_footer',					array( self::class, 'setupOrderEmailFooter' ), -1 * Plugin::PRIORITY, 1 );
			add_filter( 'manage_edit-shop_order_columns', 			array( self::class, 'setupOrderColumns' ), Plugin::PRIORITY, 1 );
			add_filter( 'manage_edit-shop_order_sortable_columns',	array( self::class, 'setupOrderSortableColumns' ), Plugin::PRIORITY, 1 );
			add_action( 'manage_shop_order_posts_custom_column', 	array( self::class, 'setupOrderColumnsData' ), Plugin::PRIORITY, 2 );
			add_action( 'admin_head',			 					array( self::class, 'setupOrderDetails' ), Plugin::PRIORITY, 0 );
			add_action( 'woocommerce_create_refund',				array( self::class, 'setupRefundHandling' ), 0 );
			add_action( 'post_updated',								array( Setup::class, 'setupVoidHandling' ), 0 );
		}
		return true;
	}

	/**
	 * Check if WooCommerce is installed.
	 * @return void
	 */
	public static function isInstalled():bool
	{
		if( function_exists( 'get_plugins' ) ) {
			return array_key_exists( 'woocommerce/woocommerce.php', get_plugins() );
		}
		return false;
	}

	/**
	 * Check if WooCommerce is active.
	 * @return bool
	 */
	public static function isActive():bool
	{
		return in_array( 'woocommerce/woocommerce.php', WordPress::getActivePlugins(), true );
	}

	/**
	 * Push notices to guide WooCommerce install and activation process, followed by setting up the gateway.
	 * @return void
	 */
	private static function doInstallGuide():void
	{
		if( self::isActive() ) {
			if( !BBMSL::ready() ) {
				add_action( 'admin_notices', array( Notice::class, 'requiredSetup' ), -1 * Plugin::PRIORITY, 0 );
			}
		} else {
			if( self::isInstalled() ) {
				add_action( 'admin_notices', array( Notice::class, 'activateWooCommerce' ), -1 * Plugin::PRIORITY, 0 );
			} else {
				add_action( 'admin_notices', array( Notice::class, 'requiredWooCommerce' ), -1 * Plugin::PRIORITY, 0 );
			}
		}
	}

	/**
	 * Payment gateway settings page handler functions.
	 * @return void
	 */
	final public static function generateSettingsHTML()
	{
		if( $ev_payload = BBMSL::getRawPostedPayloads() ) {
			if( isset( $ev_payload ) && is_array( $ev_payload ) && sizeof( $ev_payload ) > 0 ) {
				Setup::updatePaymentGatewaySettings( $ev_payload );
			}
		}
		Setup::testModeSettings( BBMSL::ensureGatewayMode() );
		Setup::view( 'woocommerce_payments_settings' );
	}

	/**
	 * Filter function for overriding gateway title on bbmsl gateways by user config.
	 * @param string $title
	 * @param string $payment_id
	 * @return string
	 */
	final public static function setupPluginGatewayTitle( string $title = '', string $payment_id = '' ) {
		if( Constants::GATEWAY_ID === $payment_id ) {
			if( WordPress::currentLanguage() === Constants::LANGUAGE_TC ) {
				return Option::get( Constants::PARAM_GATEWAY_DISPLAY_NAME_TC );
			}
			return Option::get( Constants::PARAM_GATEWAY_DISPLAY_NAME );
		}
		return $title;
	}

	/**
	 * Filter function for overriding gateway description on bbmsl gateways by user config.
	 * @param string $title
	 * @param string $payment_id
	 * @return string
	 */
	final public static function setupPluginGatewayDescription( string $description = '', string $payment_id = '' ) {
		if( Constants::GATEWAY_ID === $payment_id ) {
			if( WordPress::currentLanguage() === Constants::LANGUAGE_TC ) {
				return Option::get( Constants::PARAM_GATEWAY_DESCRIPTION_TC, $description, true );
			}
			return Option::get( Constants::PARAM_GATEWAY_DESCRIPTION, $description, true );
		}
		return $description;
	}

	/**
	 * Filter function for overriding gateway icon in HTML string format.
	 * @param string $title
	 * @param string $payment_id
	 * @return string HTML
	 */
	final public static function setupPluginGatewayIcon( string $icon = '', string $payment_id = '' ) {
		if( Constants::GATEWAY_ID === $payment_id ) {
			return BBMSL::getMethodLogoHTML();
		}
		return $icon;
	}

	/**
	 * Filter function for overriding order email footer on bbmsl gateways by user config.
	 * @return bool
	 */
	final public static function setupOrderEmailFooter( ?\WC_Order $order = null )
	{
		if ($order instanceof \WC_Order && Constants::GATEWAY_ID === $order->get_payment_method()) {
			return true;
		}
		$value = Option::get( Constants::PARAM_GATEWAY_EMAIL_CONTENT, '', true );
		if( WordPress::currentLanguage() == Constants::LANGUAGE_TC ) {
			$value = Option::get( Constants::PARAM_GATEWAY_EMAIL_CONTENT_TC, $value, true );
		}
		echo $value;
		return true;
	}

	/**
	 * Setup admin panel order view additional columns.
	 * @param array $columns
	 * @return array
	 */
	final public static function setupOrderColumns( array $columns ) {
		$result = array();
		$count = 0;
		foreach( $columns as $k => $row ) {
			if( 2 === $count++ ) {
				$enabled_columns = BBMSL::getEnabledWooCommerceOrderColumns();
				$woocommerce_columns = Constants::getWooCommerceColumns();
				foreach( $woocommerce_columns as $kk => $rrow){
					if( in_array( $kk, $enabled_columns, true ) ) {
						$result[ $kk ] = $rrow['name'];
					}
				}
			}
			$result[$k] = $row;
		}
		return $result;
	}

	/**
	 * Setup admin panel order view additional columns to becoming sortable.
	 * @param array $columns
	 * @return array
	 */
	final public static function setupOrderSortableColumns( array $columns ) {
		$enabled_columns = BBMSL::getEnabledWooCommerceOrderColumns();
		$woocommerce_columns = Constants::getWooCommerceColumns();
		foreach ($woocommerce_columns as $k => $row) {
			if (in_array( $k, $enabled_columns, true)) {
				$columns[ $k ] = $row['name'];
			}
		}
		$columns['order_email'] = __( 'Email', 'woocommerce');
		$columns['order_phone'] = __( 'Phone', 'woocommerce');
		$columns['payment_method'] = __( 'Payment Method', 'woocommerce');
		return $columns;
	}

	/**
	 * Output function during processing data for additional columns.
	 * @param string $column
	 * @param int $post_id
	 * @return void
	 */
	final public static function setupOrderColumnsData( $column, $post_id ) {
		if( Constants::COLUMN_KEY_BBMSL_ORDER_ID === $column ) {
			$order = BBMSL::getOrder( strval( $post_id ) );
			if ($order instanceof \WC_Order && Constants::GATEWAY_ID === $order->get_payment_method()) {
				$order_id = BBMSL::getOrdeIdByID($post_id);
				if ($order_id === null) {
					echo '?';
				}
				echo $order_id;
			}else{
				echo '-';
			}
		} else if( Constants::COLUMN_KEY_BBMSL_MERCHANT_REFERENCE === $column ) {
			$order = BBMSL::getOrder( strval( $post_id ) );
			if ($order instanceof \WC_Order && Constants::GATEWAY_ID === $order->get_payment_method()) {
				$merchant_reference = BBMSL::getMerchantReferenceByID($post_id);
				if ($merchant_reference === null) {
					echo '?';
				}
				echo $merchant_reference;
			}else{
				echo '-';
			}
		// } else if( 'payment_method' === $column ) {
		// 	$order = wc_get_order( $post_id );
		// 	echo $order->get_payment_method();
		}
	}

	/**
	 * Injection function to include BBMSL payment gateway into WooCommerce.
	 * @param array $gateways
	 * @return array
	 */
	final public static function bindGateway( array $gateways = array() ) {
		if( ( function_exists( 'is_admin' ) ? is_admin() : false ) ? true : BBMSL::ready() ) {	
			array_splice( $gateways, 0, 0, PaymentGateway::class );
		}
		return $gateways;
	}

	/**
	 * Setup shopping cart express checkout button.
	 * @return void
	 */
	final public static function setupExpressCheckout()
	{
		if( !BBMSL::ready() ) {
			return;
		}
		if( !function_exists( 'wc_get_checkout_url' ) ) {
			return;
		}
		
		echo sprintf( '<a class="button checkout wc-forward bbmsl-btn bbmsl-express-checkout" id="bbmsl_express_checkout" href="%s">%s</a>',
			esc_url( sprintf( '%s?gateway=bbmsl', wc_get_checkout_url() ) ),
			esc_attr__( 'BBMSL Checkout', 'bbmsl-gateway' )
		);
		
		WooCommerce::setupExpressCheckoutDisplay();
		
		add_action('wp_footer', function () {
			WooCommerce::setupExpressCheckoutDisplay();
		}, Plugin::PRIORITY );
	}

	/**
	 * Setup shopping cart express checkout button.
	 * @return void
	 */
	final public static function setupExpressCheckoutDisplay()
	{
		if( Utility::checkBoolean( Option::get( Constants::PARAM_EXPRESS_CHECKOUT ) ) ) {
			echo '<style>#bbmsl_express_checkout{display:block!important;}</style>';
		}else{
			echo '<style>#bbmsl_express_checkout{display:none!important;}</style>';
		}
	}
	
	/**
	 * Setup order details.
	 * @return void
	 */
	final public static function setupOrderDetails()
	{
		if( WordPress::currentScreen( 'shop_order' ) ) {

			// obtain order info
			if( function_exists( 'get_the_ID' ) ) {
				$order_id	= intval( get_the_ID() );
				$metadata	= BBMSL::getOrderMetaByID( $order_id );
				$order		= BBMSL::getOrder( strval( $order_id ) );
			};

			// check order payment method to be of our gateway before proceeding
			$order_payment_method = 'unknown';
			if( method_exists( $order, 'get_payment_method' ) ) {
				$order_payment_method = $order->get_payment_method();
			}

			if( !( isset( $order_payment_method ) && is_string( $order_payment_method ) ) ) {
				$order_payment_method = strtolower( trim( $order_payment_method ) );
			}
			
			if( $order_payment_method !== Constants::GATEWAY_ID ) {
				return;
			}
			
			// process the order for fetching API info
			$error = false;
			$query_result	= '';
			$order_info		= '';
			$order_status	= '';
			$merchant_reference = BBMSL::getMerchantReferenceByID( $order_id );
			$portal_link = Constants::TESTING_PORTAL_LINK;

			if( BBMSL::ready() && $gateway = BBMSL::newApiCallInstance() ) {
				$portal_link = $gateway->getPortalLink();
				if( ! empty( $merchant_reference ) ) {
					$query_result = $gateway->queryOrder( $merchant_reference );
					update_post_meta( $order_id, Constants::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
						if( isset( $query_result['message'] ) && is_string( $query_result['message'] ) ) {
							$api_message = strtolower( trim( $query_result['message'] ) );
							if( ! empty( $api_message ) ) {
								if( 'success' == $api_message ) {
									if( isset( $query_result['order'] ) && is_array( $query_result['order'] ) && sizeof( $query_result['order'] ) > 0 ) {
										$order_info = $query_result['order'];
										if( isset( $order_info['status'] ) ) {
											$order_status = strtoupper( trim( $order_info['status'] ) );
										}
									}
								}else{
									$error = esc_attr__( sprintf( __( '[API Message] %s', 'bbmsl-gateway'), $api_message ) );
								}
							}
						}
					}else{
						$error = esc_attr__( 'Query returned empty result.', 'bbmsl-gateway' );
					}
				}else{
					$error = esc_attr__( 'Empty order ID or merchant reference.', 'bbmsl-gateway' );
				}
			}else{
				$error = esc_attr__( 'Cannot create new API call instance.', 'bbmsl-gateway' );
			}

			add_action( 'submitpost_box', function() use(
				$query_result,
				$order_id,
				$order_info,
				$order_status,
				$error,
				$metadata,
				$portal_link
			) {
				return Setup::view( 'woocommerce_order_status', array(
					'query_result'		=> $query_result,
					'order_id'			=> $order_id,
					'order_info'		=> $order_info,
					'order_status'		=> $order_status,
					'error'				=> $error,
					'metadata'			=> $metadata,
					'portal_link'		=> $portal_link,
				) );
			} );
		}
	}

	final public static function setupRefundHandling( $refund ) : void
	{
		if( $refund instanceof WC_Order_Refund ){
			RefundController::handleWpAjaxWooCommerceRefundRequest( $refund );
		}
	}
}