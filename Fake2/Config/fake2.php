<?php

return [

	// Default Store
	'default' => 'database',

	// Repository Stores
	'stores' => [

		'database' => [
			[
				'driver' => 'db',
				'connection' => 'fake2-repository',
				'entities' => [
					'clientInfo',
					'product'
				]
			],
			[
				'driver' => 'db',
				'connection' => 'fake-repository',
				'entities' => [
					'client'
				]
			]
		],

		'mongo' => [
			[
				'driver' => 'mongo',
				'host' => 'localhost',
				'port' => 27017,
				'database' => 'fake2-repository',
				'entities' => [
					'clientInfo',
					'product'
				]
			],
			[
				'driver' => 'mongo',
				'host' => 'localhost',
				'port' => 27017,
				'database' => 'fake-repository',
				'entities' => [
					'client'
				]
			]
		]

	]
	
];
