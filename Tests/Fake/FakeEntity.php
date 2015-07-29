<?php namespace Mreschke\Repository\Fake;

use App;
use Mreschke\Repository\Entity;

/**
 * Fake Entity
 * @copyright Dynatron Software, Inc.
 * @author Matthew Reschke <mreschke@dynatronsoftware.com>
 */
abstract class FakeEntity extends Entity
{
	/**
	 * Get the repository manager
	 * @return object
	 */
	protected function manager()
	{
		// Return the manager from Laravels IoC Singleton
		return App::make('Mreschke\Repository\Fake');
	}

	/**
	 * Get the repository manager
	 * @return object
	 */
	protected function fake()
	{
		return $this->manager();
	}

}
