<?php namespace Mreschke\Repository\Fake\Stores\Db;

use Mreschke\Repository\DbStore;
use Mreschke\Repository\Fake\Stores\ClientStoreInterface;

/**
 * Iam Client Store
 * @copyright Dynatron Software, Inc.
 * @author Matthew Reschke <mreschke@dynatronsoftware.com>
 */
class ClientStore extends DbStore implements ClientStoreInterface
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
			'table' => 'clients',
			'primary' => 'id',
			'increments' => true,
			'order_by' => 'clients.name',
			'keystone_attributes' => 'dynatron/iam::client:%id%:attributes',
			'map' => [
				'id' => ['column' => 'id'],
				'guid' => ['column' => 'guid'],
				'name' => ['column' => 'name'],
				'extract' => ['column' => 'extract'],
				'hostKey' => ['column' => 'host'],

				#'hostname' => ['column' => 'hosts.key', 'save' => false],
				#'company' => ['column' => 'hosts.name', 'save' => false],
				#'companyGuid' => ['column' => 'hosts.guid', 'save' => false],
				#'server' => ['column' => 'hosts.server_num', 'save' => false],
				#'serverNum' => ['column' => 'hosts.server_num', 'save' => false],

				'addressID' => ['column' => 'address_id'],
				'disabled' => ['column' => 'disabled', 'filter' => function($store) {
					return (boolean) $store->disabled;
				}],
				'created' => ['column' => 'created_at'],
				'updated' => ['column' => 'updated_at'],

				// Relationships
				#'host' => ['entity' => 'host', 'table' => 'hosts'],
				#'address' => ['entity' => 'address', 'table' => 'addresses'],
				#'groups' => ['entity' => 'group', 'table' => 'groups'],
			]
		];

		// Always include hosts
		#$this->with('host'); // NO, do it manually...or I do it if you select on legacy columns like serverNum or hostname
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
	 * Add host join to query
	 */
	public function joinHost()
	{
		$this->join('host', 'client.hostKey', '=', 'host.key');
	}

	/**
	 * Merge address subentity with client
	 * @param  \Illuminate\Support\Collection $clients
	 */
	protected function mergeAddress($clients)
	{
		$addresses = app($this->realNamespace())->address->where('id', 'in', $clients->lists('addressID'))->get();
		foreach ($clients as $client) {
			$client->address = isset($addresses[$client->addressID]) ? $addresses[$client->addressID]: null;
		}
	}

	/**
	 * Merge host subentity with client
	 * @param  \Illuminate\Support\Collection $clients
	 */
	protected function mergeHost($clients)
	{
		$hosts = app($this->realNamespace())->host->where('key', 'in', $clients->lists('hostKey'))->showDisabled()->get();
		foreach ($clients as $client) {
			$client->host = isset($hosts[$client->hostKey]) ? $hosts[$client->hostKey]: null;
		}
	}

	/**
	 * Merge groups subentity with client
	 * @param  \Illuminate\Support\Collection $clients
	 */
	protected function mergeGroups($clients)
	{
		$groups = app($this->realNamespace())->group->byClients($clients->lists('id'));
		foreach ($groups as $clientID => $group) {
			$clients[$clientID]->groups = collect($group);
		}
	}

}
