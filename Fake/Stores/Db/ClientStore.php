<?php namespace Mreschke\Repository\Fake\Stores\Db;

use Mreschke\Repository\DbStore;

/**
 * Fake Client Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class ClientStore extends DbStore
{
	/**
	 * The showDisabled filter
	 * @var array
	 */
	protected $showDisabled = false;

	/**
	 * Initialize Store
	 * @return void
	 */
	protected function init() {
		$this->attributes = [
			'entity' => 'client',
			'table' => 'ClientTable',
			'primary' => 'ID',
			'increments' => true,
			'order_by' => 'ClientTable.Name',
			#'keystone_attributes' => 'dynatron/iam::client:%id%:attributes',
			'map' => [
				'id' => ['column' => 'ID'],
				'guid' => ['column' => 'GUID'],
				'name' => ['column' => 'Name'],
				'addressID' => ['column' => 'AddressID'],
				'note' => ['column' => 'Note'],
				'disabled' => ['column' => 'Disabled', 'filter' => function($store) {
					return (boolean) $store->Disabled;
				}],

				// Relationships
				'address' => ['entity' => 'address', 'table' => 'AddressTable'],
				'groups' => ['entity' => 'group', 'table' => 'GroupTAble'],
			]
		];
	}

	/**
	 * Set the showDisabled filter
	 * @param  boolean
	 * @return void
	 */
	public function showDisabled($showDisabled)
	{
		$this->showDisabled = $showDisabled;
	}

	/**
	 * Find a record by id or key
	 * @param  mixed $id
	 * @return object
	 */
	public function find($id = null)
	{
		// Always show disabled on find()
		$this->showDisabled(true);
		return parent::find($id);
	}

	/**
	 * Get clients by group type and id/guid/key
	 * @param  string $groupTypeKey
	 * @param  mixed $id can be id, guid or key
	 * @return \Illuminate\Support\Collection
	 */
	public function byGroup($groupTypeKey, $id)
	{
		if (is_numeric($id)) {
			$idColumn = 'id';
		} elseif (strlen($id) == 36) {
			$idColumn = 'guid';
		} else {
			$idColumn = 'key';
		}
		return $this->transaction(function() use($groupTypeKey, $id, $idColumn) {
			return $this->newQuery()
				->join('client_groups', 'clients.id', '=', 'client_groups.client_id')
				->join('groups', 'client_groups.group_id', '=', 'groups.id')
				->where('groups.group_type', $groupTypeKey)
				->where('groups.'.$idColumn, $id)
				->get();
			});
	}

	/**
	 * Start a new query builder
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newQuery()
	{
		if (!$this->showDisabled) $this->where('disabled', false);
		return parent::newQuery();
	}

	/**
	 * Resets the query properties
	 * @return void
	 */
	protected function resetQuery()
	{
		parent::resetQuery();
		$this->showDisabled = false;
	}

	/**
	 * Add address join to query
	 * @param  \Illuminate\Database\Query\Builder $query
	 */
	public function joinAddress()
	{
		$this->join('address', 'client.addressID', '=', 'address.id');
	}

	/**
	 * Merge address subentity with client
	 * @param  \Illuminate\Support\Collection $clients
	 */
	protected function mergeAddress($clients)
	{
		$addresses = $this->manager->address->where('id', 'in', $clients->lists('addressID'))->get();
		foreach ($clients as $client) {
			$client->address = isset($addresses[$client->addressID]) ? $addresses[$client->addressID]: null;
		}
	}

	/**
	 * Merge groups subentity with client
	 * @param  \Illuminate\Support\Collection $clients
	 */
	protected function mergeGroups($clients)
	{
		$groups = $this->manager->group->byClients($clients->lists('id'));
		foreach ($groups as $clientID => $group) {
			$clients[$clientID]->groups = collect($group);
		}
	}

}
