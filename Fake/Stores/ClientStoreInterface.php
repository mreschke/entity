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
	public function showDisabled($showDisabled);

	/**
	 * Get clients by group type and id/guid/key
	 * @param  string $groupTypeKey
	 * @param  mixed $id can be id, guid or key
	 * @return \Illuminate\Support\Collection
	 */
	public function byGroup($groupTypeKey, $id);

	/**
	 * Add address join to query
	 * @param  \Illuminate\Database\Query\Builder $query
	 */
	public function joinAddress();

}
