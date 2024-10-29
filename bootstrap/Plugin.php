<?php

/**
 * Plugin.php
 *
 * Bootstrap functions as a WordPress plugin, driver concept for all starting functions.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Bootstrap\Plugin
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      1.0.18 - Migrated and gathered boot related functions.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Bootstrap;

use BBMSL\BBMSL as Core;
use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Notice;
use BBMSL\Plugin\Option;
use BBMSL\Plugin\Setup;
use BBMSL\Plugin\WooCommerce;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\Webhook;

class Plugin
{
	public const PRIORITY = 2147483647;

	/**
	 * Declairation of plugin default option values.
	 * @return array
	 */
	final public static function getDefaults()
	{
		return array(
			Constants::PARAM_GATEWAY_MODE					=> BBMSL_SDK::MODE_TESTING,
			Constants::PARAM_GATEWAY_REFUND					=> false,
			Constants::PARAM_GATEWAY_METHODS				=> json_encode([
				Constants::PAY_METHOD_ALIPAYHK,
				Constants::PAY_METHOD_MASTERCARD,
				Constants::PAY_METHOD_VISA,
				Constants::PAY_METHOD_WECHATPAY,
			]),
			Constants::PARAM_GATEWAY_DISPLAY_NAME			=> 'BBMSL',
			Constants::PARAM_GATEWAY_DESCRIPTION			=> '<p>Pay with BBMSL gateway, your full range payments service provider.</p>',
			Constants::PARAM_GATEWAY_THANK_YOU_PAGE			=> '<p>Thank you for checking out with BBMSL.</p>',
			Constants::PARAM_GATEWAY_EMAIL_CONTENT			=> '<p>Payment service powered by BBMSL.</p>',
			Constants::PARAM_GATEWAY_DISPLAY_NAME_TC		=> 'BBMSL',
			Constants::PARAM_GATEWAY_DESCRIPTION_TC			=> '<p>歡迎使用BBMSL，您的全方位電子支付方式。</p>',
			Constants::PARAM_GATEWAY_THANK_YOU_PAGE_TC		=> '<p>多謝使用BBMSL付款服務。</p>',
			Constants::PARAM_GATEWAY_EMAIL_CONTENT_TC		=> '<p>電子支付技術由BBMSL提供。</p>',
			Constants::PARAM_EXPRESS_CHECKOUT				=> false,
			Constants::PARAM_SHOW_LANGUAGE_TOOLS			=> true,
			Constants::PARAM_SHOW_GATEWAY_BRAND				=> true,
			Constants::PARAM_BROWSER_DEFAULT_LANG			=> Constants::LANGUAGE_EN,
			Constants::PARAM_SHOW_ORDER_DETAIL				=> true,
			Constants::PARAM_SHOW_EMAIL						=> true,
			Constants::PARAM_SHOW_MERCHANT_REFERENCE		=> true,
			Constants::PARAM_SHOW_ORDER_ID					=> true,
			Constants::PARAM_SHOW_RESULT_PAGE				=> true,
			Constants::PARAM_SHOW_CUSTOM_THEME_COLOR		=> null,
			Constants::PARAM_SHOW_CUSTOM_BUTTON_BG_COLOR	=> null,
			Constants::PARAM_SHOW_CUSTOM_BUTTON_FT_COLOR	=> null,
			Constants::PARAM_ORDER_STATUS_ON_CREATE			=> Constants::WC_ORDER_STATUS_PENDING,
			Constants::PARAM_ORDER_STATUS_ON_SUCCESS		=> Constants::WC_ORDER_STATUS_HOLD,
			Constants::PARAM_ORDER_STATUS_ON_FAILED			=> Constants::WC_ORDER_STATUS_FAILED,
			Constants::PARAM_ORDER_STATUS_ON_PAID			=> Constants::WC_ORDER_STATUS_PROCESSING,
			Constants::PARAM_ORDER_STATUS_ON_VOIDED			=> Constants::WC_ORDER_STATUS_CANCELLED,
			Constants::PARAM_ORDER_STATUS_ON_REFUNDED		=> Constants::WC_ORDER_STATUS_REFUNDED,
			Constants::PARAM_WC_ORDER_COLUMNS 				=> json_encode([
				Constants::COLUMN_KEY_BBMSL_ORDER_ID,
				Constants::COLUMN_KEY_BBMSL_MERCHANT_REFERENCE,
			]),
		);
	}
	
	/**
	 * Assign necessary funciton bindings to WordPress actions and hooks.
	 * @return void
	 */
	final public static function bootstrap()
	{
		self::prepareDatabase();
		add_action( 'template_redirect', array( Webhook::class, 'handle' ) );
		if( function_exists( 'is_admin' ) ? is_admin() : true) {
			$plugin_file = basename( BBMSL_PLUGIN_DIR ) . '/' . basename( BBMSL_PLUGIN_FILE );
			add_action( 'admin_init', array( WooCommerce::class, 'init' ), -1 * self::PRIORITY, 0);
			add_filter( sprintf( 'plugin_action_links_%s', $plugin_file ), array( Setup::class, 'setupPluginActionLinks' ) );
			add_filter( 'plugin_row_meta', array( Setup::class, 'setupPluginMeta' ), 10, 2 );
			add_action( 'admin_menu', array( Setup::class, 'attachToMenu' ), Plugin::PRIORITY);
			add_action( 'admin_menu', array( Setup::class, 'attachAdminPages' ), Plugin::PRIORITY);
			add_action( 'admin_notices', array( Notice::class, 'recall' ) );
			add_action( 'admin_enqueue_scripts', function() {
				wp_enqueue_style( 'bbmsl-fonts', BBMSL_PLUGIN_BASE_URL . 'public/css/fonts.style.css', array (), Core::$version, 'all' );
				wp_enqueue_style( 'bbmsl-style', BBMSL_PLUGIN_BASE_URL . 'public/css/admin-style.css', array (), Core::$version, 'all' );
			}, self::PRIORITY );
		}
		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_style( 'bbmsl-gateway', plugin_dir_url( BBMSL_PLUGIN_FILE) . 'public/css/public-style.css' );
		}, self::PRIORITY );
		add_filter( 'woocommerce_payment_gateways', array( WooCommerce::class, 'bindGateway') );
		add_action( 'woocommerce_widget_shopping_cart_buttons', array( WooCommerce::class, 'setupExpressCheckout' ) );
		add_action( 'wp_footer', array( WooCommerce::class, 'setupExpressCheckoutDisplay' ) );
		self::loadTextDomain();

		if(class_exists( 'WC_Payment_Gateway' ) ){
			include_once( BBMSL_PLUGIN_DIR . 'sdk' . DIRECTORY_SEPARATOR . 'PaymentGateway.php' );
		}
	}
	
	/**
	 * Manifest default values into the database if not found.
	 * @return void
	 */
	private static function updateDefaultOptions()
	{
		$defaults = self::getDefaults();
		foreach( $defaults as $key => $value ) {
			if( Option::has( $key ) ) {
				continue;
			}
			if( is_null( $value ) ) {
				continue;
			}
			$type = gettype( $value );
			switch( $type ) {
				case 'bool':
				case 'boolean':
					Option::update( $key, ( $value ? 'true' : 'false' ) );
				case 'array':
					Option::update( $key, json_encode( $value, JSON_UNESCAPED_UNICODE ) );
				default:
					Option::update( $key, $value );
			}
		}
	}

	/**
	 * Removes legacy option keys from the wp_options table.
	 * @return void
	 */
	private static function removeLegacyOptions()
	{
		$accepted_options = Constants::getAcceptedOptions();
		if( isset( $accepted_options ) && is_array( $accepted_options ) && sizeof( $accepted_options ) > 0 ) {
			global $wpdb;
			$wpdb->query(
				sprintf(
					"DELETE FROM `%s` WHERE `option_name` LIKE 'bbmsl_%%' AND `option_name` NOT IN (%s);",
					$wpdb->options,
					implode(', ', array_map( function($e){ return sprintf( "'bbmsl_%s'", $e ); }, $accepted_options) )
				)
			);
		}
	}

	/**
	 * Apply version specific updates to the database
	 * @return void
	 */
	private static function updateDatabase()
	{
		$current_version = Option::get( Constants::PARAM_DB_VERSION, 0);
		if(1 > $current_version){
			Option::update( Constants::PARAM_ORDER_STATUS_ON_SUCCESS, Constants::WC_ORDER_STATUS_HOLD );
			Option::update( Constants::PARAM_ORDER_STATUS_ON_FAILED, Constants::WC_ORDER_STATUS_FAILED );
			Option::update( Constants::PARAM_ORDER_STATUS_ON_VOIDED, Constants::WC_ORDER_STATUS_CANCELLED );
			Option::update( Constants::PARAM_ORDER_STATUS_ON_REFUNDED, Constants::WC_ORDER_STATUS_REFUNDED );
			// Option::update( Constants::PARAM_DB_VERSION, 1);
		}
		// if(2 > $current_version){
		// 	Option::update( Constants::PARAM_ORDER_STATUS_ON_SUCCESS, Constants::WC_ORDER_STATUS_HOLD );
		// 	Option::update( Constants::PARAM_ORDER_STATUS_ON_FAILED, Constants::WC_ORDER_STATUS_FAILED );
		// 	Option::update( Constants::PARAM_ORDER_STATUS_ON_VOIDED, Constants::WC_ORDER_STATUS_CANCELLED );
		// 	Option::update( Constants::PARAM_ORDER_STATUS_ON_REFUNDED, Constants::WC_ORDER_STATUS_REFUNDED );
		// 	Option::update( Constants::PARAM_DB_VERSION, 2);
		// }
		if(3 > $current_version){
			Option::update( Constants::PARAM_DB_VERSION, 3);
		}
	}

	/**
	 * Execute inital database sequences
	 * @return void
	 */
	private static function prepareDatabase()
	{
		self::updateDefaultOptions();
		self::removeLegacyOptions();
		self::updateDatabase();
	}

	/**
	 * Loads plugin translation files if exists.
	 * @return void
	 */
	final public static function loadTextDomain()
	{
		$language_directory = BBMSL_PLUGIN_DIR . 'i18n' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
		if( !function_exists( 'get_locale' ) ) {
			return;
		}
		if( !function_exists( 'load_textdomain' ) ) {
			return;
		}
		$mofile_realpath = sprintf( '%sbbmsl-gateway-%s.mo', $language_directory, get_locale() );
		if( Utility::file( $mofile_realpath ) ) {
			load_textdomain( 'bbmsl-gateway', $mofile_realpath );
		}
	}

	/**
	 * Collect system info and output in HTML for debug display.
	 * @return string
	 */
	public static function getDebugText()
	{
		// Prepare current time.
		$debug_info = array(
			date('c')
		);

		// Prepare operating system
		if( function_exists( 'php_uname' ) ) {
			$debug_info[] = sprintf( 'Setup: %s', php_uname() );
		}
		
		// Prepare detected PHP version
		if( function_exists( 'phpversion' ) ) {
			$additinal_check = '';
			if( function_exists( 'version_compare' ) && defined( 'PHP_VERSION' ) ) {
				if( $check = version_compare( PHP_VERSION, '7.0' ) ) {
					$additinal_check = sprintf( '<span style="color:%s">%s</span>', '#00ff00', __( 'OK', 'bbmsl-gateway' ) );
				}else{
					$additinal_check = sprintf( '<span style="color:%s">%s</span>', '#ff0000', __( 'PHP Update required!', 'bbmsl-gateway' ) );
				}
			}
			$debug_info[] = sprintf( __( 'PHP Version: %s %s', 'bbmsl-gateway' ), phpversion(), $additinal_check );
		}

		// Prepare test results for server to gateway traffic and access availability.
		$test_domain_dns = array(
			'payapi.sit.bbmsl.com',
			'payapi.prod.bbmsl.com',
		);
		foreach( $test_domain_dns as $domain ){
			$check = checkdnsrr( $domain, 'A' );
			if( $check ) {
				$additinal_check = sprintf( '<span style="color:%s">%s</span>', '#00ff00', __( 'OK', 'bbmsl-gateway' ) );
			} else {
				$additinal_check = sprintf( '<span style="color:%s">%s</span>', '#ff0000', __( 'Failed', 'bbmsl-gateway' ) );
			}
			$debug_info[] = sprintf( __( 'DNS probe for: %s %s', 'bbmsl-gateway' ), $domain, $additinal_check );
		}

		// Prepare the internal database version, reflects the top version of plugin ever existed on the server.
		$debug_info[] = sprintf( __( 'Internal DB version: %d', 'bbmsl-gateway' ), Option::get( Constants::PARAM_DB_VERSION ) );
		
		// Prepare current memory usage.
		if( function_exists('memory_get_usage' ) ) {
			$debug_info[] = sprintf( __( 'Memory usage: %s', 'bbmsl-gateway' ), Utility::bytes2size( memory_get_usage() ) );
		}

		// Prepare server settings regarding uploads.
		$debug_info[] = sprintf( __( 'Max. upload size: %s', 'bbmsl-gateway' ), Utility::bytes2size( Utility::maxUploadSize() ) );
		$debug_info[] = sprintf( __( 'Max. upload time: %ss', 'bbmsl-gateway' ), Utility::maxUploadTime() );
		
		// Prepare server disk space availability.
		if( function_exists('disk_free_space' ) ) {
			$debug_info[] = sprintf( __( 'Disk free space: %s', 'bbmsl-gateway' ), Utility::bytes2size( disk_free_space('.') ) );
		}
		if( function_exists('disk_total_space' ) ) {
			$debug_info[] = sprintf( __( 'Disk total space: %s', 'bbmsl-gateway' ), Utility::bytes2size( disk_total_space('.') ) );
		}
		
		// Prepare the webhook URL for direct testing.
		$webhook_endpoint = Webhook::getEndpoint();
		if(!empty($webhook_endpoint)){
			$debug_info[] = sprintf('Webhook endpoint: <a href="%s" target="_blank">%s</a>', $webhook_endpoint, $webhook_endpoint);
		}

		// Combine all prepared content for display.
		return implode( "\n", $debug_info );
	}
}
