<?php

use BBMSL\Bootstrap\Constants;
use BBMSL\Sdk\BBMSL;
use BBMSL\Plugin\Option;
use BBMSL\Plugin\WordPress;

$wc_order_status = WordPress::getWooCommerceOrderStatuses();
$boolean_switches = [
	[
		'type'		=> 'select',
		'heading'	=> __( 'Order Status on Create', 'bbmsl-gateway' ),
		'intro'		=> __( 'The initial order status when the order is first created.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_ORDER_STATUS_ON_CREATE,
		'options'	=> $wc_order_status,
	],
	// [
	// 	'type'		=> 'select',
	// 	'heading'	=> __( 'Order Status on Success', 'bbmsl-gateway' ),
	// 	'intro'		=> __( 'Change order status when the order has been checked out but not yet confirmed by gateway.', 'bbmsl-gateway' ),
	// 	'param'		=> Constants::PARAM_ORDER_STATUS_ON_SUCCESS,
	// 	'options'	=> $wc_order_status,
	// ],
	// [
	// 	'type'		=> 'select',
	// 	'heading'	=> __( 'Order Status on Failed', 'bbmsl-gateway' ),
	// 	'intro'		=> __( 'Change order status when the order has failed.', 'bbmsl-gateway' ),
	// 	'param'		=> Constants::PARAM_ORDER_STATUS_ON_FAILED,
	// 	'options'	=> $wc_order_status,
	// ],
	[
		'type'		=> 'select',
		'heading'	=> __( 'Order Status on Paid', 'bbmsl-gateway' ),
		'intro'		=> __( 'Change order status when payment has been confirmed by the gateway.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_ORDER_STATUS_ON_PAID,
		'options'	=> $wc_order_status,
	],
	// [
	// 	'type'		=> 'select',
	// 	'heading'	=> __( 'Order Status on Void', 'bbmsl-gateway' ),
	// 	'intro'		=> __( 'Change order status when the store receives a "Voided" signal.', 'bbmsl-gateway' ),
	// 	'param'		=> Constants::PARAM_ORDER_STATUS_ON_VOIDED,
	// 	'options'	=> $wc_order_status,
	// ],
	// [
	// 	'type'		=> 'select',
	// 	'heading'	=> __( 'Order Status on Refund', 'bbmsl-gateway' ),
	// 	'intro'		=> __( 'Change order status when the store receives a "Refunded" signal.', 'bbmsl-gateway' ),
	// 	'param'		=> Constants::PARAM_ORDER_STATUS_ON_REFUNDED,
	// 	'options'	=> $wc_order_status,
	// ],
];
?>
<h1 class="heading"><?php echo __( 'Operational Settings', 'bbmsl-gateway' ); ?></h1>
<?php foreach( $boolean_switches as $config ){ ?>
	<h3 class="heading"><?php echo esc_html($config['heading']); ?></h3>
	<span class="inline"><?php echo esc_html($config['intro']); ?></span>
	<?php
	$name = sprintf( '%s[payment_settings][%s]', BBMSL::POSTED_KEY, $config['param'] );
	if($config['type'] === 'select'){
		$current = Option::get( $config['param'] );
	?>
	<div class="input-group">
		<div class="input-box">
			<select name="<?php echo esc_attr($name); ?>">
				<?php foreach($config['options'] as $slug => $option){ 
					$selected = ($slug === $current) ? 'selected' : '';
				?>
				<option value="<?php echo esc_attr($slug); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($option); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
	<?php } ?>
<?php } ?>

<div class="spacer" style="min-height:360px;"></div>