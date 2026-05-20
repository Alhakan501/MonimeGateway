<?php

namespace Adaptors\Givewp;

use Monime\registry\AdapterRegistry;

/**
 * Boots the GiveWP integration and registers it with both Monime and GiveWP.
 */
class GivewpAdapter
{
	/**
	 * Attach GiveWP-related hooks.
	 */
	public static function boot(): void
	{
		/**
		 * Register in Monime registry
		 */
		add_action('monime_register_adapters', function () {
			AdapterRegistry::registerAdapter(
				new GiveMonimeGateway()
			);
		});

		/**
		 * Register in GiveWP
		 */
			add_action('givewp_register_payment_gateway', function ($registrar) {

				// GiveWP may not have loaded its gateway base class yet.
				if (
					!class_exists(\Give\Framework\PaymentGateways\PaymentGateway::class) ||
					!class_exists(GiveMonimeGateway::class)
<<<<<<< HEAD
				) {
					return;
				}
=======
			) {
				error_log('[Monime] GiveWP not ready or gateway missing');
				return;
			}
>>>>>>> 81d537cdd291be62e2ee9205a06e72c1809819a0

			// ✅ CORRECT METHOD
			$registrar->registerGateway(
				GiveMonimeGateway::class
			);
		});

		/**
		 * Register settings at correct time
		 */
		add_action('admin_init', function () {
			GiveMonimeGateway::registerSettings();
		});
	}
}
