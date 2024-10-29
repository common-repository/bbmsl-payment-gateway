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

use BBMSL\Bootstrap\Plugin;

?>

<h1 class="heading"><?php echo __( 'Advanced Settings', 'bbmsl-gateway' ); ?></h1>
<p><?php echo __( 'The settings on this page is intended for additional support, merchants should only make use of these functions upon advised by customer support.', 'bbmsl-gateway' ); ?></p>
<div class="debug">
	<pre><?php echo Plugin::getDebugText(); ?></pre>
</div>

<h3 class="heading"><?php echo __( 'Download Logs', 'bbmsl-gateway' ); ?></h3>
<p>
	<?php echo __( 'Download a copy of API activities for programmatic inspection.', 'bbmsl-gateway' ); ?><br />
	<?php echo __( 'This may include request signatures and your public key. Never expose this data other than BBMSL support.', 'bbmsl-gateway' ); ?>
</p>
<a class="bbmsl-btn" href="<?php echo admin_url('?page=bbmsl-settings-download-logs'); ?>" target="_blank" rel="noreferrer noopener">
	<?php echo __( 'Download', 'bbmsl-gateway' ); ?>
</a>

<h3 class="heading"><?php echo __( 'Show Server Info', 'bbmsl-gateway' ); ?></h3>
<p>
	<?php echo __( 'Display your server full configuration.', 'bbmsl-gateway' ); ?><br />
	<?php echo __( 'This will include all technical settings of your hosting server. Never expose this data other than BBMSL support.', 'bbmsl-gateway' ); ?>
</p>
<a class="bbmsl-btn" href="<?php echo admin_url('?page=bbmsl-settings-server-info'); ?>" target="_blank" rel="noreferrer noopener">
	<?php echo __( 'Run', 'bbmsl-gateway' ); ?>
</a>

<h3 class="heading"><?php echo __( 'Reset Settings', 'bbmsl-gateway' ); ?></h3>
<p>
	<?php echo __( 'Erase all BBMSL related settings except for your public and private keys.', 'bbmsl-gateway' ); ?><br />
	<?php echo __( 'Gateway settings will be reset to default state, including previously enabled/disabled payment methods.', 'bbmsl-gateway' ); ?>
</p>
<a class="bbmsl-btn" href="<?php echo admin_url('?page=bbmsl-settings-reset-settings'); ?>">
	<?php echo __( 'Run', 'bbmsl-gateway' ); ?>
</a>