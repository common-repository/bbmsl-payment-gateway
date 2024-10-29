<?php

/**
 * Utility.php
 *
 * chiucs123 Utility class for this plugin.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Utility
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      -
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use \DateTime;
use \DateTimeZone;

class Utility
{
    /**
     * Check if a given string is a valid json.
     * @param string $string
     * @return bool
     */
    final public static function isJson( string $string = '' )
    {
        json_decode( $string );
        return JSON_ERROR_NONE === json_last_error();
    }

    /**
     * Check if a given representation is an integer.
     * @param mixed $input
     * @return bool
     */
    final public static function isInt( $input )
    {
        if ( isset( $input ) && is_scalar( $input ) ) {
            return is_int( $input ) || ctype_digit( $input );
        }
        return false;
    }

    /**
     * Resolves the input to a boolean value, with common perception considered.
     * @param mixed $input
     * @return bool
     */
    final public static function checkBoolean( $input )
    {
        if ( isset( $input ) && is_scalar( $input )) {
            if ( is_string( $input ) ) {
                $input = strtolower( trim( $input ) );
            }
            if ( is_numeric( $input ) ) {
                $input = intval( $input );
            }

            return in_array( $input, array( 'yes', 'on', 'true', 'enable', '1', 1, true ), true);
        }

        return false;
    }

    /**
     * Converts the string timezome to another timezone.
     * @param string $fromTime
     * @param string $fromTimezone
     * @param string $toTimezone
     * @return string
     */
    final public static function dateFromTimezone( string $fromTime = '', string $fromTimezone = 'UTC', string $toTimezone = 'Asia/Hong_Kong' )
    {
        $from = new DateTimeZone( $fromTimezone );
        $to = new DateTimeZone( $toTimezone );
        $orgTime = new DateTime( $fromTime, $from );
        $toTime = new DateTime( $orgTime->format( 'c' ) );
        $toTime->setTimezone( $to );
        return $toTime->format( 'c' );
    }

    /**
     * Filters input HTML string to be of representation only, stripping all script and style tags, optionally allows links.
     * @param string $value
     * @param bool $allow_links
     * @return string
     */
    final public static function safeHTML( string $value = '', bool $allow_links = false )
    {
        return strip_tags( $value, '<span><p><i><div><strong><b><img><hr><em><sup><sub><del><h1><h2><h3><h4><h5><h6><pre><s><br><ul><li>' . ( $allow_links ? '<a>' : '' ) );
    }
	
    /**
     * Checks for file existance and accessibility.
     * @param string $realpath
     * @param string $safe_bounds
     * @param bool $check_write
     * @return bool|string
     */
    final public static function file( string $realpath, string $safe_bounds = '', bool $check_write = false )
    {
        if ( empty( $realpath ) ) {
            return false;
        }
        $realpath = realpath( $realpath );
        if ( !$realpath ) {
            return false;
        }
        if ( !empty( $safe_bounds ) ) {
            $safe_bounds = realpath( $safe_bounds );
            if ( stripos( $realpath, $safe_bounds ) > 0 ) {
                return false;
            };
        }
        if ( is_dir( $realpath ) ) {
            return false;
        }
        if ( 0 === filesize( $realpath ) ) {
            return false;
        }
        if ( !file_exists( $realpath ) ) {
            return false;
        }
        if ( !is_readable( $realpath ) ) {
            return false;
        }
        if ( $check_write && !is_writable( $realpath ) ) {
            return false;
        }
        return $realpath;
    }

    /**
     * Cleans array with unique non-null values
     * @param array $array
     * @return array
     */
    final public static function array_ready( array $array = array() )
    {
        return array_values( array_unique( array_filter( $array ) ) );
    }
    
    /**
     * Cnverts numeber bytes into string representation.
     * @param mixed $bytes
     * @param int $precision
     * @return bool|string
     */
    final public static function bytes2Size( $bytes = 0, int $precision = 2 )
    {
        if ( !is_numeric( $bytes ) ) {
            return false;
        }
        $base = floor( log( $bytes ) / log( 1024 ) );
        $units = array( 'bytes','KB','MB','GB','TB','PB','EB','ZB','YB','XB','SB','DB' );
        if ( 0 === $base ) {
            return sprintf( '%d %s', $bytes, $units[ $base ] );
        }
        $size = number_format( $bytes / pow( 1024, $base ), $precision ) * 1;
        return sprintf( '%s %s', $size, $units[ $base ] );
    }

    /**
     * Converts string representations of file size into bytes.
     * @param string $filesize
     * @return float|int
     */
    final public static function size2Bytes( string $filesize )
    {
        if ( empty( $filesize ) ) {
            return 0;
        }
        $size = '';
        preg_match( '/([\d,.]+)\s?(\S+)/', $filesize, $matches );
        if ( sizeof( $matches ) > 1 ) {
            $size = floatval( str_replace(',', '', $matches[ 1 ] ) );
            $unit = strtoupper( trim( $matches[ 2 ] ) );
        }
        $units = array( 'BYTES','KB','MB','GB','TB','PB','EB','ZB','YB','XB','SB','DB' );
        $size = floatval( $size );
        if ( 1 === strlen( $unit ) && 'B' !== $unit ) {
            $unit .= 'B';
        }
        if ( in_array( $unit, array( 'B', 'BYTE' ), true ) ) {
            $unit = 'BYTES';
        }
        if ( is_numeric( $size ) && in_array( $unit, $units, true ) ) {
            $index = array_search( $unit, $units );
            if ( false !== $index ) {
                return intval( $size ) * pow( 1024, $index );
            }
        }
        return -1;
    }

    /**
     * Checks if string is a hex color.
     * @param string $value
     * @return bool
     */
    final public static function isColor( string $value ) {
        return preg_match( '/^#[0-9A-F]{6}$/', $value ) !== false;
    }

    /**
     * Obtains server maximum allowed upload filesize.
     * @return float|int
     */
    final public static function maxUploadSize()
    {
        return self::size2Bytes( min( ini_get( 'upload_max_filesize' ), ini_get( 'post_max_size' ) ) );
    }

    /**
     * Obtains server maximum allowed upload time limit.
     * @return int
     */
    final public static function maxUploadTime()
    {
        $input = intval( ini_get( 'max_input_time' ) );
        if ( $input === -1 ) {
            return intval( ini_get( 'max_execution_time' ) );
        }
        return $input;
    }
}