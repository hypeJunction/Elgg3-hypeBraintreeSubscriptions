<?php

return [
	'subscriptions:braintree:import' => 'Sync Braintree Plans',
	'subscriptions:braintree:import:success' => '%s subscription plans have been synced',
	'subscriptions:braintree:refunded' => '%s %s were refunded to your card',

	'subscriptions:braintree:error:payment_required' => 'Provided payment details are invalid',
	'subscriptions:braintree:error:unsynced_plan' => 'This plan has not been synchronized with Braintree, hence you can not use this payment method',
	'subscriptions:braintree:error:subscription_fail' => 'New subscription could not be created',

	'braintree:notice:setup_plan' => 'Braintree API does not allow plans to be created via an API call. Please log in to your Braintree Control Panel and add a new plan %s [%s]',
];