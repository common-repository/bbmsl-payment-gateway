<?php

/**
 * SSL.php
 *
 * Packaged SSL Encryption support class.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\SSL
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      -
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use phpseclib3\Crypt\RSA;

class SSL
{
    private const DEFAULT_KEY_SIZE  = 2048;
    private const CERT_TYPE_PKCS1   = 'PKCS1';
    private const CERT_TYPE_PKCS8   = 'PKCS8';
    
    public static function newKeyPair()
	{
		$keypair		= RSA::createKey( self::DEFAULT_KEY_SIZE );
		$public_key		= $keypair->getPublicKey();
		$public_str		= $public_key->toString( self::CERT_TYPE_PKCS8 );
		$private_str	= $keypair->toString( self::CERT_TYPE_PKCS1 );
		return array(
			'public'	=> $public_str,
			'private'	=> $private_str,
		);
	}

	public static function isPem( ?string $content = '' ) {
		if( isset( $content ) && is_string( $content ) ) {
			return preg_match( '/-{5}BEGIN\s(?:PUBLIC|(?:RSA\s)?PRIVATE)\sKEY-{5}\r?\n(?:[a-z0-9\\/=\+]{1,64}\r?\n)+\r?\n?-{5}END\s(?:PUBLIC|(?:RSA\s)?PRIVATE)\sKEY-{5}/i', trim( $content ) );
		}
		return false;
	}

	public static function pem2str( ?string $pem = null ) {
        if( !self::isPem( $pem ) ) { return $pem; }
        $pem = explode( "\n", str_replace( "\r", '', $pem ) );
        $pem = array_filter( array_map( 'trim', $pem ) );
        if( is_array( $pem ) && sizeof( $pem ) > 0 ) {
            $pem = implode( array_slice( $pem, 1, -1 ) );
        }
		return $pem;
	}

	public static function str2pem( ?string $str = null, string $type = 'PUBLIC' ) {
        if( self::isPem( $str ) ) {
            $str = self::pem2str( $str );
        }
        if( empty( $str ) ) { return false; }
        $str = preg_replace( '/[^a-z0-9\\/=\+]/i', '', $str );
        return sprintf("-----BEGIN %s KEY-----\n%s-----END %s KEY-----\n\n", $type, chunk_split( $str, 64, "\n" ), $type );
	}

	public static function check_str( ?string $key = '' ) {
		if( isset( $key ) && is_string( $key ) ) {
			return 1 === preg_match( '/^[a-z0-9\\/=\+]+$/i', trim( $key ) );
		}
		return false;
	}

	public static function createPreVerifyString( array $params = array() ) {
		ksort( $params );
		array_walk( $params, function( &$e, $k ) {
			$e = sprintf( '%s=%s', $k, $e );
		} );
		return implode( '&', $params );
	}

	public static function verify( string $key = '', string $content = '', string $signiture = '' ) {
        if( ! self::isPem( $key ) ) { $key = self::str2pem( $key, 'PUBLIC' ); }
        return openssl_verify( $content, base64_decode( $signiture ), $key, OPENSSL_ALGO_SHA256 );
    }

	public static function sign( string $key = '', string $content = '' ) {
        if( ! self::isPem( $key ) ) {
            $key = self::str2pem( $key, 'RSA PRIVATE' );
        }
        if( $key === false ) { 
            throw new \Exception('Provided string cannot be converted into a valid PEM format.');
        }
        openssl_sign( $content, $encrypted, $key, OPENSSL_ALGO_SHA256 );
        return base64_encode( $encrypted );
	}
}