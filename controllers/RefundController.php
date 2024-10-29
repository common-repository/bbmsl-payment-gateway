<?php

/**
 * RefundController.php
 *
 * MVC controller concept for handling refund ajax requests
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Controller\RefundController
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.23
 * @since      1.0.18
 * @deprecated -
 * @todo Migrate all refund procedures to this controller in future updates.
 */

declare( strict_types = 1 );
namespace BBMSL\Controllers;

use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Option;
use BBMSL\Sdk\BBMSL;
use WC_Order_Refund;

class RefundController
{
	/**
	 * Intercept WooCommerce refund requests.
	 * @param \WC_Order_Refund $refund
	 * @return void
	 */
	final public static function handleWpAjaxWooCommerceRefundRequest( WC_Order_Refund $refund ):void
	{
		if( !method_exists( $refund, 'get_parent_id' ) ) {
			throw new \Exception( esc_attr__( 'Cannot retrieve order ID in refund request.' ) );
		}
		$order_id = $refund->get_parent_id();
		$order = BBMSL::getOrder( $order_id );
		if( !method_exists( $order, 'get_payment_method' ) ) {
			throw new \Exception( esc_attr__( 'Cannot determine order payment method.' ) );
		}
		if( Constants::GATEWAY_ID !== $order->get_payment_method() ) {
			return;
		}

		$status = BBMSL::getOrderOnlineStatus( $order_id );
		if( !BBMSL::statusRefundable( $status ) ) {
			throw new \Exception( esc_attr__( 'Cancelled order does not qualify for refunds.', 'bbmsl-gateway' ) );
		}
		if( $order->get_total() < $refund->get_amount() ) {
			throw new \Exception( esc_attr__( 'Refund amount cannot be larger than the order total.', 'bbmsl-gateway' ) );
		}

		if( !BBMSL::ready() ) {
			throw new \Exception( esc_attr__( 'Merchant settings not ready.', 'bbmsl-gateway' ) );
		}

		$merchant_reference = BBMSL::getMerchantReferenceByID( $order_id );
		if( empty( $merchant_reference ) ) {
			throw new \Exception( esc_attr__( 'Failed to get order instance.', 'bbmsl-gateway' ) );
		}

		$gateway = BBMSL::newApiCallInstance();
		if( !$gateway ) {
			throw new \Exception( esc_attr__( 'Cannot obtain API instance.', 'bbmsl-gateway' ) );
		}

		$metadata = $gateway->queryOrder( $merchant_reference );
		if( isset( $metadata ) && is_array( $metadata ) && sizeof( $metadata ) > 0 ) {
			if( array_key_exists( 'responseCode', $metadata ) && '0000' === $metadata['responseCode'] ) {
				if( array_key_exists( 'order', $metadata ) && array_key_exists( 'id', $metadata['order'] ) ) {
					$bbmsl_order_id = $metadata['order']['id'];
				}
			} else {
				if( isset( $metadata['message'] ) ) {
					$api_message = sprintf( '[API] %s', wp_strip_all_tags( $metadata['message'] ) );
					throw new \Exception( $api_message );
				}
			}
		}

		if( empty( $bbmsl_order_id ) ) {
			throw new \Exception( esc_attr__( 'Failed to get order instance.', 'bbmsl-gateway' ) );
		}

		$amount = floatval( $refund->get_amount() );
		$bbmsl_order_id = strval( $bbmsl_order_id );

		$query_result = $gateway->refundOrder( $bbmsl_order_id, $merchant_reference, $amount );
		if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
			if( isset( $query_result['message'] ) ) {
				$api_message = sprintf( '[API] %s', wp_strip_all_tags( $query_result['message'] ) );
				throw new \Exception( $api_message );
			}
			
			// ON REFUND
			$order = BBMSL::getOrder( $order_id );
			BBMSL::updateOrderStatus( $order, Option::get( Constants::PARAM_ORDER_STATUS_ON_REFUNDED ) );
			if( function_exists( 'add_post_meta' ) ) {
				add_post_meta( $order_id, Constants::META_LAST_WEBHOOK, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
			}
		}
	}
}