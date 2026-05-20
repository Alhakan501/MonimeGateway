<?php

declare(strict_types=1);

use Adaptors\Givewp\GivewpAdapter;
use Monime\core\Webhook;
use Monime\services\AdapterService;

/**
 * Plugin Name:       Monime Gateway
 * Description:       Monime Payment Gateway Plugin
 * Version:           1.0.1
 * Text Domain:       monime-gateway
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Plugin constants
define('MONIME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MONIME_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MONIME_VERSION', '1.0.1');

// Load core files once (early)
$core_files = [
	'src/Monime/core/Env.php',
	'src/Monime/core/get_urls.php',
	'src/Monime/core/dto.php',
	'src/Monime/core/MonimeClient.php',
	'src/Monime/core/webhook.php',
	'src/Monime/contracts/WebhookAdapterInterface.php',
	'src/Monime/contracts/PaymentAdapterInterface.php',
	'src/Monime/registry/AdapterRegistry.php',
	'src/Monime/services/AdapterService.php',
	'src/Monime/services/PaymentService.php',
	'src/Monime/admin_pages/SettingsPage.php',
];

	foreach ($core_files as $file) {
		$path = MONIME_PLUGIN_DIR . $file;
		if (file_exists($path)) {
			require_once $path;
		} else {
		}
	}

/*
|--------------------------------------------------------------------------
| Admin Menu
|--------------------------------------------------------------------------
*/
add_action('admin_menu', function () {
	$svg_path = MONIME_PLUGIN_DIR . 'assets/images/monime_icon.svg';
	$icon = '';
	if (file_exists($svg_path)) {
		$svg = file_get_contents($svg_path);
		$icon = 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	add_menu_page(
		'Monime',
		'Monime',
		'manage_options',
		'monime-settings',
		'Monime\\admin_pages\\render_settings_page',
		$icon,
		56
	);
});

/*
|--------------------------------------------------------------------------
| Platform Integrations
|--------------------------------------------------------------------------
*/
add_action('plugins_loaded', function () {

	// GiveWP Integration
	if (class_exists(\Give\Framework\PaymentGateways\PaymentGateway::class)) {

		$give_files = [
			'src/adaptors/givewp/GiveMonimeGateway.php',
			'src/adaptors/givewp/GivewpAdapter.php',
		];

			foreach ($give_files as $file) {

				$path = MONIME_PLUGIN_DIR . $file;

				if (file_exists($path)) {
					require_once $path;
				} else {
				}
			}

			GivewpAdapter::boot();
		}

	// WooCommerce Integration
	if (class_exists('WooCommerce') || function_exists('WC')) {
		$wc_files = [
			'src/adaptors/WC/WcMonimeGateway.php',
			'src/adaptors/WC/WC_Monime_Blocks_Support.php',
			'src/adaptors/WC/WcAdapter.php',
		];
		foreach ($wc_files as $file) {
			$path = MONIME_PLUGIN_DIR . $file;
			if (file_exists($path)) {
				require_once $path;
			}
		}
		if (class_exists(WcAdapter::class)) {
			WcAdapter::boot();
		}
	}

	// Shared Services
	if (class_exists(AdapterService::class)) {
		AdapterService::boot();
	}

	if (class_exists(Webhook::class)) {
		Webhook::init();
	}
	});
