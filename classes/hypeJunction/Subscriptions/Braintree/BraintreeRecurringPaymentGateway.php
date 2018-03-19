<?php

namespace hypeJunction\Subscriptions\Braintree;

use Elgg\Http\ResponseBuilder;
use ElggUser;
use hypeJunction\Braintree\BraintreeGateway;
use hypeJunction\Payments\Amount;
use hypeJunction\Subscriptions\RecurringPaymentGatewayInterface;
use hypeJunction\Subscriptions\Subscription;
use hypeJunction\Subscriptions\SubscriptionPlan;

class BraintreeRecurringPaymentGateway extends BraintreeGateway implements RecurringPaymentGatewayInterface {

	/**
	 * Start a recurring payment
	 *
	 * @param ElggUser         $user   User
	 * @param SubscriptionPlan $plan   Plan
	 * @param array            $params Request parameters
	 *
	 * @return ResponseBuilder
	 */
	public function subscribe(ElggUser $user, SubscriptionPlan $plan, array $params = []) {
		$nonce = elgg_extract('braintree_token', $params);

		if (!$nonce) {
			return elgg_error_response(elgg_echo('subscriptions:braintree:error:payment_required'), REFERRER, ELGG_HTTP_BAD_REQUEST);
		}

		try {

			if (!$plan->braintree_id) {
				throw new \RuntimeException(elgg_echo('subscriptions:braintree:error:unsynced_plan'));
			}

			$braintree_customer = $this->client->createCustomer($user);

			$result = $this->client->gateway->paymentMethod()->create([
				'customerId' => $braintree_customer->id,
				'paymentMethodNonce' => $nonce,
			]);

			if (!$result->success) {
				return elgg_error_response(elgg_echo('subscriptions:braintree:error:payment_required'), REFERRER, ELGG_HTTP_BAD_REQUEST);
			}

			$braintree_payment_method = $result->paymentMethod;

			$result = $this->client->gateway->subscription()->create([
				'paymentMethodToken' => $braintree_payment_method->token,
				'planId' => $plan->braintree_id,
			]);

			if (!$result->success) {
				return elgg_error_response(elgg_echo('subscriptions:braintree:error:subscription_fail'), REFERRER, ELGG_HTTP_BAD_REQUEST);
			}

			$braintree_subscription = $result->subscription;

			if ($this->client->environment === 'sandbox') {
				$this->client->gateway->testing()->settle($braintree_subscription->transactions[0]->id);
			}

			if ($record = $plan->subscribe($user, $braintree_subscription->billingPeriodEndDate->getTimestamp())) {
				$record->braintree_id = $braintree_subscription->id;

				return elgg_ok_response([
					'user' => $user,
					'subscription' => $record,
				], elgg_echo('subscriptions:subscribe:success', [$plan->getDisplayName()]));
			}

		} catch (\Exception $ex) {
			return elgg_error_response($ex->getMessage(), REFERRER, $ex->getCode() ? : ELGG_HTTP_INTERNAL_SERVER_ERROR);
		}

		return elgg_error_response(elgg_echo('subscriptions:subscribe:error'), REFERRER, ELGG_HTTP_INTERNAL_SERVER_ERROR);
	}

	/**
	 * Cancel subscription
	 *
	 * @param Subscription $subscription Subscription
	 * @param array        $params       Request parameters
	 *
	 * @return bool
	 */
	public function cancel(Subscription $subscription, array $params = []) {

		$at_period_end = elgg_extract('at_period_end', $params, true);

		try {

			$braintree_subscription = $this->client->gateway->subscription()->find($subscription->braintree_id);

			if (!$at_period_end) {
				$time = new \DateTime('now', new \DateTimeZone('UTC'));
				$used = $time->getTimestamp() - $braintree_subscription->billingPeriodStartDate->getTimestamp();

				$duration = $braintree_subscription->billingPeriodEndDate->getTimestamp() - $braintree_subscription->billingPeriodStartDate->getTimestamp();

				$transactions = $braintree_subscription->transactions;
				if (!empty($transactions)) {
					$last_transaction = array_shift($transactions);
					$amount = Amount::fromString($last_transaction->amount, $last_transaction->currencyIsoCode);

					$refund = $amount->getAmount() - round(($used / $duration) * $amount->getAmount());
					$refund = new Amount((int) $refund, $amount->getCurrency());

					if ($refund->getAmount() > 0) {

						$result = $this->client->gateway->transaction()->refund($last_transaction->id, [
							'amount' => $refund->getConvertedAmount(),
						]);

						if ($result->success) {
							system_message(elgg_echo('subscriptions:braintree:refunded', [
								$amount->getConvertedAmount(),
								$amount->getCurrency()
							]));
						}
					}

				}

				$this->client->gateway->subscription()->cancel($subscription->braintree_id);

				return true;
			}
		} catch (\Exception $ex) {
			elgg_log($ex->getMessage(), 'ERROR');

			return false;
		}
	}
}