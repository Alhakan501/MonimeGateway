<?php

namespace Monime\services;

use Monime\core\MonimeClient;
use Monime\registry\AdapterRegistry;
use Monime\core\Env;

/**
 * Coordinates payment creation between platform adapters and MonimeClient.
 */
class PaymentService
{
	/**
	 * Build a Monime checkout request through the selected adapter and send it.
	 */
	public static function create(string $adaptorid, array $payload): array
	{
		// Resolve the adapter that knows how to translate this platform payload.
		$adaptor = AdapterRegistry::get($adaptorid);
		if (!$adaptor) {
			return [
				'message' => 'Adapter not found'
			];
		};
		$env = Env::get();

		// Provide default display text when an adapter did not supply it.
		$payload['name'] = $payload['name'] ?? ($env['name'] ?? 'Donation');
		$payload['description'] = $payload['description'] ?? ($env['description'] ?? 'GiveWP Donation');

		// Normalize adapter-specific data into the shared Monime request DTO.
		$data = $adaptor->buildPaymentPayload($payload);
		$client = new MonimeClient(
			$env['monime_token'],
			$env['monime_space_id']
		);
		error_log(print_r($data, true));
		// Pass the adapter ID so Monime webhooks can be routed back correctly.
		$response = $client->create_checkout_session(donationrequest: $data, _handler: $adaptorid);
		//error_log($response);
		return $response;
	}
}
