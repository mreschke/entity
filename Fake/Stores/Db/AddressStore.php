<?php namespace Mreschke\Repository\Fake\Stores\Db;

use Mreschke\Repository\DbStore;

/**
 * Fake Address Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class AddressStore extends DbStore
{
    /**
     * Initialize Store
     * @return void
     */
    protected function init()
    {
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
