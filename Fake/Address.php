<?php namespace Mreschke\Repository\Fake;

use App;

/**
 * Fake Address
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Address extends FakeEntity
{
    public $id;
    public $address;
    public $city;
    public $state;
    public $zip;
    public $note;
}
