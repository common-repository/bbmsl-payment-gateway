<?php

/**
 * woocommerce_payments_settings.php
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

use BBMSL\Sdk\BBMSL;
use BBMSL\Plugin\WordPress;

$setting_panes = array(
	array(
		'id'	=> 'merchant-settings',
		'name'	=> __( 'Merchant Settings', 'bbmsl-gateway' ),
		'pane'	=> implode(DIRECTORY_SEPARATOR, array( __DIR__, 'panes', 'merchant-settings.php') ),
	),
	array(
		'id'	=> 'content-settings',
		'name'	=> __( 'Content Settings', 'bbmsl-gateway' ),
		'pane'	=> implode(DIRECTORY_SEPARATOR, array( __DIR__, 'panes', 'content-settings.php') ),
	),
	array(
		'id'	=> 'operational-settings',
		'name'	=> __( 'Operational Settings', 'bbmsl-gateway' ),
		'pane'	=> implode(DIRECTORY_SEPARATOR, array( __DIR__, 'panes', 'operational-settings.php') ),
	),
	array(
		'id'	=> 'other-settings',
		'name'	=> __( 'Other Settings', 'bbmsl-gateway' ),
		'pane'	=> implode(DIRECTORY_SEPARATOR, array( __DIR__, 'panes', 'other-settings.php') ),
	),
	array(
		'id'	=> 'woocommerce-settings',
		'name'	=> __( 'WooCommerce Settings', 'bbmsl-gateway' ),
		'pane'	=> implode(DIRECTORY_SEPARATOR, array( __DIR__, 'panes', 'woocommerce-settings.php') ),
	),
	array(
		'id'	=> 'advanced-settings',
		'name'	=> __( 'Advanced Settings', 'bbmsl-gateway' ),
		'pane'	=> implode(DIRECTORY_SEPARATOR, array( __DIR__, 'panes', 'advanced-settings.php') ),
	),
);

?>
<div class="woocommmerce-settings bbmsl-settings bbmsl-bg">
	<?php wp_nonce_field( 'bbmsl-plugin', BBMSL::NONCE_KEY ); ?>
	<input type="hidden" name="ui_last_pane_state" value="merchant-settings" readonly />
	<input type="hidden" name="wp_language" value="<?php echo WordPress::currentLanguage(); ?>" id="wp_language" readonly />
	<input type="checkbox" id="mobile-menu" />
	<?php foreach ( $setting_panes as $k => $pane ) { ?>
	<input type="radio" name="ui[pane]" id="<?php echo esc_attr( $pane['id'] ); ?>" <?php echo ($k === 0?'checked':''); ?> />
	<?php } ?>
	<button type="submit" name="save" value="<?php echo esc_attr__( 'Save changes', 'bbmsl-gateway' ); ?>" class="default-failover" id="save"></button>
	<div class="header">
		<img class="bbmsl-logo" src="<?php echo BBMSL::getLogoURL(); ?>" width="135" height="36" />
		<label for="mobile-menu" class="btn-mobile-menu">
			<span class="dashicons dashicons-menu"></span>
		</label>
	</div>
	<div class="body">
		<div class="settings">
			<menu>
				<?php foreach ( $setting_panes as $k => $pane ) { ?>
				<label class="menu-item" for="<?php echo esc_attr( $pane['id'] ); ?>">
					<span class="dashicons <?php echo esc_attr( isset( $pane['icon'] ) ? $pane['icon'] : 'dashicons-admin-generic' ); ?>"></span>
					<span class="menu-item-name"><?php echo esc_attr( $pane['name'] ); ?></span>
				</label>
				<?php } ?>
			</menu>
			<?php
			foreach( $setting_panes as $k => $pane ) { ?>
			<div class="pane" data-name="<?php echo esc_attr( $pane['id'] ); ?>">
				<?php include_once( $pane['pane'] ); ?>
			</div>
			<?php } ?>
		</div>
	</div>
</div>