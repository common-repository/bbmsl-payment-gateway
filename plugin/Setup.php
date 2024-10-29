<?php

/**
 * Setup.php
 *
 * Contains procedural functions for plguin utilization.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Plugin\Setup
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
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\Log;
use BBMSL\Sdk\SSL;
use BBMSL\Sdk\Utility;
use BBMSL\Plugin\WordPress;
use BBMSL\Plugin\Notice;

class Setup
{
	public const DISPLAY_DATE_FORMAT = Constants::DATETIME_FORMAT;

	/**
	 * Generate plugin setup link.
	 * @return bool|string
	 */
	final public static function setupLink()
	{
		if( function_exists( 'admin_url' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bbmsl' );
		}
		return false;
	}

	/**
	 * Get the official support link.
	 * @return mixed
	 */
	final public static function supportLink()
	{
		return apply_filters( 'bbmsl_support_url', esc_attr__( 'https://bbmsl.com/en/contact-us/', 'bbmsl-gateway' ) );
	}

	/**
	 * Output the rendered view file when called by filename with additional variables.
	 * @param string $view_file
	 * @param array $params
	 * @return mixed
	 */
	final public static function view( string $view_file = '', array $params = array() ) {
		$realpath = realpath( implode( DIRECTORY_SEPARATOR, array( BBMSL_PLUGIN_DIR, 'views', sprintf( '%s.php', $view_file ) ) ) );
		if( Utility::file( $realpath, BBMSL_PLUGIN_DIR ) ) {
			extract( $params, EXTR_SKIP );
			Notice::showErrors();
			return include( $realpath );
		}
	}

	/**
	 * Checks the input merchant ID, add necessary notices to admin.
	 * @param int $number
	 * @param string $name
	 * @return bool
	 */
	private static function checkNumber( $number = 0, string $name = '' ) {
		if( empty( $number ) ) {
			/* translators: %s is the name of the attribute that triggered this error */
			Notice::addError( Notice::TYPE_WARNING, sprintf( esc_attr__( '%s cannot be blank.', 'bbmsl-gateway' ), $name ) );
			return false;
		}

		if( !Utility::isInt( $number) ) {
			/* translators: %s is the name of the attribute that triggered this error */
			Notice::addError( Notice::TYPE_WARNING, sprintf( esc_attr__( '%s must be an integer', 'bbmsl-gateway' ), $name ) );
			return false;
		}

		if( intval( $number ) <= 0 ) {
			/* translators: %s is the name of the attribute that triggered this error */
			Notice::addError( Notice::TYPE_WARNING, sprintf( esc_attr__( '%s must larger than 0.', 'bbmsl-gateway' ), $name ) );
			return false;
		}
		return true;
	}

	/**
	 * Test mode settings basd on current settings, add error notice to admin panel whenever necessary.
	 * @param string $mode
	 * @return bool
	 */
	final public static function testModeSettings( string $mode = '' ) {
		if( !BBMSL_SDK::isModeAccepted( $mode ) ) {
			/* translators: %s is the mode name */
			Notice::addError(
				Notice::TYPE_ERROR,
				sprintf(
					esc_attr__( 'Mode (%s) is not accepted, please try again.', 'bbmsl-gateway' ),
					esc_attr__( $mode )
				)
			);
			return false;
		}

		switch( $mode ) {
			case BBMSL_SDK::MODE_PRODUCTION:
				$merchant_id = intval( Option::get( Constants::PARAM_PRODUCTION_MERCHANT_ID ) );
				self::checkNumber( $merchant_id, esc_attr__( 'Production merchant ID', 'bbmsl-gateway' ) );
	
				if( !(
					SSL::check_str( Option::get( Constants::PARAM_PRODUCTION_PUBLIC_KEY ) ) &&
					SSL::check_str( Option::get( Constants::PARAM_PRODUCTION_PRIVATE_KEY ) ) 
				) ) {
					Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production keypair not ready, please regenerate and upload to BBMSL Portal.', 'bbmsl-gateway') );
				}
				break;
			case BBMSL_SDK::MODE_TESTING:
				$merchant_id = intval( Option::get( Constants::PARAM_TESTING_MERCHANT_ID ) );
				self::checkNumber( $merchant_id, esc_attr__( 'Testing merchant ID', 'bbmsl-gateway' ) );
				
				if( !(
					SSL::check_str( Option::get( Constants::PARAM_TESTING_PUBLIC_KEY ) ) &&
					SSL::check_str( Option::get( Constants::PARAM_TESTING_PRIVATE_KEY ) )
				) ) {
					Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing keypair not ready, please regenerate and upload to BBMSL Portal.', 'bbmsl-gateway' ) );
				}
				break;
		}
		return !!! Notice::hasError();
	}

	private static function handleKeyActions( string $action = '' ) {
		switch( $action ) {
			case Constants::ACTION_REGEN_PRODUCTION_KEYS:
				$keypair = SSL::newKeyPair();
				$public_key = SSL::pem2str( $keypair['public'] );
				$private_key = SSL::pem2str( $keypair['private'] );
				Option::update( Constants::PARAM_PRODUCTION_PUBLIC_KEY, $public_key );
				Option::update( Constants::PARAM_PRODUCTION_PRIVATE_KEY, $private_key );
				Option::update( Constants::PARAM_PRODUCTION_KEY_LAST_UPDATE, date( Constants::DATETIME_FORMAT ) );
				Notice::displayNewKeypairNotice( BBMSL_SDK::MODE_PRODUCTION );
				return true;
			case Constants::ACTION_REGEN_TESTING_KEYS:
				$keypair = SSL::newKeyPair();
				$public_key = SSL::pem2str( $keypair['public'] );
				$private_key = SSL::pem2str( $keypair['private'] );
				Option::update( Constants::PARAM_TESTING_PUBLIC_KEY, $public_key );
				Option::update( Constants::PARAM_TESTING_PRIVATE_KEY, $private_key );
				Option::update( Constants::PARAM_TESTING_KEY_LAST_UPDATE, date( Constants::DATETIME_FORMAT ) );
				Notice::displayNewKeypairNotice( BBMSL_SDK::MODE_TESTING );
				return true;
		}
		Notice::flash( esc_attr__( 'Generated key check failed, please try again.', 'bbmsl-gateway' ), Notice::TYPE_WARNING, true );
		return false;
	}

	private static function handleModeChange( array $settings = array() ) {
		if( array_key_exists( Constants::PARAM_GATEWAY_MODE, $settings ) && is_string( $settings[Constants::PARAM_GATEWAY_MODE] ) ) {
			$mode = strtolower( trim( $settings[Constants::PARAM_GATEWAY_MODE] ) );
			if( empty( $mode ) ) {
				return false;
			}
			if( !BBMSL_SDK::isModeAccepted( $mode ) ) {
				return false;
			}
			if( Option::get( Constants::PARAM_GATEWAY_MODE ) === $mode ) {
				return false;
			}
			switch( $mode ) {
				case BBMSL_SDK::MODE_TESTING:
					if(
						array_key_exists( Constants::PARAM_TESTING_MERCHANT_ID, $settings ) &&
						Utility::isInt( $settings[Constants::PARAM_TESTING_MERCHANT_ID] ) && 
						Option::has( Constants::PARAM_TESTING_PUBLIC_KEY ) && 
						Option::has( Constants::PARAM_TESTING_PRIVATE_KEY )
					) {
						Option::update( Constants::PARAM_GATEWAY_MODE, $mode, true );
						return true;
					}
					Notice::flash( sprintf( 
						esc_attr__( 'Switching to %s failed, please review settings and try again.', 'bbmsl-gateway' ),
						esc_attr__( 'Testing Mode', 'bbmsl-gateway' )
					), Notice::TYPE_WARNING, true );
					break;
				case BBMSL_SDK::MODE_PRODUCTION:
					if(
						array_key_exists( Constants::PARAM_PRODUCTION_MERCHANT_ID, $settings ) &&
						Utility::isInt( $settings[Constants::PARAM_PRODUCTION_MERCHANT_ID] ) && 
						Option::has( Constants::PARAM_PRODUCTION_PUBLIC_KEY ) && 
						Option::has( Constants::PARAM_PRODUCTION_PRIVATE_KEY )
					) {
						Option::update( Constants::PARAM_GATEWAY_MODE, $mode, true );
						return true;
					}
					Notice::flash( sprintf( 
						esc_attr__( 'Switching to %s failed, please review settings and try again.', 'bbmsl-gateway' ),
						esc_attr__( 'Live Site', 'bbmsl-gateway' )
					), Notice::TYPE_WARNING, true );
					break;
			}
		}
		return false;
	}

	/**
	 * Checks and update submitted values by types.
	 * Add admin notice whenever necessary.
	 * @param array $ev_payload
	 * @return bool
	 */
	final public static function updatePaymentGatewaySettings( array $ev_payload = array() ) {
		if( WordPress::currentScreen( 'woocommerce_page_wc-settings' ) ) {
			if( array_key_exists( 'action', $ev_payload ) ) {
				self::handleKeyActions( $ev_payload['action'] );
			}

			if( array_key_exists( 'payment_settings', $ev_payload ) && is_array( $ev_payload['payment_settings'] ) && sizeof( $ev_payload['payment_settings'] ) > 0 ) {
				$ev_settings = $ev_payload['payment_settings'];
				
				self::handleModeChange( $ev_settings );

				$mode = Option::get( Constants::PARAM_GATEWAY_MODE, null );
				switch ( $mode ) {
					case BBMSL_SDK::MODE_PRODUCTION:
						if ( self::checkNumber( $ev_settings[Constants::PARAM_PRODUCTION_MERCHANT_ID], esc_attr__( 'Production merchant ID', 'bbmsl-gateway' ) ) ) {
							Option::update( Constants::PARAM_PRODUCTION_MERCHANT_ID, intval( $ev_settings[Constants::PARAM_PRODUCTION_MERCHANT_ID] ) );
						}
						break;
					case BBMSL_SDK::MODE_LEGACY_SANDBOX:
					case BBMSL_SDK::MODE_TESTING:
						if (self::checkNumber( $ev_settings[Constants::PARAM_TESTING_MERCHANT_ID], esc_attr__( 'Testing merchant ID', 'bbmsl-gateway' ) ) ) {
							Option::update( Constants::PARAM_TESTING_MERCHANT_ID, intval( $ev_settings[Constants::PARAM_TESTING_MERCHANT_ID] ) );
						}
						break;
				}

				$plaintext_parameters = array(
					Constants::PARAM_GATEWAY_DISPLAY_NAME,
					Constants::PARAM_GATEWAY_DISPLAY_NAME_TC,
				);
	
				foreach( $plaintext_parameters as $param ) {
					if( isset( $ev_settings[$param] ) && is_string( $ev_settings[$param] ) ) {
						$value = trim( $ev_settings[$param] );
						Option::update( $param, WordPress::plaintext( $value, false ) );
					}
				}
				
				if( isset( $ev_settings[ Constants::PARAM_GATEWAY_METHODS ] ) && is_array( $ev_settings[ Constants::PARAM_GATEWAY_METHODS ] ) ) {
					$selected = array_intersect( array_values( $ev_settings[ Constants::PARAM_GATEWAY_METHODS ] ), Constants::getPaymentMethods() );
					if( sizeof( $selected ) > 0 ) {
						Option::update( Constants::PARAM_GATEWAY_METHODS, json_encode( $selected ) );
					}else{
						Option::update( Constants::PARAM_GATEWAY_METHODS, '[]' );
					}
				}else{
					Option::update( Constants::PARAM_GATEWAY_METHODS, '[]' );
				}
				
				$richtext_parameters = array(
					Constants::PARAM_GATEWAY_DESCRIPTION,
					Constants::PARAM_GATEWAY_DESCRIPTION_TC,
					Constants::PARAM_GATEWAY_THANK_YOU_PAGE,
					Constants::PARAM_GATEWAY_THANK_YOU_PAGE_TC,
					Constants::PARAM_GATEWAY_EMAIL_CONTENT,
					Constants::PARAM_GATEWAY_EMAIL_CONTENT_TC,
				);
	
				foreach( $richtext_parameters as $param ) {
					if( isset( $ev_settings[$param] ) && is_string( $ev_settings[$param] ) ) {
						$value = trim( $ev_settings[$param] );
						Option::update( $param, WordPress::richtext( $value ) );
					}
				}
	
				$boolean_parameters = array(
					Constants::PARAM_GATEWAY_REFUND,
					Constants::PARAM_EXPRESS_CHECKOUT,
					Constants::PARAM_SHOW_LANGUAGE_TOOLS,
					Constants::PARAM_SHOW_GATEWAY_BRAND,
					Constants::PARAM_SHOW_ORDER_DETAIL,
					Constants::PARAM_SHOW_EMAIL,
					Constants::PARAM_SHOW_MERCHANT_REFERENCE,
					Constants::PARAM_SHOW_ORDER_ID,
					Constants::PARAM_SHOW_RESULT_PAGE,
				);

				foreach( $boolean_parameters as $param ) {
					if( isset( $ev_settings[$param] ) && is_string( $ev_settings[$param] ) ) {
						$boolean = intval( $ev_settings[$param] );
						Option::update( $param, ( $boolean > 0 ? 'true' : 'false' ) );
					}
				}
	
				$status_parameters = array(
					Constants::PARAM_ORDER_STATUS_ON_CREATE,
					// Constants::PARAM_ORDER_STATUS_ON_SUCCESS,
					// Constants::PARAM_ORDER_STATUS_ON_FAILED,
					Constants::PARAM_ORDER_STATUS_ON_PAID,
					// Constants::PARAM_ORDER_STATUS_ON_VOIDED,
					// Constants::PARAM_ORDER_STATUS_ON_REFUNDED,
				);

				foreach( $status_parameters as $param ) {
					if( isset( $ev_settings[$param] ) && is_string( $ev_settings[$param] ) ) {
						$value = trim( $ev_settings[$param] );
						if( !empty( $value ) && WordPress::isWooCommerceOrderStatus( $value ) ) {
							Option::update( $param, WordPress::plaintext( $value ) );
						}
					}
				}

				$color_parameters = array(
					Constants::PARAM_SHOW_CUSTOM_THEME_COLOR,
					Constants::PARAM_SHOW_CUSTOM_BUTTON_BG_COLOR,
					Constants::PARAM_SHOW_CUSTOM_BUTTON_FT_COLOR,
				);

				foreach( $color_parameters as $param ) {
					if( isset( $ev_settings[$param] ) && is_string( $ev_settings[$param] ) ) {
						$value = trim( $ev_settings[$param] );
						if( empty( $value ) ) { 
							Option::remove( $param );
						} else if( Utility::isColor( $value ) ) {
							Option::update( $param, WordPress::plaintext( $value ) );
						}
					}
				}

				
				if( isset( $ev_settings[ Constants::PARAM_WC_ORDER_COLUMNS ] ) && is_array( $ev_settings[ Constants::PARAM_WC_ORDER_COLUMNS ] ) ) {
					$selected = array_intersect( array_values( $ev_settings[ Constants::PARAM_WC_ORDER_COLUMNS ] ), array_keys( Constants::getWooCommerceColumns() ) );
					if( sizeof( $selected ) > 0 ) {
						Option::update( Constants::PARAM_WC_ORDER_COLUMNS, json_encode( $selected ) );
					}else{
						Option::update( Constants::PARAM_WC_ORDER_COLUMNS, '[]' );
					}
				}else{
					Option::update( Constants::PARAM_WC_ORDER_COLUMNS, '[]' );
				}

			}
			Notice::recall();
			return true;
		}
		return false;
	}
	
	final public static function showMethodLogosHTML()
	{
		$image_html = array();
		foreach( BBMSL::getCoeasedMethods() as $key => $method) {
			if( BBMSL::hasSelectedMethod( $key ) ) {
				$image_html[] = sprintf( '<img class="logo" src="%s%s" />', plugin_dir_url( BBMSL_PLUGIN_FILE ), $method['logo'] );
			}
		}
		return sprintf( '<div class="bbmsl_payment_methods">%s</div>', implode( $image_html ) );
	}

	final public static function setupPluginActionLinks( array $links = array(), string $plugin_file = '' ) {
		$action_links = array();
		if( $settings_link = static::setupLink() ) {
			$action_links['settings'] = sprintf( '<a href="%s" aria-label="%s">%s</a>', 
				$settings_link,
				esc_attr__( 'View BBMSL settings', 'bbmsl-gateway' ),
				esc_html__( 'Settings', 'bbmsl-gateway' )
			);
		}
		return array_merge( $action_links, $links );
	}

	final public static function setupPluginMeta( array $links = array(), string $file = '' ) {
		if( 0 === stripos( $file, 'bbmsl' ) ) {
			$row_meta = array(
				'apidocs' => sprintf( '<a target="_blank" rel="noreferrer noopener" href="%s" aria-label="%s">%s</a>',
					esc_url( apply_filters( 'bbmsl_apidocs_url', __( 'https://docs.bbmsl.com/', 'bbmsl-gateway' ) ) ),
					esc_attr__( 'View BBMSL API docs', 'bbmsl-gateway' ),
					esc_html__( 'API docs', 'bbmsl-gateway' )
				),
				'support' => sprintf( '<a target="_blank" rel="noreferrer noopener" href="%s" aria-label="%s">%s</a>',
					esc_url( static::supportLink() ),
					esc_attr__( 'Contact Support', 'bbmsl-gateway' ),
					esc_html__( 'Contact Support', 'bbmsl-gateway' )
				),
			);
			return array_merge( $links, $row_meta );
		}
		return $links;
	}

	final public static function setupRefundHandling( $refund ) {
		$ev_posted = array_intersect_key( $_POST, array(
			'action'					=> null,
			'order_id'					=> null,
			'refund_amount'				=> null,
			'refunded_amount'			=> null,
			'refund_reason'				=> null,
			'line_item_qtys'			=> null,
			'line_item_totals'			=> null,
			'line_item_tax_totals'		=> null,
			'api_refund'				=> null,
			'restock_refunded_items'	=> null,
			'security'					=> null,
		) );
		if( isset( $ev_posted ) && is_array( $ev_posted ) && sizeof( $ev_posted ) > 0 ) {
			if( isset( $ev_posted['action'] ) && is_string( $ev_posted['action'] ) ) {
				$action = strtolower( trim( $ev_posted['action'] ) );
				if( 'woocommerce_refund_line_items' != $action ) {
					return;
				}
			}
			
			if( isset( $ev_posted['api_refund'] ) && is_string( $ev_posted['api_refund'] ) ) {
				$api_refund = strtolower( trim( $ev_posted['api_refund'] ) );
				if( 'true' != $api_refund ) {
					return;
				}
			}
			
			if( isset( $ev_posted['order_id'] ) && is_numeric( $ev_posted['order_id'] ) ) {
				$order_id = intval( $ev_posted['order_id'] );
				$order = BBMSL::getOrder( $order_id.'' );
				if( method_exists( $order, 'get_payment_method' ) && Constants::GATEWAY_ID === $order->get_payment_method() ) {	
					if( method_exists( $order, 'get_total_refunded' ) && $order->get_total_refunded() > 0 ) {
						$metadata	= BBMSL::getOrderMetaByID( $order_id );
					}
					if( isset( $metadata ) && is_array( $metadata ) && sizeof( $metadata ) > 0 ) {
						$merchant_reference = BBMSL::getMerchantReference( $metadata );
						if( empty( $merchant_reference ) ) {
							Notice::flash( esc_attr__( 'Failed to obtain order metadata.', 'bbmsl-gateway' ), Notice::TYPE_ERROR, true );
						}
						if( $gateway = BBMSL::newApiCallInstance() ) {
							$query_result = $gateway->queryOrder( $merchant_reference );
							update_post_meta( $order_id, Constants::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
							if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
								if( isset( $query_result['message'] ) && is_string( $query_result['message'] ) ) {
									$api_message = strtolower( trim( $query_result['message'] ) );
									if( ! empty( $api_message ) ) {
										if( 'success' == $api_message) {
											if( isset( $query_result['order'] ) && is_array( $query_result['order'] ) && sizeof( $query_result['order'] ) > 0 ) {
												$order_info = $query_result['order'];
												if( isset( $order_info['status'] ) ) {
													$order_status = strtoupper( trim( $order_info['status'] ) );
												}
											}
										}
									}
								}
							}
						}
						if( !BBMSL::statusRefundable( $order_status ) ) {
							if( BBMSL::statusVoidable( $order_status ) ) {
								Notice::flash( esc_attr__( 'error_refund_unsettled_order', 'bbmsl_gateway' ), Notice::TYPE_ERROR, true );
							}
							return false;
						}
						Notice::flash( esc_attr__( 'This order has already been refunded before.', 'bbmsl-gateway' ), Notice::TYPE_ERROR, true );
						return false;
					}
				}
			}
		}
		return true;
	}

	final public static function setupVoidHandling( int $order_id = 0 ) {
		if( !WordPress::currentScreen( 'shop_order' ) ) {
			return false;
		}

		$params = BBMSL::getRawPostedPayloads();

		// check if there are any posted parameters.
		if( !( isset( $params ) && is_array( $params ) && sizeof( $params ) > 0 ) ) {
			return;
		}

		// check if a bbmsl_action is specified.
		if( isset( $params['bbmsl_action'] ) && is_string( $params['bbmsl_action'] ) ) {
			$bbmsl_action = strtolower( trim( $params['bbmsl_action'] ) );
			if( 'void_order' !== $bbmsl_action ) {
				return;
			}
		}else{
			return;
		}

		// evade woocommerce normal order update operations.
		if( isset( $_POST['save'] ) && is_string( $_POST['save'] ) ) {
			$default_submit = strtolower( trim( $_POST['save'] ) );
			if( 'update' === $default_submit ) {
				return;
			}
		}

		// actually do the saving

		// get order reference
		if( !( isset( $params ) && is_array( $params ) && sizeof( $params ) > 0 ) ) {return false;}
		if( empty( $params['order_reference'] ) ) { return false; }
		if( !is_string( $params['order_reference'] . '' ) ) { return false; }

		// get order from order reference
		$merchant_reference = trim( $params['order_reference'] );
		$order = BBMSL::getOrderByMerchantReference( $params['order_reference'] );
		$order_id = $order->get_id();

		// query online order status
		$gateway = BBMSL::newApiCallInstance();
		if( !$gateway ){
			Notice::flash( esc_attr__( 'Cannot create new API call instance.', 'bbmsl-gateway' ), Notice::TYPE_ERROR, true );
		}
		
		$query_result = $gateway->queryOrder( $merchant_reference );
		update_post_meta( $order_id, Constants::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
		
		// check existing order status
		if( !( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) ) { return false; }
		if( empty( $query_result['message'] ) ) { return false; }
		if( !is_scalar( $query_result['message'] ) ) { return false; }
		if( !( isset( $query_result['order'] ) && is_array( $query_result['order'] ) && sizeof( $query_result['order'] ) > 0 ) ) { return false; }
		
		$api_message = strtolower( trim( $query_result['message'] ) );
		$order_info = $query_result['order'];
		if( 'success' === $api_message && !empty( $order_info['status'] ) ) {
			$order_status = strtoupper( trim( $order_info['status'] ) );
		}else{
			Notice::flash( esc_attr__( 'API call failed.', 'bbmsl-gateway' ) . PHP_EOL . print_r( $query_result ), Notice::TYPE_ERROR, true );
		}
		
		// check if current order is voidable
		if( empty( $order_status ) ) { return false; }
		if( 'VOIDED' !== $order_status ) {
			if( !BBMSL::statusVoidable( $order_status ) ) {
				return Notice::flash( sprintf( esc_attr__( 'Order status: %s cannot be voided! Use refund function instead.', 'bbmsl-gateway'), $order_status ), Notice::TYPE_ERROR, true );
			}

			// get bbmsl order id
			if( empty( $query_result['order']['id'] ) ) { return false; }
			$bbmsl_order_id = strval( $query_result['order']['id'] );
			$query_result = $gateway->voidOrder( $bbmsl_order_id, $merchant_reference );
			if( function_exists( 'update_post_meta' ) ) {
				update_post_meta( $order_id, Constants::META_LAST_VOID, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
			}
		}

		// ON VOID
		$target_void_status = Option::get( Constants::PARAM_ORDER_STATUS_ON_VOIDED );
		BBMSL::updateOrderStatus( $order, $target_void_status );
		$_POST['order_status'] = $target_void_status;
		$_POST['post_status'] = $target_void_status;
		return true;
	}
	
	final public static function setupThankYouPage( $order_id ) {
		// ON SUCCESS
		$order = BBMSL::getOrder( $order_id );
		if( !( $order instanceof \WC_Order ) ) {
			update_post_meta( $order_id, Constants::META_THANK_YOU_PAGED, 1 );
		}
		$value = Option::get( Constants::PARAM_GATEWAY_THANK_YOU_PAGE, '', true );
		if( WordPress::currentLanguage() == Constants::LANGUAGE_TC ) {
			$value = Option::get( Constants::PARAM_GATEWAY_THANK_YOU_PAGE_TC, $value, true );
		}
		echo $value;
		return true;
	}

	final public static function attachToMenu()
	{
		global $menu;
		$index = 55.4;

		if( isset( $menu )  && is_array( $menu ) && sizeof( $menu ) > 0 ) {
			foreach( $menu as $k => $row ){
				if( 'woocommerce' === $row[ 2 ] ) {
					$index = floatval( $k ) - 0.1;
				}
			}
		}
		add_menu_page(
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
            sprintf('%s<div class="status %s"></div>', esc_html__( 'Settings', 'bbmsl-gateway' ), ( BBMSL::ready() ? 'ready' : 'pending' ) ),
            'manage_woocommerce',
            'bbmsl-settings',
			function()
			{
				wp_redirect( self::setupLink() );
				exit();
			},
            BBMSL::getLogoURL(),
            $index
		);
	}

	final public static function attachAdminPages()
	{
		add_submenu_page(
			'',
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
			'administrator',
			'bbmsl-settings-server-info',
			function()
			{
				ob_end_clean();
				phpinfo();
				exit();
			},
			null
		);
		add_submenu_page(
			'',
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
			'administrator',
			'bbmsl-settings-download-logs',
			function()
			{
				ob_end_clean();
				$logging_file = Log::getExpectedLogFile('webhook');
				$content = '[]';
				if( file_exists( $logging_file ) && !is_dir( $logging_file ) && filesize( $logging_file ) > 0){
					$content = file_get_contents( $logging_file );
					$content = sprintf( '[%s]', trim( $content, "[],\r\n" ) );
				}
				header( 'Content-Description: BBMSL Payment Logs' );
				header( 'Content-Type: application/json' );
				header( "Cache-Control: no-cache, must-revalidate" );
				header( "Expires: 0" );
				header( sprintf( 'Content-Disposition: attachment; filename="BBMSL Payment Logs.%s.json"', date( 'YmdHis' ) ) );
				header( sprintf( 'Content-Length: %d', strlen( $content ) ) );
				header( 'Pragma: public' );
				echo $content;
				exit();
			},
			null
		);
		add_submenu_page(
			'',
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
			esc_html__( 'BBMSL Settings', 'bbmsl-gateway' ),
			'administrator',
			'bbmsl-settings-reset-settings',
			function()
			{
				global $wpdb;
				$wpdb->query( "SELECT * FROM {$wpdb->prefix}options WHERE `option_name` LIKE \"%bbmsl_%\" AND `option_name` NOT LIKE \"%_key\"" );
				Notice::flash( esc_attr__( 'Reset settings to default values success. (Excluding keys)', 'bbmsl-gateway' ), Notice::TYPE_SUCCESS, true );
				wp_redirect( self::setupLink() );
				exit();
			},
			null
		);
	}
}