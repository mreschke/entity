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
    public $id;                // Actual db column
    public $address;           // Actual db column
    public $city;              // Actual db column
    public $state;             // Actual db column
    public $zip;               // Actual db column
    public $note;              // Actual db column
}
