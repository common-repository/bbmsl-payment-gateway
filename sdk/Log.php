<?php

/**
 * Log.php
 *
 * Log genreration related functions. All logging operations should refer to this class in subsequent development.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Log
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      1.0.18
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

class Log
{
	public const TYPE_WEBHOOK = 'webhook';
	public const TYPE_DEBUG = 'debug';

	/**
	 * Types accepted to be logged.
	 * @return array<string>
	 */
	final public static function getTypes()
	{
		return array(
			self::TYPE_DEBUG,
			self::TYPE_WEBHOOK,
		);
	}

	/**
	 * Check if the input type is accepted.
	 * @param string $type
	 * @return bool
	 */
	final public static function isAcceptedType( string $type = '' ) {
		return in_array( $type, self::getTypes(), true );
	}

	/**
	 * Prepare the logging directory for storing log files, create if not exists.
	 * @return string
	 */
    private static function directory()
	{
		$expected_directory = sprintf( '%slogs%s', BBMSL_PLUGIN_DIR, DIRECTORY_SEPARATOR );
		if( !is_dir( $expected_directory ) ) {
			mkdir( $expected_directory, 0755, true );
		}
		chmod( $expected_directory, 0755 );
		return $expected_directory;
	}
	
	/**
	 * Obtain the expected log file based on type provided, return false for non-accepted types.
	 * @param string $type
	 * @return bool|string
	 */
	final public static function getExpectedLogFile( string $type = '' ){
		if( self::isAcceptedType( $type ) ) {
			return sprintf( '%s%s.log.json', self::directory(), esc_attr( $type ) );
		}
		return false;
	}

	/**
	 * Accepts a payload and record into log file of respective type, automatically handles json complications.
	 * @param string $type
	 * @param array $entry
	 * @param bool $stack
	 * @return bool
	 */
	public static function put( string $type = '', array $entry = array(), bool $stack = false ){
		if( empty( $entry ) ) { return false; }
		$expected_log_file = self::getExpectedLogFile( $type );

		if( !Utility::file( $expected_log_file ) ) {
			file_put_contents( $expected_log_file, "[\n" );
		}

		if( Utility::file( $expected_log_file ) && filesize( $expected_log_file ) === 0 ) {
			file_put_contents( $expected_log_file, "[\n" );
		}
		
		if( Utility::file( $expected_log_file ) ) {
			$line = array(
				'timestamp' => date( 'Y-m-d H:i:s.u' ),
				'type' => $type,
				'content' => $entry,
			);
			if( $stack && function_exists('debug_backtrace') ) {
				$line['stack'] = debug_backtrace();
			}
			return file_put_contents( $expected_log_file, sprintf( "%s,\n", json_encode( $line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ), FILE_APPEND ) > 0;
		}
		return false;
	}

	final public static function tagAction(){
		self::put( 'debug', array( 'action' => current_action(), 'filter' => current_filter(), 'args' => func_get_args() ) );
	}
}