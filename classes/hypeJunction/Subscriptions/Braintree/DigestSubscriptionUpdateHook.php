<?php

namespace hypeJunction\Subscriptions\Braintree;

use Braintree\WebhookNotification;
use Elgg\Hook;
use hypeJunction\Braintree\BraintreeClient;
use hypeJunction\Subscriptions\Braintree\BraintreeSubscriptionsService;

class DigestSubscriptionUpdateHook {

	/**
	 * Digest plan created web hook
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function __invoke(Hook $hook) {

		$webhook = $hook->getParam('webhook');
		/* @var $webhook WebhookNotification */

		$braintree = elgg()->braintree;
		/* @var $braintree BraintreeClient */

		$subscription = $braintree->gateway->subscription()->find($webhook->subscription->id);

		$svc = elgg()->{'subscriptions.braintree'};
		/* @var $svc BraintreeSubscriptionsService */

		return $svc->importSubscription($subscription);
	}
}