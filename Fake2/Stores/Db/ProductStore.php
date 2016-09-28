<?php namespace Mreschke\Repository\Fake2\Stores\Db;

use Mreschke\Repository\DbStore;

/**
 * Fake2 Product Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class ProductStore extends DbStore
{
    /**
     * Initialize Store
     * @return void
     */
    protected function init()
    {
        $this->attributes = [
            'entity' => 'product',
            'table' => 'ProductTable',
            'primary' => 'ID',
            'increments' => true,
            'map' => [
                'id' => ['column' => 'ID'],
                'name' => ['column' => 'Name'],
                'price' => ['column' => 'Price'],
                'disabled' => ['column' => 'Disabled', 'filter' => function ($store) {
                    return (boolean) $store->Disabled;
                }],

                // Relationships
                #'address' => ['entity' => 'address', 'table' => 'AddressTable'],
                #'groups' => ['entity' => 'group', 'table' => 'GroupTAble'],

            ]
        ];
    }
}
