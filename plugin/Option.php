<?php

/**
 * Option.php
 *
 * WordPress supporting function for options.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Plugin\Option
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      -
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Plugin;

use BBMSL\Bootstrap\Plugin;
use BBMSL\Sdk\Utility;

class Option
{
	private const OPTIONS_PREFIX = 'bbmsl_';

	/**
	 * Applies the option prefix.
	 * @param string $option
	 * @return string
	 */
	private static function name( string $option ) {
		if( stripos( $option, self::OPTIONS_PREFIX ) === false ){
			return sprintf( '%s%s', self::OPTIONS_PREFIX, $option );
		}
		return $option;
	}

	/**
	 * Get default value for an option.
	 * @param string $option
	 * @return mixed
	 */
	final public static function default( string $option = '' ) {
		$defaults = Plugin::getDefaults();
		$option = self::name( $option );
		if( array_key_exists( $option, $defaults ) ) {
			return $defaults[ $option ];
		}
		return null;
	}

	/**
	 * Checks if an option exists.
	 * @param string $option
	 * @return bool
	 */
	final public static function has( string $option = '' ) {
		$option = self::name( $option );
		return !empty( self::get( $option ) );
	}

	/**
	 * Get a specific option by name.
	 * @param string $option The option name.
	 * @param mixed $default An overriding default.
	 * @param bool $strip_slashes Additional strip slashes.
	 * @param bool $skip_default Bypass getting plugin definded defaults, exposing a null value.
	 * @return mixed
	 */
	final public static function get( string $option = '', $default = null, bool $strip_slashes = false, bool $skip_default = false ) {
		if( !function_exists( 'get_option' ) ) {
			return false;
		}
		$option = self::name( $option );
		$value = get_option( $option, $default );
		if( isset( $value ) ) {
			if( is_string( $value ) ) {
				if( $strip_slashes) {$value = stripslashes( $value );}
				return Utility::safeHTML( $value );
			}
			return $value;
		}
		if( $skip_default ) {
			return null;
		}
		return self::default( $option );
	}

	/**
	 * Update an option specific to this plugin.
	 * @param string $option The option name.
	 * @param mixed $value The serialized value.
	 * @param bool $autoload Whether to autoload this value from start.
	 * @return bool Returns if the update is successful.
	 */
	final public static function update( string $option = '', $value = null, bool $autoload = true ) {
		$option = self::name( $option );
		return function_exists('update_option') && update_option( $option, $value, $autoload );
	}

	/**
	 * Removes the option from database.
	 * @param string $option
	 * @return bool
	 */
	final public static function remove( string $option = '' ){
		$option = self::name( $option );
		return function_exists( 'delete_option' ) && ( !empty( $option ) ) && delete_option( $option );
	}
}