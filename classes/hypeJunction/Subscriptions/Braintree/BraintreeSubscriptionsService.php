<?php

namespace hypeJunction\Subscriptions\Braintree;

use hypeJunction\Payments\Amount;
use hypeJunction\Braintree\BraintreeClient;
use hypeJunction\Subscriptions\SubscriptionPlan;
use Braintree\Plan;
use Braintree\Subscription;

class BraintreeSubscriptionsService {

	/**
	 * @var BraintreeClient
	 */
	protected $client;

	/**
	 * Constructor
	 *
	 * @param BraintreeClient $client Client
	 */
	public function __construct(BraintreeClient $client) {
		$this->client = $client;
	}

	/**
	 * Import plan
	 *
	 * @param Plan $plan Plan
	 *
	 * @return SubscriptionPlan|false
	 * @throws \Exception
	 */
	public function importPlan(Plan $plan) {

		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($plan) {
			$entities = elgg_get_entities([
				'types' => 'object',
				'subtypes' => SubscriptionPlan::SUBTYPE,
				'metadata_name_value_pairs' => [
					'plan_id' => $plan->id,
				],
				'limit' => 1,
			]);

			if (empty($entities)) {
				$entity = new SubscriptionPlan();
				$entity->container_guid = elgg_get_site_entity()->guid;
				$entity->access_id = ACCESS_PUBLIC;


				$entity->title = $plan->name;

				$entity->braintree_id = $plan->id;

				$entity->setPlanId($plan->id);

				$entity->interval = 'month';
				$entity->interval_count = $plan->billingFrequency;

				$amount = Amount::fromString($plan->price, $plan->currencyIsoCode);
				$entity->setPrice($amount);

				if ($plan->trialDurationUnit === 'day') {
					$entity->trial_period_days = $plan->trialDuration;
				} else {
					$entity->trial_period_days = $plan->trialDuration * 31;
				}
			} else {
				$entity = array_shift($entities);

				$entity->braintree_id = $plan->id;
			}


			$entity->setVolatileData('is_import', true);

			if (!$entity->save()) {
				return false;
			}

			return $entity;
		});

	}

	/**
	 * Import braintree subscription
	 *
	 * @param Subscription $subscription Subscription
	 *
	 * @return \hypeJunction\Subscriptions\Subscription|false
	 * @throws \Exception
	 */
	public function importSubscription(Subscription $subscription) {

		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($subscription) {

			$entities = elgg_get_entities([
				'types' => 'object',
				'subtypes' => \hypeJunction\Subscriptions\Subscription::SUBTYPE,
				'metadata_name_value_pairs' => [
					'braintree_id' => $subscription->id,
				],
				'limit' => 1,
			]);

			$entity = false;

			if ($entities) {
				$entity = $entities[0];
			} else {
				try {
					$plans = elgg_get_entities([
						'types' => 'object',
						'subtypes' => \hypeJunction\Subscriptions\SubscriptionPlan::SUBTYPE,
						'metadata_name_value_pairs' => [
							'braintree_id' => $subscription->planId,
						],
						'limit' => 1,
					]);

					$plan = array_shift($plans);

					$transaction = $subscription->transactions[0];
					$customer_id = $transaction->customerDetails->id;

					$customer = $this->client->getCustomer($customer_id);

					$users = get_user_by_email($customer->email);

					if ($plan instanceof SubscriptionPlan && $users) {
						$user = array_shift($users);

						$entity = $plan->subscribe($user, $subscription->billingPeriodEndDate->getTimestamp());
					}
				} catch (\Exception $ex) {

				}
			}

			if (!$entity) {
				return true;
			}

			switch ($subscription->status) {
				case Subscription::ACTIVE :
				case Subscription::PENDING :
				case Subscription::PAST_DUE :
					$entity->current_period_end = $subscription->billingPeriodEndDate->getTimestamp();
					break;

				case Subscription::CANCELED :
				case Subscription::EXPIRED :
					$entity->cancelled_at = time();
					$entity->current_period_end = time();
					break;

			}

			$entity->braintree_id = $subscription->id;

			return $entity;
		});
	}
}