<?php namespace Mreschke\Repository\Fake2\Stores\Mongo;

use Mreschke\Repository\MongoStore;

/**
 * Fake2 Product Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class ProductStore extends MongoStore
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
            'primary' => '_id',
            'increments' => true,
            'map' => [
                'id' => ['column' => '_id'],
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
