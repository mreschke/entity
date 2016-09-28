<?php namespace Mreschke\Repository\Fake;

use App;

/**
 * Fake Attribute
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Attribute extends FakeEntity
{
    public $key;                // Actual db column
    public $entity;             // Actual db column
    public $entityID;           // Actual db column
    public $index;              // Actual db column
    public $value;              // Actual db column
}
