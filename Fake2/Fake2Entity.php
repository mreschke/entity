<?php namespace Mreschke\Repository\Fake2;

use App;
use Mreschke\Repository\Entity;

/**
 * Fake2 Entity
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class Fake2Entity extends Entity
{

    /**
     * Get the repository manager
     * @return object
     */
    protected function fake2()
    {
        return $this->manager();
    }
}
