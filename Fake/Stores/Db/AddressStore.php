<?php namespace Mreschke\Repository\Fake\Stores\Db;

use Mreschke\Repository\DbStore;
use Mreschke\Repository\Fake\Stores\AddressStoreInterface;

/**
 * Fake Address Store
 * @copyright Dynatron Software, Inc.
 * @author Matthew Reschke <mreschke@dynatronsoftware.com>
 */
class AddressStore extends DbStore implements AddressStoreInterface
{
	/**
	 * Initialize Store
	 * @return void
	 */
	protected function init() {
		$this->attributes = [
			'entity' => 'address',
			'table' => 'AddressTable',
			'primary' => 'ID',
			'increments' => true,
			'map' => [
				'id' => ['column' => 'ID'],
				'address' => ['column' => 'Address'],
				'city' => ['column' => 'City'],
				'state' => ['column' => 'State'],
				'zip' => ['column' => 'Zip'],
				'note' => ['column' => 'Note'],
			]
		];
	}

}
