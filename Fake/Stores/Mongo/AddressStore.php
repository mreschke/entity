<?php namespace Mreschke\Repository\Fake\Stores\Mongo;

use Mreschke\Repository\MongoStore;
use Mreschke\Repository\Fake\Stores\AddressStoreInterface;

/**
 * Fake Address Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class AddressStore extends MongoStore implements AddressStoreInterface
{
	/**
	 * Initialize Store
	 * @return void
	 */
	protected function init() {
		$this->attributes = [
			'entity' => 'address',
			'table' => 'AddressTable',
			'primary' => '_id',
			'increments' => true,
			'indexes' => [
				['key' => 'address', 'options' => ['unique' => false]]
			],
			'map' => [
				'id' => ['column' => '_id', 'filter' => function($store) {
					// Convert mongo id object into hex string
					return (string) $store->_id;
				}],
				'address' => ['column' => 'Address'],
				'city' => ['column' => 'City'],
				'state' => ['column' => 'State'],
				'zip' => ['column' => 'Zip'],
				'note' => ['column' => 'Note'],
			]
		];
	}

}
