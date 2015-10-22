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
	 * The distinct statement
	 * @var boolean
	 */
	protected $distinct;

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
	 * @return $this
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
		return $this;
	}

	/**
	 * Set a new select statement
	 * @param  array
	 * @return $this
	 */
	public function select($columns)
	{
		$columns = is_array($columns) ? $columns : func_get_args();
		$this->select = $this->map($columns);
		return $this;
	}

	/**
	 * Set distinct statement
	 * @param  boolean $distinct = true
	 * @return $this
	 */
	public function distinct($distinct = true)
	{
		$this->distinct = $distinct;
		return $this;
	}

	/**
	 * Add additional select(s) to the query
	 * @param  array
	 * @return $this
	 */
	public function addSelect($columns)
	{
		$columns = is_array($columns) ? $columns : func_get_args();
		$this->select[] = $this->map($columns);
		return $this;
	}

	/**
	 * Add a where clause to the query
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return $this
	 */
	public function where($column, $operator = null, $value = null)
	{
		// Only allow operators that ALL stores can utilize (no like, between...)
		$operators = ['=', '>', '<', '!=', '>=', '<=', 'like', 'in', 'null'];
		if (func_num_args() >= 2) {
			if (func_num_args() == 2) {
				list($value, $operator) = array($operator, '=');
			}
			if (!in_array(strtolower($operator), $operators)) $operator = "=";

			$this->where[] = [
				'column' => $this->map($column),
				'operator' => $operator,
				'value' => $value,
			];
		}
		return $this;
	}

	/**
	 * Set the search query filter
	 * @param  string
	 * @return $this
	 */
	public function filter($query)
	{
		$this->filter = $query;
		return $this;
	}

	/**
	 * Set the orderBy column and direction for the query
	 * @param  string $column
	 * @param  string $direction
	 * @return $this
	 */
	public function orderBy($column, $direction)
	{
		$this->orderBy = [
			'column' => $this->map($column),
			'direction' => $direction
		];
		return $this;
	}

	/**
	 * Set the limit and offset for the query
	 * @param  integer $offset
	 * @param  integer $limit
	 * @return $this
	 */
	public function limit($offset, $limit)
	{
		$this->limit = ['skip' => $offset, 'take' => $limit];
		return $this;
	}

	/**
	 * Set the offset for the query
	 * @param  integer $offset
	 * @return $this
	 */
	public function skip($offset)
	{
		$this->limit['skip'] = $offset;
		return $this;
	}

	/**
	 * Set the limit for the query
	 * @param  integer $limit
	 * @return $this
	 */
	public function take($limit)
	{
		$this->limit['take'] = $limit;
		return $this;
	}

	/**
	 * Add a with relationship for the query
	 * @param  string $properties
	 * @return $this
	 */
	public function with($properties)
	{
		if (!is_array($properties)) $properties = func_get_args();
		foreach ($properties as $property) {
			if (is_null($this->with) || !in_array($property, $this->with)) {
				$this->with[] = $property;
			}
		}
		return $this;
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
	 * Find a record by id or key
	 * @param  mixed $id
	 * @return object
	 */
	public function find($id)
	{
		if (isset($id)) {
			// Determine ID Columns
			$idColumn = $this->map($this->attributes('primary'), true);
			if (!is_numeric($id)) $idColumn = 'key'; //fixme
			return $this->where($idColumn, $id)->first();
		}
	}

	/**
	 * Get first single record in query
	 * @return object
	 */
	public function first()
	{
		$results = $this->get();
		if (isset($results)) {
			return $results->first();
		}
	}

	/**
	 * Get a key/value list
	 * @param  string $column
	 * @param  string $key
	 * @return \Illuminate\Support\Collection
	 */
	public function lists($column, $key)
	{
		return $this->select($column, $key)->get()->lists($column, $key);
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
	 * Add a where attributes clause to the query
	 * @param  string  $column
	 * @param  mixed   $value
	 * @return \Illuminate\Support\Collection
	 */
	public function whereAttribute($column, $value = null)
	{
		$entity = $this->attributes('entity');
		$primary = $this->map($this->attributes('primary'), true);

		// Query attribute index
		if (isset($value)) {
			// Query by actual attribute value
			$index = $this->entityManager($entity)->attributeIndex->select('entityKey')->where('entity', $entity)->where('index', $column)->where('value', $value);
		} else {
			// Query if attribute exists at all
			$index = $this->entityManager($entity)->attributeIndex->select('entityKey')->where('entity', $entity)->where('index', $column);	
		}
		
		// Get array of matching entityKeys
		$index = $index->get();
		if (isset($index)) {
			// Add where to entity query matching these found entityKeys
			$this->where($primary, 'in', $index->lists('entityKey'));
		} else {
			// Want to return nothing, no attribute match, no entity return
			$this->where($primary, null);
		}
		return $this;
	}

	/**
	 * Merge entity attributes (not store attributes) subentity with client
	 * @param  \Illuminate\Support\Collection $entities
	 */
	protected function mergeAttributes($entities)
	{
		$primary = $this->map($this->attributes('primary'), true);
		$entity = $this->attributes('entity');

		// Attributes not available/enabled for this entity if store relationship not defined
		if (is_null($this->properties('attributes'))) return;

		$attributes = $this->entityManager($entity)->attribute->where('entity', $entity)->where('entityKey', 'in', $entities->lists($primary)->all())->get();
		if (isset($attributes)) {
			foreach ($attributes as $attribute) {
				$entities[$attribute->entityKey]->attributes = json_decode($attribute->value, true);
			}
		}
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
 			// Translate entity.property into store table.column (ex: addressID to address_id)
			foreach ($items as $item) {
				$thisMap = $map;
				$table = $this->attributes('table');

				// Get entity attributes from selection name (id, clients.*...)
				if (str_contains($item, '.')) {
					// Map is potentially from another entity
					list($table, $item) = explode('.', $item);

					if ($table != $this->attributes('table')) {
						// Get map and table name form another entity
						$entity = $table;
						if (isset($map[$entity])) {
							$options = $map[$entity];
							if (isset($options['entity'])) {
								$table = $options['table'];
								$entity = $options['entity'];
							}
						}
						$manager = $this->entityManager($entity);
						$thisMap = $manager->$entity->store->attributes('map');
						$table = $manager->$entity->store->attributes('table');
					}
				}

				// Translate property=>column
				if ($item == '*') {
					$translated[] = "$table.$item";
				} else {
					foreach ($thisMap as $property => $options) {
						$column = isset($options['column']) ? $options['column'] : null;
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

			// Translate store table.column into entity.property (ex: address_id into addressID)
 			foreach ($items as $item) {
				$thisMap = $map;
				$table = null;
				if (str_contains($item, '.')) {
					list($table, $item) = explode('.', $item);

					if ($table != $this->attributes('table')) {
						// From another entity
						$entity = $table;
						foreach ($map as $property => $options) {
							if (isset($options['table'])) {
								if ($options['table'] == $table) {
									$table = $property;
									$entity = $options['entity'];
								}
							}
						}
						$manager = $this->entityManager($entity);
						$thisMap = $manager->$entity->store->attributes('map');
					} else {
						// From same entity, reset table
						$table = null;
					}
				}

				// Translate table.column=>entity.property
				if ($item == '*') {
					$translated[] = "$table.$item";
				} else {
	 				foreach ($thisMap as $property => $options) {
		 				if (isset($options['column']) && $options['column'] == $item) {
		 					if (!isset($table)) {
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
			// FIXME, why if empty return items[0]?? can't remember
			// test later once you get proper unit tests
			// at this point, if you entered ->where('actual_column') instead of
			// ->where('entityColumn') it actually STILL works.  This means you can use
			// either property or column in your wheres, which I DON'T want as this lets
			// you bypass the entire point of an entity mappper.
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

			$unmappedKeys = get_object_vars($store);
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
					unset($unmappedKeys[$column]);
					$entity->$property = $value;
				}
			}

			// If using custom select or join there will be other unmapped keys
			if (isset($this->select)) {
				foreach ($unmappedKeys as $key => $value) {
					$entity->$key = $value;
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
	 * Select columns from a collection (post query)
	 * @param  \Illuminate\Support\Collection $items
	 * @param  array $select must be entity columns, not store columns so reverse map
	 * @return \Illuminate\Support\Collection
	 */
	public function selectCollection($items, $select)
	{
		if (isset($select)) {
			// Custom select, return modify collection
			$items = $items->map(function($item) use($select) {
				$newItem = new stdClass();
				foreach ($select as $column) {

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
	 * @param  string $key = null
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
	 * Get one or all store map properties and options
	 * @param  string $property = null
	 * @param  string $option = null
	 * @return mixed
	 */
	public function properties($property = null, $option = null)
	{
		if (isset($property)) {
			$map = $this->attributes('map');
			if (isset($map[$property])) {
				if (isset($option) && isset($map[$property][$option])) {
					return $map[$property][$option];
				}
				return $map[$property];
			}
		} else {
			return $this->attributes('map');
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
	 * Get manager for entity
	 * @param  string $entity
	 * @return object
	 */
	protected function entityManager($entity)
	{
		// Try this realNamespace first, in case of inheritence and same entity like vfi/iam clients
		$manager = app($this->realNamespace());
		if (is_null($manager->$entity)) {
			// Entity not found in realNamespace, try inherited namespace
			$manager = app($this->manager->namespace);
			if (is_null($manager->$entity)) {
				// Entity not found
				return null;
			}
		}
		return $manager;
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
