<?php namespace Mreschke\Repository\Fake2\Stores\Db;

use Mreschke\Repository\DbStore;

/**
 * Fake2 Client Info Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class ClientInfoStore extends DbStore
{
    /**
     * Initialize Store
     * @return void
     */
    protected function init()
    {
        $this->attributes = [
            'entity' => 'clientInfo',
            'table' => 'ClientInfoTable',
            'primary' => 'ClientID',
            'increments' => false,
            'map' => [
                'clientID' => ['column' => 'ClientID'],
                'region' => ['column' => 'Region'],
                'saleDate' => ['column' => 'SaleDate'],

                // Relationships
                #'address' => ['entity' => 'address', 'table' => 'AddressTable'],
                #'groups' => ['entity' => 'group', 'table' => 'GroupTAble'],
            ]
        ];
    }
}
