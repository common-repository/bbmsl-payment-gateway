<?php

/**
 * merchant-settings.php
 *
 * WordPress view file for plugin settings page.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Webhook
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.24
 * @since      File available since initial Release.
 * @deprecated -
 */

use BBMSL\BBMSL as Core;
use BBMSL\Bootstrap\Constants;
use BBMSL\Plugin\Option;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\Utility;
use BBMSL\Plugin\WordPress;

$url_base = plugin_dir_url( BBMSL_PLUGIN_FILE );
$language = ( Constants::LANGUAGE_TC === WordPress::currentLanguage() ? 'tc' : 'en' );

$boolean_switches = [
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Show language tools', 'bbmsl-gateway' ),
		'intro'		=> __( 'Show switch language options for the gateway page.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_language_tools.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Language options on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_LANGUAGE_TOOLS,
	],
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Show Gateway Branding', 'bbmsl-gateway' ),
		'intro'		=> __( 'Show powered by text at the bottom of the gateway checkout page.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_branding.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Gateway branding on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_GATEWAY_BRAND,
	],
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Show Order Details', 'bbmsl-gateway' ),
		'intro'		=> __( 'Show order product listing on the right hand side of the checkout page.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_order_detail.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Order detail listing on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_ORDER_DETAIL,
	],
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Show Email input', 'bbmsl-gateway' ),
		'intro'		=> __( 'Show input field for email entry, used for gateway receipt.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_email_field.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Email field on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_EMAIL,
	],
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Show Merchant Reference', 'bbmsl-gateway' ),
		'intro'		=> __( 'Show the merchant reference.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_merchant_reference.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Merchant reference line on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_MERCHANT_REFERENCE,
	],
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Show Order ID', 'bbmsl-gateway' ),
		'intro'		=> __( 'Show the gateway order ID.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_order_id.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Order ID on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_ORDER_ID,
	],
	[
		'type'		=> 'boolean',
		'heading'	=> __( 'Display Result Page', 'bbmsl-gateway' ),
		'intro'		=> __( 'Before returning to merchant shop, show payment result page for a while.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_result_page_%s.png?v=%s', $url_base, $language, Core::$version ),
		'hint_alt'	=> __( 'Result page after payment processing.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_RESULT_PAGE,
	],
	[
		'type'		=> 'color',
		'heading'	=> __( 'Theme color', 'bbmsl-gateway' ),
		'intro'		=> __( 'The background color of the gateway page can be set to match your brand.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_background_color.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Adjust the background color of the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_CUSTOM_THEME_COLOR,
	],
	[
		'type'		=> 'color',
		'heading'	=> __( 'Button Background Color', 'bbmsl-gateway' ),
		'intro'		=> __( 'The background color of the checkout button on the gateway page can be set to match your brand.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_button_background_color.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Adjust the background color of the checkout button on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_CUSTOM_BUTTON_BG_COLOR,
	],
	[
		'type'		=> 'color',
		'heading'	=> __( 'Button Font Color', 'bbmsl-gateway' ),
		'intro'		=> __( 'The font color of the checkout button on the gateway page can be set to a color that has contrast to your brand for easier reading.', 'bbmsl-gateway' ),
		'hint_img'	=> sprintf( '%s/public/images/instruction/gateway_show_button_font_color.png?v=%s', $url_base, Core::$version ),
		'hint_alt'	=> __( 'Adjust the font color of the checkout button on the gateway page.', 'bbmsl-gateway' ),
		'param'		=> Constants::PARAM_SHOW_CUSTOM_BUTTON_FT_COLOR,
	],
];
?>
<h1 class="heading"><?php echo __( 'Other Settings', 'bbmsl-gateway' ); ?></h1>
<?php foreach( $boolean_switches as $config ){ ?>
	<h3 class="heading"><?php echo esc_html($config['heading']); ?></h3>
	<span class="inline"><?php echo esc_html($config['intro']); ?>
		<div class="hint-box">
			<span class="dashicons dashicons-info"></span>
			<div class="popover">
				<img class="img-fluid w-100" src="<?php echo esc_url($config['hint_img']); ?>" alt="<?php echo esc_attr($config['hint_alt']); ?>" />
			</div>
		</div>
	</span>
	<?php
	$name = sprintf( '%s[payment_settings][%s]', BBMSL::POSTED_KEY, $config['param'] );
	if($config['type'] === 'boolean'){
		$checked = Utility::checkBoolean( Option::get( $config['param'] ) ) ? 'checked' : '';
	?>
	<div class="input-group">
		<div class="input-box">
			<label class="switch">
				<input type="hidden" name="<?php echo esc_attr($name); ?>" value="0" />
				<input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php echo esc_attr($checked); ?> />
				<span class="slider round"></span>
			</label>
		</div>
	</div>
	<?php }else if($config['type'] === 'color'){ ?>
	<div class="input-group">
		<div class="input-box">
			<input type="text" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr( Option::get( $config['param'] ) ); ?>" data-coloris />
			<span class="line"></span>
		</div>
	</div>
	<?php } ?>
<?php } ?>

<div class="spacer" style="min-height:360px;"></div>