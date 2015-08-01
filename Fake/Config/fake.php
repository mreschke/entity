<?php

return [

	// Default Store
	'default' => 'database',

	// Repository Stores
	'stores' => [

		'database' => [
			'driver' => 'db',
			'connection' => 'fake-repository',
		],

		'mongo' => [
			'driver' => 'mongo',
			'host' => 'localhost',
			'port' => 27017,
			'database' => 'fake-repository'
		],

	],

	// Entity store mapping (default store used if none defined)
	// Names should be pascalCase
	'entities' => [
		#'user' => [
		#	'store' => 'sso'
		#],
	],

];
