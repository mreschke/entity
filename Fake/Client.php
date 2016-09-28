<?php namespace Mreschke\Repository\Fake;

use App;

/**
 * Fake Client
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Client extends FakeEntity
{
    public $id;                // Actual db column
    public $guid;              // Actual db column
    public $name;              // Actual db column
    public $addressID;         // Actual db column
    public $note;              // Actual db column
    public $disabled = false;  // Actual db column

    /**
     * Join address entity
     * @return $this
     */
    public function joinAddress()
    {
        $this->store->joinAddress();
        return $this;
    }

    /**
     * Set the showDisabled filter
     * @param  boolean $showDisabled = true
     * @return $this
     */
    public function showDisabled($showDisabled = true)
    {
        $this->store->showDisabled($showDisabled);
        return $this;
    }
}
