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
    public $id;
    public $guid;
    public $name;
    public $addressID;
    public $note;
    public $disabled = false;

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
