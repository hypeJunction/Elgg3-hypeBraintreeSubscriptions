<?php

return [
	'subscriptions.braintree' => \DI\object(\hypeJunction\Subscriptions\Braintree\BraintreeSubscriptionsService::class)
		->constructor(\DI\get('braintree')),

	'subscriptions.gateways.braintree' => \DI\object(\hypeJunction\Subscriptions\Braintree\BraintreeRecurringPaymentGateway::class)
		->constructor(\DI\get('braintree')),

];