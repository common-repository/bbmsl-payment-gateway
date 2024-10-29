<?php

/**
 * woocommerce-settings.php
 *
 * WordPress view file for woocommerce specific settings page.
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

use BBMSL\Bootstrap\Constants;
use BBMSL\Sdk\BBMSL;
use BBMSL\Plugin\WordPress;

$url_base = plugin_dir_url( BBMSL_PLUGIN_FILE );
$language = ( Constants::LANGUAGE_TC === WordPress::currentLanguage() ? 'tc' : 'en' );
$enabled_columns = BBMSL::getEnabledWooCommerceOrderColumns();

?>
<h1 class="heading"><?php echo __( 'WooCommerce Settings', 'bbmsl-gateway' ); ?></h1>
<h3 class="heading"><?php echo __( 'Order Column Display', 'bbmsl-gateway' ); ?></h3>
<span class="inline"><?php echo __( 'Select to show additional columns when viewing in WooCommerce order listing page.', 'bbmsl-gateway' ); ?></span>
<p>
	<a class="me-2" href="javascript:void(0)" id="bbmsl-gateway-woocommerce-column-select-all"><?php echo __( 'Select All', 'bbmsl-gateway' ); ?></a>
	<a class="me-2" href="javascript:void(0)" id="bbmsl-gateway-woocommerce-column-deselect-all"><?php echo __( 'Deselect All', 'bbmsl-gateway' ); ?></a>
</p>
<table class="gateway-option-label">
	<?php foreach(Constants::getWooCommerceColumns() as $kk => $rrow){ ?>
	<tr>
		<td>
			<label class="switch woocommerce-column">
				<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_WC_ORDER_COLUMNS; ?>][]" value="<?php echo $kk; ?>" <?php echo( in_array( $kk, $enabled_columns, true )?'checked':'' ); ?> />
				<span class="slider round"></span>
			</label>
		</td>
		<td class="name"><?php echo esc_html__($rrow['name']); ?></td>
	</tr>
	<?php } ?>
</table>

<div class="spacer" style="min-height:360px;"></div>

<script>
window.addEventListener('DOMContentLoaded', function () {
	const checkbox_wc_select_all = document.getElementById('bbmsl-gateway-woocommerce-column-select-all');
	const checkbox_wc_select_none = document.getElementById('bbmsl-gateway-woocommerce-column-deselect-all');
	const checkbox_wc_inputs = [...document.querySelectorAll('input[type="checkbox"][name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo Constants::PARAM_WC_ORDER_COLUMNS; ?>][]"]')];

	checkbox_wc_select_all.addEventListener('click', function (event) {
		if (event.isTrusted) {
			checkbox_wc_inputs.map(function (elem) { elem.checked = true; });
		}
	});

	checkbox_wc_select_none.addEventListener('click', function (event) {
		if (event.isTrusted) {
			checkbox_wc_inputs.map(function (elem) { elem.checked = false; });
		}
	});
});
</script>