<?php

/**
 * Plugin Name: Order Attachments for WooCommerce
 * Description: This plugin allows you to add a file to orders in your store.
 * Author: DIRECT SOFTWARE
 * Author URI: https://directsoftware.pl
 * Text Domain: sld-wcoa
 * Domain Path: /languages
 * Version: 2.5.1
 * Requires at least: 5.5
 * Requires PHP: 8.0
 * Tested up to: 6.6.2
 * WC tested up to: 9.3.3
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH'))
{
    exit; // Exit if accessed directly
}

use DirectSoftware\WCOA\Kernel;

define( 'WCOA_PLUGIN_VERSION', '2.5.1' );
define( 'WCOA_PLUGIN_PATH', __FILE__);
define( 'WCOA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCOA_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WCOA_BASENAME', plugin_basename(__FILE__) );

require_once __DIR__ . '/vendor/autoload.php';

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true))
{
	add_action( 'admin_notices', 'wcoa_woocommerce_verification' );
	return;
}

function wcoa_woocommerce_verification(): void
{ ?>
	<div class="notice notice-error">
		<p class="wcoa-weight-600"><?php _e( 'An error occurred while attempting to run Order Attachments for WooCommerce plugin!', 'sld-wcoa' ); ?></p>
		<p><?php _e( 'WooCommerce plugin install and activate required.', 'sld-wcoa' ); ?></p>
	</div>
	<?php
}

register_activation_hook(__FILE__, [Kernel::class, 'activation_task']);

Kernel::getInstance();
