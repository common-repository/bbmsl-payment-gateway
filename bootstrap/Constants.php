<?php

/**
 * Constants.php
 *
 * Contains system constants for definative directives.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Bootstrap\Constants
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      1.0.18 - Constants from source files collectively.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Bootstrap;

use ReflectionClass;

class Constants
{
	private static $_cache_params = null;
	private static $_cache_meta = null;
	private static $_cache_pay_methods = null;
	private static $_cache_currencies = null;


	public const GATEWAY_ID									= 'bbmsl';

	public const DATETIME_FORMAT							= 'Y-m-d H:i:s';
	
	// parameter keys
	public const PARAM_GATEWAY_DISPLAY_NAME					= 'display_name';
	public const PARAM_GATEWAY_DESCRIPTION					= 'description';
	public const PARAM_GATEWAY_THANK_YOU_PAGE				= 'thank_you_page';
	public const PARAM_GATEWAY_EMAIL_CONTENT				= 'email_footer_content';
	public const PARAM_GATEWAY_DISPLAY_NAME_TC				= 'display_name_tc';
	public const PARAM_GATEWAY_DESCRIPTION_TC				= 'description_tc';
	public const PARAM_GATEWAY_THANK_YOU_PAGE_TC			= 'thank_you_page_tc';
	public const PARAM_GATEWAY_EMAIL_CONTENT_TC				= 'email_footer_content_tc';
	public const PARAM_GATEWAY_REFUND						= 'gateway_refund';
	public const PARAM_EXPRESS_CHECKOUT						= 'express_checkout_enabled';
	public const PARAM_SHOW_LANGUAGE_TOOLS					= 'show_language_tools_enabled';
	public const PARAM_SHOW_GATEWAY_BRAND					= 'show_gateway_brand_enabled';
	public const PARAM_SHOW_ORDER_DETAIL					= 'show_order_detail';
	public const PARAM_SHOW_EMAIL							= 'show_email';
	public const PARAM_SHOW_MERCHANT_REFERENCE				= 'show_merchant_reference';
	public const PARAM_SHOW_ORDER_ID						= 'show_order_id';
	public const PARAM_SHOW_RESULT_PAGE						= 'show_result_page';
	public const PARAM_SHOW_CUSTOM_THEME_COLOR				= 'custom_theme_color';
	public const PARAM_SHOW_CUSTOM_BUTTON_BG_COLOR			= 'custom_button_background_color';
	public const PARAM_SHOW_CUSTOM_BUTTON_FT_COLOR			= 'custom_button_font_color';
	public const PARAM_GATEWAY_MODE							= 'gateway_mode';
	public const PARAM_GATEWAY_METHODS						= 'gateway_methods';
	public const PARAM_BROWSER_DEFAULT_LANG					= 'browser_default_lang';
	public const PARAM_PRODUCTION_MERCHANT_ID				= 'master_production_merchant_id';
	public const PARAM_PRODUCTION_PUBLIC_KEY				= 'master_production_public_key';
	public const PARAM_PRODUCTION_PRIVATE_KEY				= 'master_production_private_key';
	public const PARAM_PRODUCTION_KEY_LAST_UPDATE			= 'master_production_key_last_update';
	public const PARAM_TESTING_MERCHANT_ID					= 'master_sandbox_merchant_id';
	public const PARAM_TESTING_PUBLIC_KEY					= 'master_sandbox_public_key';
	public const PARAM_TESTING_PRIVATE_KEY					= 'master_sandbox_private_key';
	public const PARAM_TESTING_KEY_LAST_UPDATE				= 'master_sandbox_key_last_update';

	public const PARAM_WC_ORDER_COLUMNS 					= 'wc_order_columns';
	
	// order status
	public const PARAM_ORDER_STATUS_ON_CREATE				= 'status_on_create';
	public const PARAM_ORDER_STATUS_ON_SUCCESS				= 'status_on_success';
	public const PARAM_ORDER_STATUS_ON_FAILED				= 'status_on_failed';
	public const PARAM_ORDER_STATUS_ON_PAID					= 'status_on_paid';
	public const PARAM_ORDER_STATUS_ON_VOIDED				= 'status_on_voided';
	public const PARAM_ORDER_STATUS_ON_REFUNDED				= 'status_on_refunded';
	public const PARAM_DB_VERSION							= 'db_version';
	
	// meta keys
	public const META_ORDERING_MODE							= 'bbmsl_ordering_mode';
	public const META_MERCHANT_ID							= 'bbmsl_merchant_id';
	public const META_MERCHANT_REF							= 'bbmsl_merchant_reference';
	public const META_ORDER_ID								= 'bbmsl_order_id';
	public const META_CHECKOUT_LINK							= 'bbmsl_checkout_link';
	public const META_CREATE_ORDER							= 'bbmsl_create_order';
	public const META_LAST_QUERY							= 'bbmsl_last_query';
	public const META_LAST_VOID								= 'bbmsl_last_void';
	public const META_LAST_WEBHOOK							= 'bbmsl_last_webhook';
	public const META_THANK_YOU_PAGED						= 'bbmsl_thank_you_paged';
	
	// payment methods
	public const PAY_METHOD_VISA							= 'visa';
	public const PAY_METHOD_MASTERCARD						= 'mastercard';
	public const PAY_METHOD_APPLEPAY						= 'apple_pay';
	public const PAY_METHOD_GOOGLEPAY						= 'google_pay';
	public const PAY_METHOD_ALIPAYHK						= 'alipay_hk';
	public const PAY_METHOD_ALIPAYCN						= 'alipay_cn';
	public const PAY_METHOD_WECHATPAY						= 'wechat_pay_hk';
	public const LEGACY_PAY_METHOD_WECHAT					= 'wechat_pay';
	
	// ui exchange keys
	public const ACTION_REGEN_PRODUCTION_KEYS				= 'regenerate-production-keys';
	public const ACTION_REGEN_TESTING_KEYS					= 'regenerate-sandbox-keys';

	// ui column keys
	public const COLUMN_KEY_BBMSL_ORDER_ID 					= 'bbmsl_order_id';
	public const COLUMN_KEY_BBMSL_MERCHANT_REFERENCE 		= 'bbmsl_merchant_reference';
	
	// currencies
	public const CURRENCY_HKD								= 'HKD';
	public const CURRENCY_USD								= 'USD';
	public const CURRENCY_EUR								= 'EUR';
	public const CURRENCY_JPY								= 'JPY';
	public const CURRENCY_GBP								= 'GBP';
	public const CURRENCY_CAD								= 'CAD';
	public const CURRENCY_AUD								= 'AUD';
	public const CURRENCY_THB								= 'THB';
	public const CURRENCY_VND								= 'VND';
	public const CURRENCY_SGD								= 'SGD';
	public const CURRENCY_TWD								= 'TWD';
	public const CURRENCY_KRW								= 'KRW';
	public const CURRENCY_IDR								= 'IDR';
	
	// woocommerce order status
	public const WC_ORDER_STATUS_COMPLETED					= 'wc-completed';
	public const WC_ORDER_STATUS_PENDING					= 'wc-pending';
	public const WC_ORDER_STATUS_HOLD						= 'wc-on-hold';
	public const WC_ORDER_STATUS_FAILED						= 'wc-failed';
	public const WC_ORDER_STATUS_PROCESSING					= 'wc-processing';
	public const WC_ORDER_STATUS_CANCELLED					= 'wc-cancelled';
	public const WC_ORDER_STATUS_REFUNDED					= 'wc-refunded';
	public const TESTING_PORTAL_LINK						= 'https://merchant.sit.bbmsl.com/user/login';
	public const PRODUCTION_PORTAL_LINK						= 'https://merchant.bbmsl.com/user/login';
	public const LANGUAGE_EN								= 'en';
	public const LANGUAGE_TC								= 'zh-HK';

	// constant methods
	final public static function getAcceptedOptions()
{
		if(is_null(self::$_cache_params)){
			$reflection = new ReflectionClass(self::class);
			$constants = $reflection->getConstants();
			self::$_cache_params = array_filter( $constants, function($e){
				return stripos($e, 'PARAM_') === 0;
			}, ARRAY_FILTER_USE_KEY );
		}
		return self::$_cache_params;
	}

	final public static function getMetaKeys()
	{
		if(is_null(self::$_cache_meta)){
			$reflection = new ReflectionClass(self::class);
			$constants = $reflection->getConstants();
			self::$_cache_meta = array_filter( $constants, function($e){
				return stripos($e, 'META_') === 0;
			}, ARRAY_FILTER_USE_KEY );
		}
		return self::$_cache_meta;
	}

	final public static function getPaymentMethods()
	{
		if(is_null(self::$_cache_pay_methods)){
			$reflection = new ReflectionClass(self::class);
			$constants = $reflection->getConstants();
			self::$_cache_pay_methods = array_filter( $constants, function($e){
				return stripos($e, 'PAY_METHOD_') === 0;
			}, ARRAY_FILTER_USE_KEY );
		}
		return self::$_cache_pay_methods;
	}

	final public static function getCurrencies()
	{
		if(is_null(self::$_cache_currencies)){
			$reflection = new ReflectionClass(self::class);
			$constants = $reflection->getConstants();
			self::$_cache_currencies = array_filter( $constants, function($e){
				return stripos($e, 'CURRENCY_') === 0;
			}, ARRAY_FILTER_USE_KEY );
		}
		return self::$_cache_currencies;
	}

	final public static function getPaymentMethodDetails()
	{
		return array(
			self::PAY_METHOD_ALIPAYCN => array(
				'name'	=> 'Alipay',
				'logo'	=> 'public/images/methods/Alipay_CN.png',
				'code'	=> 'ALIPAYCN',
			),
			self::PAY_METHOD_ALIPAYHK => array(
				'name'	=> 'AlipayHK',
				'logo'	=> 'public/images/methods/Alipay_HK.png',
				'code'	=> 'ALIPAYHK',
			),
			self::PAY_METHOD_APPLEPAY => array(
				'name'	=> 'Apple Pay',
				'logo'	=> 'public/images/methods/ApplePay.png',
				'code'	=> 'APPLEPAY',
			),
			self::PAY_METHOD_GOOGLEPAY => array(
				'name'	=> 'Google Pay',
				'logo'	=> 'public/images/methods/Googlepay.png',
				'code'	=> 'GOOGLEPAY',
			),
			self::PAY_METHOD_MASTERCARD => array(
				'name'	=> 'Mastercard',
				'logo'	=> 'public/images/methods/Mastercard.png',
				'code'	=> 'CARD',
			),
			self::PAY_METHOD_VISA => array(
				'name'	=> 'Visa',
				'logo'	=> 'public/images/methods/VISA.png',
				'code'	=> 'CARD',
			),
			self::PAY_METHOD_WECHATPAY => array(
				'name'	=> 'WeChat Pay HK',
				'logo'	=> 'public/images/methods/Wechatpay.png',
				'code'	=> 'WECHATPAY',
			),
		);
	}

	final public static function getWooCommerceColumns()
	{
		return array(
			self::COLUMN_KEY_BBMSL_ORDER_ID => array(
				'name' => esc_attr__( 'BBMSL Order ID', 'bbmsl-gateway' ),
			),
			self::COLUMN_KEY_BBMSL_MERCHANT_REFERENCE => array(
				'name' => esc_attr__( 'Merchant Reference', 'bbmsl-gateway' ),
			),
		);
	}
}