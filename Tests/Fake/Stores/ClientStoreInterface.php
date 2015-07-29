<?php namespace Mreschke\Repository\Fake\Stores;

/**
 * Fake Client Store Interface
 * @copyright Dynatron Software, Inc.
 * @author Matthew Reschke <mreschke@dynatronsoftware.com>
 */
interface ClientStoreInterface
{
	/**
	 * Set the showDisabled filter
	 * @param  boolean
	 * @return void
	 */
	#public function showDisabled($showDisabled);

}
