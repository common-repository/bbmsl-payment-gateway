<?php

/**
 * Notice.php
 *
 * Notice related functions.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Plugin\Notice
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      -
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Plugin;

use BBMSL\Bootstrap\Constants;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Plugin\Setup;
use BBMSL\Plugin\WordPress;

class Notice
{
	public const TYPE_SUCCESS	= 'success';
	public const TYPE_WARNING	= 'warning';
	public const TYPE_FAILED	= 'failed';
	public const TYPE_NOTICE	= 'notice';
	public const TYPE_ERROR		= 'error';

	public const DEFAULT_FLASH_CLASS = 'info';
	public const FLASH_KEY = 'bbmsl_flash';

	/**
	 * Runtime storage for notices
	 * @var array
	 */
	private static $errors = array();

	/**
	 * Clears the runtime storage for notice.
	 * @return void
	 */
	final public static function resetErrors()
	{
		self::$errors = array();
	}

	/**
	 * Definitions of accepted notice types.
	 * @return array<string>
	 */
	private static function getNoticeTypes()
	{
		return array(
			self::TYPE_SUCCESS,
			self::TYPE_WARNING,
			self::TYPE_FAILED,
			self::TYPE_NOTICE,
			self::TYPE_ERROR,
		);
	}

	/**
	 * Checks if the input notice type is accepted.
	 * @param string $type
	 * @return bool
	 */
	final public static function isAcceptedTypes( string $type = '' ) {
		return in_array( $type, self::getNoticeTypes(), true );
	}

	/**
	 * Setup session storage for flash messages.
	 * Returns whether flash message storage is available.
	 * @return bool
	 */
	private static function setupFlashMsgSession()
	{
		$session_status = session_status();
		if( $session_status === PHP_SESSION_DISABLED ) {
			WordPress::adminNotice( sprintf( '<p>%s</p>', esc_attr__( 'Session is disabled on server, flash messages and some user reminders would not work. It\'s strongly advised to have sessions back on via adjusting PHP settings.', 'bbmsl-gateway' ) ), self::TYPE_ERROR, true );
			return false;
		}
		if( $session_status === PHP_SESSION_NONE && !headers_sent() ) {
			session_start();
		}
		if( !( isset( $_SESSION[self::FLASH_KEY] ) && is_array( $_SESSION[self::FLASH_KEY] ) ) ) {
			$_SESSION[self::FLASH_KEY] = array();
		}
		return true;
	}
	
	/**
	 * WordPress nonce support for protecting against XSRF, display an alert if nonce check failed.
	 * @return void
	 */
	final public static function nonceExpiration()
	{
		WordPress::adminNotice( sprintf( '<p>%s</p>', esc_attr__( 'The previous page is expired, please retry.', 'bbmsl-gateway' ) ), self::TYPE_ERROR, true );
	}
	
	/**
	 * Display a message if WooCommerce is not found installed on website.
	 * @return void
	 */
	final public static function requiredWooCommerce()
	{
		WordPress::adminNotice( sprintf( '<p>%s&nbsp;<a href="%s" class="bbmsl-btn sm">%s</a></p>',
			esc_attr__( 'WooCommerce has not been installed!', 'bbmsl-gateway' ),
			admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ),
			esc_attr__( 'Install Now', 'bbmsl-gateway' )
		), self::TYPE_ERROR, true );
	}

	/**
	 * Display a prompt for activating WooCommerce if found installed but not activated.
	 * @return void
	 */
	final public static function activateWooCommerce()
	{
		WordPress::adminNotice( sprintf( '<p>%s&nbsp;<a href="%s" class="bbmsl-btn sm">%s</a></p>',
			esc_attr__( 'WooCommerce needs to be activated before setting up gateway!', 'bbmsl-gateway' ),
			admin_url( 'plugins.php' ),
			esc_attr__( 'Activate Now', 'bbmsl-gateway' )
		), self::TYPE_ERROR, true );
	}
	
	/**
	 * Display a prompt for setting up BBMSL payment gateway.
	 * @return void
	 */
	final public static function requiredSetup()
	{
		if( !BBMSL::ready() ) {
			WordPress::adminNotice( sprintf( '<p>%s&nbsp;<a href="%s" class="bbmsl-btn sm">%s</a></p>',
				esc_attr__( 'Please complete BBMSL Payment Gateway setup by entering merchant ID and generating new keypair.', 'bbmsl-gateway' ),
				Setup::setupLink(),
				esc_attr__( 'Setup Now', 'bbmsl-gateway' )
			), self::TYPE_WARNING, true );
		}
	}
	
	/**
	 * Display follow up guide for merchant to properly compelete the two-way encryption.
	 * @param string $mode
	 * @return void
	 */
	final public static function displayNewKeypairNotice( string $mode = '' ) {
		$portal_link = Constants::TESTING_PORTAL_LINK;
		if( BBMSL::ready() && $gateway = BBMSL::newApiCallInstance() ) {
			$portal_link = $gateway->getPortalLink();
		}
		$info = BBMSL_SDK::getModeInfo( $mode );
		if( isset( $info ) && is_array( $info) && sizeof( $info ) > 0 ) {
			if( isset( $info['portal'] ) && is_string( $info['portal']) ) {
				$ev_portal_link = trim( $info['portal'] );
				if( strlen( $ev_portal_link) > 0 ) {
					$portal_link = $ev_portal_link;
				}
			}
		}
		WordPress::adminNotice( sprintf(
			'<p>%1$s<br />%2$s</p>
			<p><a href="%3$s" target="_blank" rel="noreferrer noopener" class="bbmsl-btn sm">%4$s</a></p>',
			/* translators: %s is the name of the mode for which the keys are genereated */
			sprintf( esc_attr__( 'New %s key pair generated successfully, please update portal information immediately.', 'bbmsl-gateway' ), $mode),
			esc_attr__( 'Transactions will not get authenticated if you do not update portal info now.', 'bbmsl-gateway' ),
			$portal_link,
			esc_attr__( 'Portal Login', 'bbmsl-gateway' )
		), self::TYPE_SUCCESS, true );
	}
	
	/**
	 * Action function to store flash message.
	 * @param string $content
	 * @param string $class
	 * @param bool $dismissible
	 * @param bool $rich_content
	 * @return bool
	 */
	final public static function flash( string $content = '', string $class = 'info', bool $dismissible = true, bool $rich_content = false ) {
		if( self::setupFlashMsgSession() ) {
			$content = trim( $content );
			if( strlen( $content ) > 0 ) {
				if( !$rich_content ) {
					$plaintext = strip_tags( $content );
					if( $content === $plaintext && function_exists( 'wpautop' ) ) {
						$content = wpautop( $content );
					}
				}
				$existing_messages = array_column( $_SESSION[self::FLASH_KEY], 'content' );
				if( !in_array( $content, $existing_messages, true ) ) {
					$_SESSION[self::FLASH_KEY][] = array(
						'content'		=> $content,
						'class'			=> $class,
						'dismissable'	=> $dismissible,
						'rich'			=> $rich_content,
					);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Action function to bulk retrieve all stored flash messages and show on admin as notices.
	 * @return bool
	 */
	final public static function recall()
	{
		self::showErrors();
		if( self::setupFlashMsgSession() ) {
			if( isset( $_SESSION[self::FLASH_KEY] ) && is_array( $_SESSION[self::FLASH_KEY] ) && sizeof( $_SESSION[self::FLASH_KEY] ) > 0 ) {
				foreach( $_SESSION[self::FLASH_KEY] as $k => $message ) {
					if( isset( $message ) && is_array( $message ) && sizeof( $message ) > 0 ) {
						if( isset( $message['content'] ) && is_string( $message['content'] ) ) {
							$message_content = trim( $message['content'] );
						}
						$message_class = self::DEFAULT_FLASH_CLASS;
						if( isset( $message['class'] ) && is_string( $message['class'] ) ) {
							$message_class = strtolower( trim( $message['class'] ) );
						}
						$message_dismissable = true;
						if( isset( $message['dismissable'] ) ) {
							$message_dismissable = boolval( $message_dismissable );
						}
						if( strlen( $message_content ) > 0 ) {
							WordPress::adminNotice( $message_content, $message_class, $message_dismissable );
						}
						unset( $_SESSION[self::FLASH_KEY][ $k ] );
					}
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Display a list of errors for user to adjust their settings accordingly.
	 * @return bool
	 */
	final public static function showErrors()
	{
		if( isset( self::$errors ) && is_array( self::$errors ) && sizeof( self::$errors ) > 0 )  {
			foreach( self::$errors as $flash_class => $notices ) {
				if( isset( $notices ) && is_array( $notices ) )  {
					$notice_count = sizeof( $notices );
					if( $notice_count > 0 ) {
						Notice::flash( 
							wpautop(
								/* translators: %d is the number of errors currently detected */
								sprintf( __( 'We need %d more settings in place to function properly, please correct the following items.', 'bbmsl-gateway' ), sizeof( $notices ) ) . 
								sprintf( '<ul>%s</ul>' , implode( '', array_map( function( $e ) {
									return sprintf( '<li>%s</li>', esc_attr__( $e ) );
								}, $notices ) ) )
							),
							$flash_class,
							true,
							true
						);
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Store an error found during form validation.
	 * @param string $type
	 * @param string $content
	 * @return bool
	 */
	final public static function addError( string $type = '', string $content = '' ) {
		if( !self::isAcceptedTypes( $type ) ) {
			return false;
		}
		$content = trim( $content );
		if( empty( $content ) ) {
			return false;
		}
		if( !( isset( self::$errors[$type] ) && is_array( self::$errors[$type] ) ) ) {
			self::$errors[$type] = array();
		}
		if( !in_array( $content, self::$errors[$type] ) ) {
			self::$errors[$type][] = $content;
		}
		return true;
	}

	/**
	 * Count the number of errors found in form validation.
	 * @return int
	 */
	final public static function countError()
	{
		$sum = 0;
		if( isset( self::$errors ) && is_array( self::$errors ) && sizeof( self::$errors ) > 0 )  {
			foreach( self::$errors as $notices ) {
				if( isset( $notices ) && is_array( $notices ) )  {
					$sum += sizeof( $notices );
				}
			}
		}
		return $sum;
	}

	/**
	 * Boolean check for if errors exists on website.
	 * @return bool
	 */
	final public static function hasError()
	{
		return self::countError() > 0;
	}
}