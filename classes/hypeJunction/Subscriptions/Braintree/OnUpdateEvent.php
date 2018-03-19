<?php

namespace hypeJunction\Subscriptions\Braintree;

use Elgg\Event;
use hypeJunction\Subscriptions\SubscriptionPlan;

class OnUpdateEvent {

	/**
	 * Sync plan updates
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 */
	public function __invoke(Event $event) {

		$entity = $event->getObject();
		if (!$entity instanceof SubscriptionPlan) {
			return null;
		}

		if ($entity->getVolatileData('is_import')) {
			return null;
		}

		if (!$entity->braintree_id) {
			elgg_add_admin_notice("braintree:plan:$entity->plan_id", elgg_echo('braintree:notice:setup_plan', [
				$entity->getDisplayName(),
				$entity->plan_id
			]));
		}

	}
}