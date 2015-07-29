<?php

return [

	// Default Store
	'default' => 'test-database',

	// Repository Stores
	'stores' => [

		'test-database' => [
			'driver' => 'db',
			'connection' => 'test-repository-db',
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
