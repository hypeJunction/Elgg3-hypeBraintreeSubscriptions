<?php

/**
 * Subscriptions
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 */
require_once __DIR__ . '/autoloader.php';

return function () {
	elgg_register_event_handler('init', 'system', function () {

		$svc = elgg()->subscriptions;
		/* @var $svc \hypeJunction\Subscriptions\SubscriptionsService */

		$svc->registerGateway(elgg()->{'subscriptions.gateways.braintree'});

		elgg_register_event_handler('update', 'object', \hypeJunction\Subscriptions\Braintree\OnUpdateEvent::class);

		elgg_register_event_handler('cancel', 'subscription', \hypeJunction\Subscriptions\Braintree\OnSubscriptionCancelEvent::class);

		elgg_register_plugin_hook_handler('register', 'menu:page', \hypeJunction\Subscriptions\Braintree\PageMenu::class);

		elgg_register_plugin_hook_handler('subscription_canceled', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('subscription_charged_successfully', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('subscription_charged_unsuccessfully', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('subscription_expired', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('subscription_trial_ended', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('subscription_went_active', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('subscription_went_past_due', 'braintree', \hypeJunction\Subscriptions\Braintree\DigestSubscriptionUpdateHook::class);
	});
};
