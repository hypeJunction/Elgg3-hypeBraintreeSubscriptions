<?php

return [
	'actions' => [
		'subscriptions/braintree/import' => [
			'controller' => \hypeJunction\Subscriptions\Braintree\ImportBraintreePlans::class,
			'access' => 'admin',
		],
	],
];
