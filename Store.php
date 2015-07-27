<?php namespace Mreschke\Repository;

use Event;
use stdClass;

/**
 * Repository Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class Store
{
	/**
	 * The repository manager
	 * @var object
	 */
	protected $manager;

	/**
	 * The repository store key
	 * @var object
	 */
	protected $storeKey;

	/**
	 * The repository attributes
	 * @var array
	 */
	protected $attributes;

	/**
	 * The selected columns for this instance
	 * @var array
	 */
	protected $select;

	/**
	 * The join statements
	 * @var array
	 */
	protected $join;

	/**
	 * The where statement
	 * @var array
	 */
	protected $where;

	/**
	 * The search query filter
	 * @var string
	 */
	protected $filter;

	/*
	 * The orderBy column and direction
	 * @var array
	 */
	protected $orderBy;

	/**
	 * The limit and offset
	 * @var array
	 */
	protected $limit;

	/**
	 * The final get function override
	 * @var string
	 */
	protected $with;

	/**
	 * Add a join to the query
	 * @param  string $table
	 * @param  string $one
	 * @param  string $operator
	 * @param  string $two
	 * @return void
	 */
	public function join($table, $one, $operator, $two)
	{
		$one = $this->map($one);
		$two = $this->map($two);

		// Translate table
		if ($table != $this->attributes('table')) {
			$manager = app($this->realNamespace());
			$table = $manager->$table->store->attributes('table');
		}

		$this->join[] = [
			'table' => $table,
			'one' => $one,
			'operator' => $operator,
			'two' => $two
		];
	}

	/**
	 * Set a new select statement
	 * @param  array
	 * @return void
	 */
	public function select($columns)
	{
		$this->select = $this->map($columns);
	}

	/**
	 * Add additional select(s) to the query
	 * @param  array
	 * @return void
	 */
	public function addSelect($columns)
	{
		$this->select[] = $this->map($columns);
	}

	/**
	 * Add a where clause to the query
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return void
	 */
	public function where($column, $operator = null, $value = null)
	{
		// Only allow operators that ALL stores can utilize (no like, between...)
		$operators = ['=', '>', '<', '!=', '>=', '<=', 'like', 'in'];
		if (func_num_args() >= 2) {
			if (func_num_args() == 2) {
				list($value, $operator) = array($operator, '=');
			}
			if (!in_array(strtolower($operator), $operators)) {
				$operator = "=";
			}

			/*$table = $this->attributes('table');
			if (str_contains($column, '.')) list($table, $column) = explode('.', $column);

			$subentity = false;
			if ($table != $this->attributes('table')) {
				// This column does not belong to this store...but to a subentity. Use that subentities attributes map
				$manager = app($this->realNamespace()); //Tricky...for inherited entities like VFI client
				$map = $manager->$table->store->attributes('map');
				$table = $manager->$table->store->attributes('table');
				$subentity = true;
			} else {
				// This columns belongs to this store...use this stores map
				$map = $this->attributes('map');
			}

			$mappedColumn = $this->map($column, false, $map);
			if (str_contains($mappedColumn, '.')) {
				// If mappedColumn has . in it, use that as override table
				list($table, $mappedColumn) = explode('.', $mappedColumn);
			}*/

			$mappedColumn = $this->map($column);
			$this->where[] = [
				#'table' => $table,
				#'subentity' => $subentity,
				'column' => $mappedColumn,
				'operator' => $operator,
				'value' => $value,
			];
		}
	}

	/**
	 * Set the search query filter
	 * @param  string
	 * @return void
	 */
	public function filter($query)
	{
		$this->filter = $query;
	}

	/**
	 * Set the orderBy column and direction for the query
	 * @param  string $column
	 * @param  string $direction
	 * @return void
	 */
	public function orderBy($column, $direction)
	{
		$table = $this->attributes('table');
		if (str_contains($column, '.')) list($table, $column) = explode('.', $column);

		if ($table != $this->attributes('table')) {
			// This column does not belong to this store...but to a subentity. Use that subentities attributes map
			$manager = app($this->realNamespace()); //Tricky...for inherited entities like VFI client
			$map = $manager->$table->store->attributes('map');
			$table = $manager->$table->store->attributes('table');
		} else {
			// This columns belongs to this store...use this stores map
			$map = $this->attributes('map');
		}

		$mappedColumn = $this->map($column, false, $map);
		if (str_contains($mappedColumn, '.')) {
			// If mappedColumn has . in it, use that as override table
			list($table, $mappedColumn) = explode('.', $mappedColumn);
		}

		$this->orderBy = [
			'column' => "$table.$mappedColumn",
			'direction' => $direction
		];
	}

	/**
	 * Set the limit and offset for the query
	 * @param  integer $offset
	 * @param  integer $limit
	 * @return void
	 */
	public function limit($offset, $limit)
	{
		$this->limit = ['skip' => $offset, 'take' => $limit];
	}

	/**
	 * Set the with get finalizer override method
	 * @param  string $properties
	 * @return void
	 */
	public function with($properties)
	{
		if (!is_array($properties)) $properties = func_get_args();
		$this->with = $properties;
	}

	/**
	 * Check if with has been set
	 * @param  string  $with
	 * @return boolean
	 */
	public function hasWith($with)
	{
		return in_array($with, $this->with);
	}

	/**
	 * Insert one or multiple records by array
	 * @param  object $entity
	 * @param  array $data
	 * @return array|object|boolean
	 */
	public function insert($entity, $data)
	{
		if (array_keys($data) !== range(0, count($data) - 1)) {
			// Single record assoc array
			return $this->update($entity, $data);
		}

		// Creating bulk records
		$entities = array();
		foreach ($data as $item) {
			$entity = $entity->newInstance();
			foreach ($item as $key => $value) {
				$entity->$key = $value;
			}
			$entities[] = $entity;
		}
		return $this->save($entities);
	}

	/**
	 * Update a record by array
	 * @param  object $entity
	 * @param  array $data
	 * @return array|object|boolean
	 */
	public function update($entity, $data)
	{
		foreach ($data as $key => $value) {
			$entity->$key = $value;
		}
		return $this->save($entity);
	}


	/**
	 * Translate entity property names to store column names (or visa versa)
	 * @param  array|string $items
	 * @param  boolean $reverse = false reverse the translation
	 * @param  array $map = null use alternate store attributes map
	 * @return array
	 */
	public function map($items, $reverse = false, $map = null)
	{
		$map = isset($map) ? $map : $this->attributes('map');
		$translated = [];
		$isArray = is_array($items);
		if (!$isArray) $items = [$items];

		if (!$reverse) {
 			// Translate entity property to column name
			foreach ($items as $item) {
				$table = $this->attributes('table');
				if (str_contains($item, '.')) list($table, $item) = explode('.', $item);

				if ($table != $this->attributes('table')) {
					// This column does not belong to this store...but to a subentity. Use that subentities attributes map

					if ($table == 'feeds') $table = 'feed'; //fixme
					if ($table == 'groups') $table = 'group'; //fixme

					$manager = app($this->realNamespace());
					if (is_null($manager->$table)) $manager = app($this->manager->namespace);
					$map = $manager->$table->store->attributes('map');
					$table = $manager->$table->store->attributes('table');
				} else {
					// This columns belongs to this store...use this stores map
					$map = $this->attributes('map');
				}

				if ($item == '*') {
					$translated[] = "$table.$item";
				} else {
					foreach ($map as $property => $options) {
						$column = isset($options['column']) ? $options['column'] : null;
						$filter = isset($options['filter']) ? $options['filter'] : null;
						if ($item == $property) {
							if (isset($column)) {
								if (str_contains($column, '.')) {
									// Map has . in it...use that override instead of default table
									$translated[] = $column;
								} else {
									$translated[] = "$table.$column";
								}
							}
							break;
						}
					}
				}

			}

 		} else {
 			// Translate column name to entity property
 			foreach ($items as $item) {

				$thisMap = $map;
				if (str_contains($item, '.')) list($table, $item) = explode('.', $item);
				if (isset($table)) {
					if ($table != $this->attributes('table')) {
						// This column does not belong to this store...but to a subentity. Use that subentities attributes map
						$manager = app($this->realNamespace()); //Tricky...for inherited entities like VFI client

						// Translate entity into table name (addresses into address)
						foreach ($map as $property => $options) {
							if (isset($options['table'])) {
								if ($options['table'] == $table) {
									$table = $property;
								}
							}
						}

						if (is_null($manager->$table)) $manager = app($this->manager->namespace);
						$table = $manager->$table->store->attributes('entity');
						$thisMap = $manager->$table->store->attributes('map');
					} else {
						$table = $this->attributes('entity');
					}
				}

				if ($item == '*') {
					$translated[] = "$table.$item";
				} else {
	 				foreach ($thisMap as $property => $options) {
		 				if (isset($options['column']) && $options['column'] == $item) {
		 					if ((isset($table)) && $table == $this->attributes('entity') || !isset($table)) {
		 						$translated[] = $property;
		 					} else {
		 						$translated[] = "$table.$property";
		 					}
		 					break;
		 				}
	 				}
 				}
 			}
 		}
		if ($isArray) {
			return $translated;
		} else {
			return empty($translated) ? $items[0] : $translated[0];
		}
	}

	/**
	 * Perform a query transaction
	 * @param  function $callback
	 * @param  $collect = true run through $this->collect()
	 * @return mixed
	 */
	protected function transaction($callback, $collect = true)
	{
		// Perform transaction
		$results = call_user_func($callback);

		// Collect Results
		if ($collect && isset($results)) {
			$results = $this->collect($results);;
		}

		// Reset transaction defaults
		$this->resetQuery();

		return $results;
	}

	/**
	 * Collect and transform store results
	 * @param  array|object $results
	 * @return null|entity|\Illuminate\Support\Collection
	 */
	protected function collect($results)
	{
		if (is_array($results)) {
			// Results is multi row
			$entities = [];
			foreach ($results as $result) {
				#if (is_array($result)) $result = (object) $result;
				$entities[] = $this->transformStore($result);
			}
			return $this->keyByPrimary($entities);

		} else {
			// Results is single row find() one
			return $this->transformStore($results);
		}
	}

	/**
	 * Transform one store record into one entity object
	 * @param  array $store
	 * @return object
	 */
	protected function transformStore($store)
	{
		if (isset($store)) {
			if (isset($this->select)) {
				// Using custom select, cannot return entity, return stdClass
				$entity = new stdClass();

				// Predefine empty $entity object to force order of class properties
				foreach ($store as $key => $value) {
					$mappedKey = $this->map($key, true);
					$entity->$mappedKey = null;
				}
			} else {
				// Using default select (*), transform into entity object
				$entity = $this->newEntity();
			}

			$map = $this->attributes('map');
			foreach ($map as $property => $options) {
				$value = null;
				$column = isset($options['column']) ? $options['column'] : null;
				$filter = isset($options['filter']) ? $options['filter'] : null;

				if (isset($column) && isset($store->$column)) {
					// This is a database column which has been selected
					// Its value is from the actual column or from a filter
					if (isset($filter)) {
						$value = call_user_func($filter, $store);
					} else {
						$value = $store->$column;
					}
				} elseif (!isset($column) && isset($filter)) {

					// This is a virtual column which has been selected
					// Its only value is from a filter
					$value = call_user_func($filter, $store);

				}

				if (property_exists($entity, $property)) {
					$entity->$property = $value;
				}
			}
			// Call a postTransformStore method if exists
			if (method_exists($this, 'postTransformStore')) {
				$this->postTransformStore($entity);
			}
			return $entity;
		}
	}

	/**
	 * Transform one entity object into one store model
	 * @param  object $entity
	 * @return array
	 */
	protected function transformEntity($entity)
	{
		if (isset($entity)) {
			$map = $this->attributes('map');
			$store = [];
			foreach ($entity as $key => $value) {
				foreach ($map as $property => $options) {
					if (isset($options['column']) && $property == $key) {
						if (!isset($options['save']) || $options['save'] == true) {
							$store[$options['column']] = $entity->$key;
							break;
						}
					}
				}
			}
			return $store;
		}
	}

	/**
	 * Handle all bulk with merges
	 * @return \Illuminate\Support\Collection
	 */
	protected function mergeWiths()
	{
		// Save off and reset select before newQuery()
		$select = $this->select; $this->select = null;

		// Run query with joins and wheres to get final initial base result
		$entities = $this->transaction(function() {

			// Include each subentity join method
			foreach ($this->with as $with) {
				$method = 'join'.studly_case($with);
				if (method_exists($this, $method)) {
					$this->$method();
				}
			}

			// Run query with any new joins
			$query = $this->newQuery();

			// Select only main table or ids and duplicated columns between joins will collide
			return $query->select($this->attributes('table').'.*')->get();
		});

		$results = null;
		if (isset($entities)) {
			// Run new subentity query and merge with main query
			foreach ($this->with as $with) {
				$method = 'merge'.studly_case($with);
				if (method_exists($this, $method)) {
					$this->$method($entities);
				}
			}

			// Select columns from collection (post query)
			$results = $this->selectCollection($entities, $this->map($select, true));

			// I could actually fill legacy properties with new ->host->* columns if needed!
			// But they are still accesslbie via lazy legacy if needed
			#$results->map(function($result) {
				#$result->hostname = $result->hostKey;
				#$result->company = $result->host->name;
				#$result->companyGuid = $result->host->guid;
				#$result->server = $result->host->server;
				#$result->serverNum = $result->host->serverNum;
				#unset($result->host);
			#});

		}
		$this->with = null;
		return $results;
	}

	/**
	 * Select columns from a collection (post query)
	 * @param  \Illuminate\Support\Collection $items
	 * @param  array $select must be entity columns, not store columns so reverse map
	 * @return \Illuminate\Support\Collection
	 */
	protected function selectCollection($items, $select)
	{
		if (isset($select)) {
			// Custom select, return modify collection
			$items = $items->map(function($item) use($select) {
				$newItem = new stdClass();
				foreach ($select as $column) {

					if ($column == 'feed.*') $column = 'feeds.*'; //fixme
					if ($column == 'group.*') $column = 'groups.*'; //fixme

					if (str_contains($column, '.*')) {
						// Just remove .* and pickup entire subentity
						$column = head(explode('.', $column));
					}
					if (str_contains($column, '.')) {
						// Sub entity
						list($subentity, $subproperty) = explode('.', $column);
						// Careful. Potential lazy load here.  Ex ->select('address.state')->with(address) the state will lazy load from address
						if (isset($item->$subentity)) {
							if (!isset($newItem->$subentity)) $newItem->$subentity = new stdClass();

							$newItem->$subentity->$subproperty = $item->$subentity->$subproperty; //subclass
						}
					} else {
						$newItem->$column = $item->$column;
					}
				}
				return $newItem;
			});
		}
		return $items;
	}

	/**
	 * Unflatten collection (from address.city to address->city)
	 * @param  \Illuminate\Support\Collection $items
	 * @return \Illuminate\Support\Collection
	 */
	protected function expandCollection($items)
	{
		if (isset($items)) {
			$results = [];
			foreach ($items as $item) {
				$result = new stdClass();
				foreach ($item as $key => $value) {
					if (str_contains($key, '.')) {
						list($key, $subkey) = explode('.', $key);
						if (!isset($result->$key)) $result->$key = new stdClass();
						$result->$key->$subkey = $value;
					} else {
						$result->$key = $value;
					}
				}
				$results[] = $result;
			}
			return collect($results);
		}
	}

	/**
	 * Key entities collection by primary if set, else no keyby
	 * @param  array $entities
	 * @return \Illuminate\Support\Collection|null
	 */
	protected function keyByPrimary($entities)
	{
		if (count($entities) > 0) {
			$primary = $this->map($this->attributes('primary'), true);
			if (property_exists($entities[0], $primary)) {
				// Re-key assoc array by primary
				return collect($entities)->keyBy($primary);
			} else {
				return collect($entities);
			}
		}
	}

	/**
	 * Resets the query properties
	 * @return void
	 */
	protected function resetQuery()
	{
		$this->select = null;
		$this->join = null;
		$this->where = null;
		$this->filter = null;
		$this->orderBy = null;
		$this->limit = null;
	}

	/**
	 * Get one or all store attributes
	 * @param  string $key
	 * @return mixed
	 */
	public function attributes($key = null)
	{
		// Special defaults
		if ($key == 'order_by_dir' && !isset($this->attributes[$key])) {
			return 'asc';
		}

		if (is_null($key)) {
			// Return all attributes
			return $this->attributes;
		} elseif (isset($key) && isset($this->attributes[$key])) {
			// Return individual attribute
			return $this->attributes[$key];
		}
	}

	/**
	 * Get a query builder for this table
	 * @param  string $table = null
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function table($table = null)
	{
		if (is_null($table)) $table = $this->attributes('table');
		return $this->connection->table("$table");
	}

	/**
	 * Get new entity instance maintaining the last used store driver
	 * @return object
	 */
	protected function newEntity()
	{
		$entity = $this->attributes('entity');
		return $this->manager->store($this->storeKey)->$entity->newInstance();
	}

	/**
	 * Get the real master namespace in case of store inheritance
	 * @return string
	 */
	protected function realNamespace()
	{
		// Sometimes I have one class that inherits the entity and store of another
		// Like how Vfi Client is fake and simply inherits Iam Client
		// In this case $vfi->client REAL MASTER namespace should actually be Dynatron\Iam
		// Not Dynatron\Vfi like $this->manager->namespace will reveal.

		// I need the REAL MASTER namespace for cases like events
		// I want to fire true Iam\Client events not Vfi\Client is using $vfi->client
		// or Iam\Client if using $iam->client...both are truely Iam and should fire only
		// Dynatron\Iam events.
		$class = get_class($this);
		$parent = get_parent_class($this);

		if (str_contains($parent, "\\Repository\\")) {
			// Store is not inherited from another namespace
			$namespace = $this->manager->namespace;
		} else {
			// Store is inherited from another namespace
			$tmp = explode('\\', $parent);
			$namespace = $tmp[0].'\\'.$tmp[1];
		}
		return $namespace;
	}

	/**
	 * Fire a store event
	 * @param  string $event
	 * @param  mixed $payload
	 * @param  boolean $halt
	 * @return mixed
	 */
	public function fireEvent($event, $payload = null, $halt = true)
	{
		// Public becuase in some complex situations, I will want to cancel an event, handle myself, then call the final deleted or saved event manually

		// Get event name (ex: dynatron.iam.client.deleting)
		$namespace = strtolower($this->realNamespace());
		$entity = str_replace('_', '-', snake_case($this->attributes('entity')));
		$event = str_replace('\\', '.', "$namespace.$entity.$event");

		// Fire event
		$method = $halt ? 'until' : 'fire';
		return Event::$method($event, [$payload]);
	}

}
