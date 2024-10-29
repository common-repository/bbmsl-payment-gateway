<?php

/**
 * WordPress.php
 *
 * WordPress framework supporting functions.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Plugin\WordPress
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      -
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Plugin;

use BBMSL\Bootstrap\Constants;
use BBMSL\Sdk\Utility;
use \WP_Screen;

class WordPress
{
	/**
	 * Obtain a list of installed, active and valid plugins.
	 * @param bool $bypass_cache
	 * @return mixed
	 */
	final public static function getActivePlugins()
	{
		return array_map( function( $e ) {
			$comp = explode( DIRECTORY_SEPARATOR, realpath( $e ) );
			return implode( '/', array_slice( $comp, -2, 2 ) );
		}, wp_get_active_and_valid_plugins() );
	}

	/**
	 * Output the admin notice HTML to the top of management page
	 * @param string $content HTML content
	 * @param string $class CSS class: success|warning|info|error
	 * @param bool $dismissible
	 * @return void
	 */
	final public static function adminNotice( string $content = '', string $class = "info", bool $dismissible = true ) {
		echo Utility::safeHTML( sprintf( '<div class="notice notice-%s bbmsl_notice%s">%s</div>', $class, ( $dismissible ? ' is-dismissible' : '' ), $content ), true );
	}

	/**
	 * Get current screen name via WordPress.
	 * Compare the current screen against a given screen name.
	 * @param string $compare
	 * @return bool|mixed|string
	 */
	final public static function currentScreen( string $compare = '' ) {
		if( !function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		if( isset( $screen ) && $screen instanceof WP_Screen ) {
			if( ! empty( $compare ) ) {
				return boolval( $compare == $screen->id );
			}
			return $screen->id;
		}
		return false;
	}

	/**
	 * Check if current screen is in list.
	 * Useful for checking conditions for a common block of code.
	 * @param array $screens
	 * @return bool
	 */
	final public static function isScreens( array $screens = array() ) {
		if( isset( $screens ) && is_array( $screens ) ) {
			$screens = array_values( array_unique( array_filter( $screens, function($e) {
				if ( is_object($e) && $e instanceof WP_Screen ) {
					if( isset( $e->id ) ) {
						$e = $e->id;
					}
				}
				if( is_string( $e ) ) {
					$e = trim( $e );
					if( strlen( $e ) > 0 ) {
						return $e;
					}
				}
				return false;
			} ) ) );
			if( sizeof( $screens ) > 0 ) {
				return in_array( self::currentScreen(), $screens, true );
			}
		}
		return false;
	}
	
	/**
	 * Cleans a string to plaintext as per WordPress specified functions.
	 * @param string $string
	 * @param bool $preserve_new_line
	 * @return string
	 */
	final public static function plaintext( string $string = '', bool $preserve_new_line = false ) {
		if( $preserve_new_line ) {
			if( function_exists( 'sanitize_textarea_field' ) ) {
				return sanitize_textarea_field( $string );
			}
			return $string;
		}
		if( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $string );
		}
		return $string;
	}

	/**
	 * Cleans a string to HTML as per WordPress specified functions.
	 * @param string $string
	 * @return string
	 */
	final public static function richtext( string $string = '' ) {
		$prev_string = '';
		while( $prev_string === $string ) {
			$prev_string = $string;
			if( function_exists( 'wptexturize' ) ) {
				$string = wptexturize( $string, true );
			}
			if( function_exists( 'wpautop' ) ) {
				$string = wpautop( $string, true );
			}
			$string = trim( $string );
			if( empty( $string ) ) {
				return '';
			}
		}
		return $string;
	}

	/**
	 * Determine the bilingual language by locale string.
	 * @return string
	 */
	final public static function currentLanguage()
	{
		if( function_exists( 'get_locale' ) ) {
			$language = get_locale();
			$supported_locale = array(
				'en_US' => Constants::LANGUAGE_EN,
				'en_AU' => Constants::LANGUAGE_EN,
				'en_CA' => Constants::LANGUAGE_EN,
				'en_GB' => Constants::LANGUAGE_EN,
				'zh_HK' => Constants::LANGUAGE_TC,
				'zh_TW' => Constants::LANGUAGE_TC,
				'zh_CN' => Constants::LANGUAGE_TC,
			);
			if( array_key_exists( $language, $supported_locale ) ) {
				return strval( $supported_locale[ $language ] );
			}
		}
		return Constants::LANGUAGE_EN;
	}

	/**
	 * Get WooCommerce order statuses.
	 * @return array<string>
	 */
	final public static function getWooCommerceOrderStatuses()
	{
		if(function_exists('wc_get_order_statuses')){
			return wc_get_order_statuses();
		}
		return array(
			Constants::WC_ORDER_STATUS_COMPLETED => esc_attr__( 'Completed', 'woocommerce' ),
			Constants::WC_ORDER_STATUS_PENDING => esc_attr__( 'Pending payment', 'woocommerce' ),
			Constants::WC_ORDER_STATUS_HOLD => esc_attr__( 'On hold', 'woocommerce' ),
			Constants::WC_ORDER_STATUS_FAILED => esc_attr__( 'Failed', 'woocommerce' ),
			Constants::WC_ORDER_STATUS_PROCESSING => esc_attr__( 'Processing', 'woocommerce' ),
			Constants::WC_ORDER_STATUS_CANCELLED => esc_attr__( 'Cancelled', 'woocommerce' ),
			Constants::WC_ORDER_STATUS_REFUNDED => esc_attr__( 'Refunded', 'woocommerce' ),
		);
	}

	/**
	 * Determine of input is a WooCommerce order status.
	 * @param string $status
	 * @return bool
	 */
	final public static function isWooCommerceOrderStatus( string $status ) {
		return array_key_exists( $status, self::getWooCommerceOrderStatuses() );
	}
}