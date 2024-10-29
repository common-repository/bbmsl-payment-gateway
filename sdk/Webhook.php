<?php

/**
 * Webhook.php
 *
 * HTTP and webhook specific support class for WordPress.
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

use BBMSL\Controllers\WebhookController;

class Webhook
{
	private const IDENTIFIER = 'bbmsl-gateway/webhook';

	/**
	 * Get the hostname of the current website.
	 * @return bool|string
	 */
	private static function getHost()
	{
		if( function_exists( 'home_url' ) ) {
			$home_url = home_url();
			$parsed = parse_url( $home_url );
			return sprintf( '%s://%s', $parsed['scheme'], $parsed['host'] );
		}
		return false;
	}
	
	/**
	 * Get the endpoint URL of the current website.
	 * @return string
	 */
	public static function getEndpoint()
	{
		return home_url( '/bbmsl-gateway/webhook/notification' );
	}

	/**
	 * Get the escaped raw URL pathname.
	 * Returns false on failure.
	 * @return bool|string
	 */
	private static function getPathname()
	{
		if( function_exists( 'esc_url_raw' ) ) {
			return ltrim( esc_url_raw( $_SERVER['REQUEST_URI'] ), '/' );
		}
		return false;
	}
	
	/**
	 * Get the path name without query or anchor parameters.
	 * @return string
	 */
	private static function getBasePathname()
	{
		$pathname = self::getHost() . self::getPathname();
		if( str_contains( $pathname, '?' ) ) {
			$pathname = substr( $pathname, 0, stripos( $pathname, '?' ) );
		}
		if( str_contains( $pathname, '#' ) ) {
			$pathname = substr( $pathname, 0, stripos( $pathname, '#' ) );
		}
		return $pathname;
	}

	/**
	 * Determine if the current request is a webhook request by checking whether the pathname matches the realm name.
	 * @return bool
	 */
	public static function isWebhookRequest()
	{
		return stripos( self::getPathname(), self::IDENTIFIER ) !== false;
	}

	/**
	 * Handle the webhook request accordingly.
	 * @return void
	 */
	public static function handle()
	{
		if( !self::isWebhookRequest() ) {
			return;
		}

		$path_name = self::getPathname();

		$route_name = substr( $path_name, stripos($path_name, self::IDENTIFIER) + strlen( self::IDENTIFIER ) );
		$route_name = trim( $route_name, '/' );
		$route_comps = explode( '/', $route_name );
		$route = '';

		if( sizeof( $route_comps ) > 0 ) {
			$route = $route_comps[0];
		}

		if( in_array( '.', $route_comps, true) || in_array( '..', $route_comps, true) ) {
			http_response_code(404);
			exit();
		}

		switch($route){
			case 'notification':
				list( $response_message, $response_code ) = WebhookController::notification();
				header( 'content-type: text/plain; charset=utf-8' );
				http_response_code( $response_code );
				print_r( $response_message );
				exit();
		}
	}
}