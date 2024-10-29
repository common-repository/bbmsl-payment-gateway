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
use BBMSL\Plugin\Setup;
use BBMSL\Plugin\WordPress;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\SSL;
use BBMSL\Sdk\Utility;

$reference_endpoint = BBMSL::newApiCallInstance();
$testing_mode = boolval( $reference_endpoint && Utility::checkBoolean( $reference_endpoint->getModeCode() == BBMSL_SDK::MODE_TESTING) );
$url_base = plugin_dir_url( BBMSL_PLUGIN_FILE );
$language = ( Constants::LANGUAGE_TC === WordPress::currentLanguage() ? 'tc' : 'en' );

?>

<div class="display-box"><?php echo __( 'Remember to save change for update new setting.', 'bbmsl-gateway' ); ?></div>
<h1 class="heading"><?php echo __( 'Merchant Settings', 'bbmsl-gateway' ); ?></h1>
<div class="input-group">
	<h2 class="heading"><?php echo __( 'Testing Mode', 'bbmsl-gateway' ); ?></h2>
	<div class="input-box">
		<label class="switch">
			<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_MODE; ?>]" value="<?php echo BBMSL_SDK::MODE_PRODUCTION; ?>" />
			<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_MODE; ?>]" value="<?php echo BBMSL_SDK::MODE_TESTING; ?>" <?php echo( $testing_mode?'checked':'' ); ?> id="toggle_site_checkbox" />
			<span class="slider round"></span>
		</label>
		<br />
		<p><?php echo __( 'This mode allows paper ordering without making real transactions, used during integration and development.', 'bbmsl-gateway' ); ?></p>
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td class="pe-2"><?php echo __( 'Current mode: ', 'bbmsl-gateway' ); ?></td>
				<td><span style="color:#00F;"><?php echo esc_html( $reference_endpoint->getModeName() ); ?></span></td>
			</tr>
			<tr>
				<td class="pe-2"><?php echo __( 'Current endpoint: ', 'bbmsl-gateway' ); ?></td>
				<td><span style="color:#00F;"><?php echo esc_html( $reference_endpoint->getEndpoint() ); ?></span></td>
			</tr>
		</table>
	</div>
</div>

<h3 class="heading"><?php echo __( 'Signature Verification', 'bbmsl-gateway' ); ?></h3>
<p>
	<?php echo __( 'To verify request coming from your online store, you\'ll need to upload your public key to BBMSL Portal or transactions will not be authenticated.', 'bbmsl-gateway' ); ?>
</p>
<div class="steps">
	<div class="step">
		<p style="font-size:24px;font-weight:500;"><?php echo sprintf(
			__( '1. On %s', 'bbmsl-gateway' ),
			__( 'WordPress', 'bbmsl-gateway' )
		); ?></p>
		<?php echo sprintf(
			/* translators: %1$s, %2$s, %3$s are text labels for "generate", "copy", "portal login" buttons. */
			__( 'Please click the \'%1$s\' button below to receive a new key, then you can click \'%2$s\' button to copy the new public key and click \'%3$s\' button go to BBMSL portal for the next step.', 'bbmsl-gateway' ), 
			__( 'Generate', 'bbmsl-gateway' ),
			__( 'Copy', 'bbmsl-gateway' ),
			__( 'Portal Login', 'bbmsl-gateway' )
		); ?>
	</div>
	<div class="step">
		<p style="font-size:24px;font-weight:500;"><?php echo __( '2. On BBMSL Portal', 'bbmsl-gateway' ); ?></p>
		<?php echo __( 'Please enter the "Account Center" by clicking your account name at top right corner. Then select "Public Key" from the menu and click "Add Public Key" button to insert the newly copied public key from wordpress. Finally, click "Active" to make it effective.', 'bbmsl-gateway' ); ?>
		<img class="gen_key_img" src="<?php echo sprintf( '%s/public/images/instruction/portal_generate_key_%s.png?v=%s', $url_base, $language, Core::$version ); ?>" />
	</div>
</div>

<h3 class="heading"><?php echo __( 'Public Key', 'bbmsl-gateway' ); ?></h3>
<div class="steps">
	<div class="toggle_site input-group <?php echo( $testing_mode?'disabled':'' ); ?>" id="toggle_site_live">
		<div class="label prime">
			<?php echo __( 'Live Site', 'bbmsl-gateway' ); ?>
		</div>
		<div class="label">
			<?php echo __( 'Merchant ID', 'bbmsl-gateway' ); ?>
		</div>
		<div class="input-box">
			<input type="number" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_PRODUCTION_MERCHANT_ID; ?>]" maxlength="4" min="0" step="1" placeholder="0000" value="<?php echo Option::get( Constants::PARAM_PRODUCTION_MERCHANT_ID ); ?>"/>
			<span class="line"></span>
		</div>
		<div class="input-box">
			<textarea class="monospace" spellcheck="false" id="field_production_public_key" readonly><?php echo SSL::pem2str( Option::get( Constants::PARAM_PRODUCTION_PUBLIC_KEY, '' ) ); ?></textarea>
			<span class="line"></span>
		</div>
		<p>
			<?php
			$last_update_timestamp = __( 'Never', 'bbmsl-gateway' );
			$last_update = Option::get( Constants::PARAM_PRODUCTION_KEY_LAST_UPDATE );
			if( !empty( $last_update ) ) {
				$last_update_timestamp = $last_update;
			}
			/* translators: %s is the timestamp string for last key generation */
			echo sprintf( __( 'Last key generation: %s', 'bbmsl-gateway' ), esc_attr( $last_update_timestamp ) );
			?>
		</p>
		<p>
			<button type="submit" class="bbmsl-btn" name="<?php echo BBMSL::POSTED_KEY; ?>[action]" value="<?php echo Constants::ACTION_REGEN_PRODUCTION_KEYS; ?>">
				<?php echo __( 'Generate', 'bbmsl-gateway' ); ?>
			</button>
			<button type="button" class="bbmsl-btn" data-copy-source="field_production_public_key">
				<?php echo __( 'Copy', 'bbmsl-gateway' ); ?>
			</button>
			<a class="bbmsl-btn" href="<?php echo Constants::PRODUCTION_PORTAL_LINK; ?>" target="_blank" rel="noreferrer noopener">
				<?php echo __( 'Portal Login', 'bbmsl-gateway' ); ?>
			</a>
		</p>
	</div>
	<div class="toggle_site input-group <?php echo( $testing_mode?'':'disabled' ); ?>" id="toggle_site_testing">
		<div class="label prime">
			<?php echo __( 'Testing Site', 'bbmsl-gateway' ); ?>
		</div>
		<div class="label">
			<?php echo __( 'Merchant ID', 'bbmsl-gateway' ); ?>
		</div>
		<div class="input-box">
			<input type="number" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_TESTING_MERCHANT_ID; ?>]" maxlength="4" min="0" step="1" placeholder="0000" value="<?php echo Option::get( Constants::PARAM_TESTING_MERCHANT_ID ); ?>"/>
			<span class="line"></span>
		</div>
		<div class="input-box">
			<textarea class="monospace" spellcheck="false" id="field_testing_public_key" readonly><?php echo SSL::pem2str( Option::get( Constants::PARAM_TESTING_PUBLIC_KEY, '' ) ); ?></textarea>
			<span class="line"></span>
		</div>
		<p>
			<?php
			$last_update_timestamp = __( 'Never', 'bbmsl-gateway' );
			$last_update = Option::get( Constants::PARAM_TESTING_KEY_LAST_UPDATE );
			if( !empty( $last_update ) ) {
				$last_update_timestamp = $last_update;
			}
			/* translators: %s is the timestamp string for last key generation */
			echo sprintf( __( 'Last key generation: %s', 'bbmsl-gateway' ), esc_attr( $last_update_timestamp ) );
			?>
		</p>
		<p>
			<button type="submit" class="bbmsl-btn" name="<?php echo BBMSL::POSTED_KEY; ?>[action]" value="<?php echo Constants::ACTION_REGEN_TESTING_KEYS; ?>">
				<?php echo __( 'Generate', 'bbmsl-gateway' ); ?>
			</button>
			<button type="button" class="bbmsl-btn" data-copy-source="field_testing_public_key">
					<?php echo __( 'Copy', 'bbmsl-gateway' ); ?>
			</button>
			<a class="bbmsl-btn" href="<?php echo Constants::TESTING_PORTAL_LINK; ?>" target="_blank" rel="noreferrer noopener">
				<?php echo __( 'Portal Login', 'bbmsl-gateway' ); ?>
			</a>
		</p>
	</div>
</div>

<hr />

<h3 class="heading"><?php echo __( 'Refund Settings', 'bbmsl-gateway' ); ?></h3>
<span class="inline"><?php echo __( 'Enable refund via this gateway.', 'bbmsl-gateway' ); ?>
	<div class="hint-box">
		<span class="dashicons dashicons-info"></span>
		<div class="popover">
			<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/admin_refund_function_%s.png?v=%s', $url_base, $language, Core::$version ); ?>" />
		</div>
	</div>
</span>
<div class="input-group">
	<div class="input-box">
		<label class="switch">
			<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_REFUND; ?>]" value="0" />
			<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_REFUND; ?>]" value="1" <?php echo( Utility::checkBoolean( Option::get( Constants::PARAM_GATEWAY_REFUND, 1) )?'checked':'' ); ?> />
			<span class="slider round"></span>
		</label>
	</div>
</div>

<div class="spacer" style="min-height:30px;"></div>

<h3 class="heading"><?php echo __( 'About Refunds', 'bbmsl-gateway' ); ?></h3>
<p><?php echo __( 'There are 2 modes for refund, be sure to choose Refund via BBMSL to refund transactions over our gateway, otherwise it will be manual refund, which is not reflected on the merchant portal.', 'bbmsl-gateway' ); ?></p>
<p><?php echo __( 'Our gateway can process refunds only when the order is after settlement, and once only per order. Please contact our support for cases beyond this processing scope.', 'bbmsl-gateway' ); ?></p>
<a class="bbmsl-btn" href="<?php echo esc_url( Setup::supportLink() ); ?>" target="_blank" rel="noreferrer noopener">
	<?php echo __( 'Contact Support', 'bbmsl-gateway' ); ?>
</a>

<hr />

<h3 class="heading"><?php echo __( 'Express Checkout', 'bbmsl-gateway' ); ?></h3>
<span class="inline"><?php echo __( 'Display Cart Express Checkout.', 'bbmsl-gateway' ); ?>
	<div class="hint-box">
		<span class="dashicons dashicons-info"></span>
		<div class="popover">
			<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/eshop_express_checkout_%s.png?v=%s', $url_base, $language, Core::$version ); ?>" />
		</div>
	</div></span>
<div class="input-group">
	<div class="input-box">
		<label class="switch">
			<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_EXPRESS_CHECKOUT; ?>]" value="0" />
			<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_EXPRESS_CHECKOUT; ?>]" value="1" <?php echo( Utility::checkBoolean( Option::get( Constants::PARAM_EXPRESS_CHECKOUT ) )?'checked':'' ); ?> />
			<span class="slider round"></span>
		</label>
	</div>
</div>
<p><?php echo __( 'Show BBMSL checkout option from within the mini-cart of the theme.', 'bbmsl-gateway' ); ?></p>

<div class="spacer" style="min-height:200px;"></div>