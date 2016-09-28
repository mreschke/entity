<?php namespace Mreschke\Repository\Fake;

use App;
use Mreschke\Repository\Entity;

/**
 * Fake Entity
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class FakeEntity extends Entity
{

    /**
     * Get the repository manager
     * @return object
     */
    protected function fake()
    {
        return $this->manager();
    }
}
