<?php

namespace hypeJunction\Subscriptions\Braintree;

use Elgg\Http\ResponseBuilder;
use Elgg\Request;

class ImportBraintreePlans {

	/**
	 * Import braintree subscriptions
	 *
	 * @param Request $request
	 *
	 * @return ResponseBuilder
	 * @throws \Exception
	 */
	public function __invoke(Request $request) {

		$braintree = elgg()->braintree;
		/* @var $braintree \hypeJunction\Braintree\BraintreeClient */

		$braintree_subscriptions = elgg()->{'subscriptions.braintree'};
		/* @var $braintree_subscriptions \hypeJunction\Subscriptions\Braintree\BraintreeSubscriptionsService */

		$collection = $braintree->gateway->plan()->all();

		$result = 0;
		foreach ($collection as $plan) {
			$braintree_subscriptions->importPlan($plan);
			$result++;
		}

		return elgg_ok_response('', elgg_echo('subscriptions:braintree:import:success', [$result]));
	}


}