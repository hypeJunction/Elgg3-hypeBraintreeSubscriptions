<?php

namespace hypeJunction\Subscriptions\Braintree;

use Elgg\Event;
use hypeJunction\Payments\Amount;
use hypeJunction\Subscriptions\Subscription;

class OnSubscriptionCancelEvent {

	/**
	 * Sync plan updates
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 */
	public function __invoke(Event $event) {

		$entity = $event->getObject();
		if (!$entity instanceof Subscription) {
			return null;
		}

		if (!$entity->braintree_id) {
			return;
		}

		$at_period_end = $entity->current_period_end > time();

		$gateway = elgg()->{'subscriptions.gateways.braintree'};
		/* @var $gateway \hypeJunction\Subscriptions\Braintree\BraintreeRecurringPaymentGateway */

		return $gateway->cancel($entity, ['at_period_end' => $at_period_end]);
	}
}
