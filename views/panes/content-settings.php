<?php

/**
 * advanced-settings.php
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
use BBMSL\Plugin\WordPress;
use BBMSL\Sdk\BBMSL;

$language = ( Constants::LANGUAGE_TC === WordPress::currentLanguage() ? 'tc' : 'en' );
?>

<h1 class="heading"><?php echo __( 'Content Settings', 'bbmsl-gateway' ); ?></h1>

<div class="input-group">
	<span class="inline"><?php echo __( 'Gateway Display Name', 'bbmsl-gateway' ); ?>
		<div class="hint-box">
			<span class="dashicons dashicons-info"></span>
			<div class="popover">
				<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/field_display_name_%s.png?v=%s', BBMSL_PLUGIN_BASE_URL, $language, Core::$version ); ?>" />
			</div>
		</div>
	</span>
	<div class="language-item">
		<span class="language">English</span>
		<div class="language-input">
			<div class="input-box">
				<input type="text" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_DISPLAY_NAME; ?>]" maxlength="256" placeholder="" value="<?php echo Option::get( Constants::PARAM_GATEWAY_DISPLAY_NAME ); ?>" style="width:300px;max-width:100%;"/>
				<span class="line"></span>
			</div>
		</div>
	</div>
	<div class="language-item">
		<span class="language">繁體中文</span>
		<div class="language-input">
			<div class="input-box">
				<input type="text" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_DISPLAY_NAME_TC; ?>]" maxlength="256" placeholder="" value="<?php echo Option::get( Constants::PARAM_GATEWAY_DISPLAY_NAME_TC ); ?>" style="width:300px;max-width:100%;"/>
				<span class="line"></span>
			</div>
		</div>
	</div>
</div>

<div class="spacer" style="min-height:30px;"></div>

<span class="inline"><?php echo __( 'Description', 'bbmsl-gateway' ); ?>
	<div class="hint-box">
		<span class="dashicons dashicons-info"></span>
		<div class="popover">
			<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/field_description_%s.png?v=%s', BBMSL_PLUGIN_BASE_URL, $language, Core::$version ); ?>" />
		</div>
	</div>
</span>
<p><?php echo __( 'Content to display when customer is going to checkout with BBMSL gateway.', 'bbmsl-gateway' ); ?></p>
<div class="steps input-group">
	<div class="language-item">
		<span class="language">English</span>
		<div class="language-input">
			<div class="input-box">
				<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_DESCRIPTION; ?>]"><?php echo Option::get( Constants::PARAM_GATEWAY_DESCRIPTION, '', true ); ?></textarea>
			</div>
		</div>
	</div>
	<div class="language-item">
		<span class="language">繁體中文</span>
		<div class="language-input">
			<div class="input-box">
				<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_DESCRIPTION_TC; ?>]"><?php echo Option::get( Constants::PARAM_GATEWAY_DESCRIPTION_TC, '', true ); ?></textarea>
			</div>
		</div>
	</div>
</div>

<div class="spacer" style="min-height:30px;"></div>

<span class="inline"><?php echo __( 'Available Gateways', 'bbmsl-gateway' ); ?>
	<div class="hint-box">
		<span class="dashicons dashicons-info"></span>
		<div class="popover">
			<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/field_payment_icons_%s.png?v=%s', BBMSL_PLUGIN_BASE_URL, $language, Core::$version ); ?>" />
		</div>
	</div>
</span>
<p>
	<?php echo __( 'The selected icons will be reflected in the checkout options and will also determine the display sequence and payment options on the BBMSL checkout page.', 'bbmsl-gateway' ); ?><br />
	<?php echo __( 'In the scenario where none of the icons are selected, all available payment methods will be displayed on the BBMSL checkout page.', 'bbmsl-gateway' ); ?>
</p>
<table class="gateway-option-label" id="sortable_payment_methods">
	<?php foreach( BBMSL::getCoeasedMethods() as $key => $method) { ?>
	<tr>
		<td><span class="dashicons dashicons-menu handle"></span></td>
		<td>
			<label class="switch payment-method">
				<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_METHODS; ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php echo( BBMSL::hasSelectedMethod( $key )?'checked':'' ); ?> />
				<span class="slider round"></span>
			</label>
		</td>
		<td><img class="logo" src="<?php echo esc_url( BBMSL_PLUGIN_BASE_URL . $method['logo'] ); ?>" /></td>
		<td><span class="name"><?php echo esc_html( $method['name'] ); ?></span></td>
	</tr>
	<?php } ?>
</table>
<p><?php
/* translators: %s is the HTML code for the handle icon */
echo sprintf( __( 'Hint: Drag %s to adjust sorting.', 'bbmsl-gateway' ), '<span class="dashicons dashicons-menu handle"></span>' );
?></p>

<div class="spacer" style="min-height:30px;"></div>

<span class="inline"><?php echo __( 'Thank You Page Content', 'bbmsl-gateway' ); ?>
	<div class="hint-box">
		<span class="dashicons dashicons-info"></span>
		<div class="popover">
			<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/field_thank_you_%s.png?v=%s', BBMSL_PLUGIN_BASE_URL, $language, Core::$version ); ?>" />
		</div>
	</div>
</span>
<p><?php echo __( 'The content displayed after the customer checks out an order and returned to the confirmation page.', 'bbmsl-gateway' ); ?></p>

<div class="steps input-group">
	<div class="language-item">
		<span class="language">English</span>
		<div class="language-input">
			<div class="input-box">
				<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_THANK_YOU_PAGE; ?>]"><?php echo Option::get( Constants::PARAM_GATEWAY_THANK_YOU_PAGE, '', true ); ?></textarea>
			</div>
		</div>
	</div>
	<div class="language-item">
		<span class="language">繁體中文</span>
		<div class="language-input">
			<div class="input-box">
				<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_THANK_YOU_PAGE_TC; ?>]"><?php echo Option::get( Constants::PARAM_GATEWAY_THANK_YOU_PAGE_TC, '', true ); ?></textarea>
			</div>
		</div>
	</div>
</div>

<div class="spacer" style="min-height:30px;"></div>

<span class="inline"><?php echo __( 'Email Content', 'bbmsl-gateway' ); ?>
	<div class="hint-box">
		<span class="dashicons dashicons-info"></span>
		<div class="popover">
			<img class="img-fluid w-100" src="<?php echo sprintf( '%s/public/images/instruction/field_email_content_%s.png?v=%s', BBMSL_PLUGIN_BASE_URL, $language, Core::$version ); ?>" />
		</div>
	</div>
</span>
<p><?php echo __( 'The content display to the customer in the invoice email.', 'bbmsl-gateway' ); ?></p>

<div class="steps input-group">
	<div class="language-item">
		<span class="language">English</span>
		<div class="language-input">
			<div class="input-box">
				<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_EMAIL_CONTENT; ?>]"><?php echo Option::get( Constants::PARAM_GATEWAY_EMAIL_CONTENT, '', true ); ?></textarea>
			</div>
		</div>
	</div>
	<div class="language-item">
		<span class="language">繁體中文</span>
		<div class="language-input">
			<div class="input-box">
				<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_GATEWAY_EMAIL_CONTENT_TC; ?>]"><?php echo Option::get( Constants::PARAM_GATEWAY_EMAIL_CONTENT_TC, '', true ); ?></textarea>
			</div>
		</div>
	</div>
</div>

<div class="spacer" style="min-height:200px;"></div>