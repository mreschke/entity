<?php namespace Mreschke\Repository;

use App;
use Event;
use Mreschke\Helpers\String;

/**
 * Entity
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class Entity
{
	/**
	 * Repository classname (keep public)
	 * @var string
	 */
	public $repository;

	/**
	 * Create a new entity instance
	 * @param string $storeClassname
	 */
	public function __construct($storeClassname)
	{
		// Set the repository classname
		$this->repository($storeClassname);
	}

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
		$this->store->join($table, $one, $operator, $two);
		return $this;
	}

	/**
	 * Set a new select statement
	 * @param  array|func_get_args $columns
	 * @return $this
	 */
	public function select($columns)
	{
		$columns = is_array($columns) ? $columns : func_get_args();
		$this->store->select($columns);
		return $this;
	}

	/**
	 * Select columns from a collection (post query)
	 * @param  \Illuminate\Support\Collection $items
	 * @param  array $select must be entity columns, not store columns so reverse map
	 * @return \Illuminate\Support\Collection
	 */
	public function selectCollection($items, $select)
	{
		return $this->store->selectCollection($items, $select);
	}

	/**
	 * Add additional select(s) to the query
	 * @param  array
	 * @return void
	 */
	public function addSelect($columns)
	{
		$columns = is_array($columns) ? $columns : func_get_args();
		$this->store->addSelect($columns);
		return $this;
	}

	#public function join($table, $one, $operator, $two)
	#{
	#	$this->store->join($table, $one, $operator, $two);
		#return $this;
	#}

	/**
	 * Add a where clause to the query
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return $this
	 */
	public function where($column, $operator = null, $value = null)
	{
		if (func_num_args() >= 2) {
			if (func_num_args() == 2) {
				list($value, $operator) = array($operator, '=');
			}
			$this->store->where($column, $operator, $value);
		}
		return $this;
	}

	/**
	 * Find all entities matching this attribute
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return \Illuminate\Support\Collection
	 */
	public function whereAttribute($column, $operator = null, $value = null)
	{
		if (func_num_args() >= 2) {
			if (func_num_args() == 2) {
				list($value, $operator) = array($operator, '=');
			}
			$this->store->whereAttribute($column, $operator, $value);
		}
		return $this;
	}

	/**
	 * Set the search query filter
	 * @param  string $query
	 * @return $this
	 */
	public function filter($query)
	{
		$this->store->filter($query);
		return $this;
	}

	/**
	 * Add an orderBy column and direction to the query
	 * @param  string $column
	 * @param  string $direction = 'asc'
	 * @return $this
	 */
	public function orderBy($column, $direction = 'asc')
	{
		$this->store->orderBy($column, $direction);
		return $this;
	}

	/**
	 * Add an offset and limit to the query
	 * @param  integer $offset
	 * @param  integer $limit
	 * @return $this
	 */
	public function limit($offset, $limit)
	{
		$this->store->limit($offset, $limit);
		return $this;
	}

	/**
	 * Add an offset to the query
	 * @param  integer $offset
	 * @return $this
	 */
	public function skip($offset)
	{
		$this->store->skip($offset);
		return $this;
	}

	/**
	 * Add a limit to the query
	 * @param  integer $limit
	 * @return $this
	 */
	public function take($limit)
	{
		$this->store->take($limit);
		return $this;
	}

	/**
	 * Find a record by id or key
	 * @param  mixed $id = null
	 * @return object
	 */
	public function find($id = null)
	{
		return $this->store->find($id);
	}

	/**
	 * Get first single record in query
	 * @return object
	 */
	public function first()
	{
		return $this->store->first();
	}

	/**
	 * Get all records for an entity
	 * @return \Illuminate\Support\Collection
	 */
	public function get()
	{
		return $this->store->get();
	}

	/**
	 * Alias to get
	 * @return \Illuminate\Support\Collection
	 */
	public function all()
	{
		return $this->get();
	}

	/**
	 * Get a key/value list
	 * @param  string $column
	 * @param  string $key
	 * @return \Illuminate\Support\Collection
	 */
	public function lists($column, $key)
	{
		return $this->store->lists($column, $key);
	}

	/**
	 * Get a count of query records (null if error counting)
	 * @return integer|null
	 */
	public function count()
	{
		return $this->store->count();
	}

	/**
	 * Get or set entity attributes
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed
	 */
	public function attributesXXX_FIRST_MYSQL($key = null, $value = null)
	{
		$primary = $this->store->map($this->store->attributes('primary'), true);
		$entity = $this->store->attributes('entity');
		$entityID = $this->$primary;

		// Not working on individual entity or attributes not available/enabled for this entity
		if (is_null($entityID) || !array_key_exists('attributes', $this->store->attributes('map')))	return 's';

		if (isset($key)) {

			// Attribute keys are always underscore
			$key = snake_case(str_replace("-", "_", str_slug($key)));

			if (isset($value)) {

				// Set a single attribute
				$originalValue = $this->attributes($key);
				if ($this->fireEvent('attributes.saving', ['key' => $key, 'value' => $value, 'original' => $originalValue]) === false) return false;

				$this->entityManager()->attribute->create([
					'key' => "$entity:$entityID:$key",
					'entity' => $entity,
					'entityID' => $entityID,
					'index' => $key,
					'value' => $value
				]);

				$this->fireEvent('attributes.saved', ['key' => $key, 'value' => $value, 'original' => $originalValue]);

				// Refresh attributes
				unset($this->attributes);
				return $this->attributes($key);

			} else {

				// Getting a single attribute
				if (isset($this->attributes[$key]) && $this->attributes[$key] != "") {
					return $this->attributes[$key];
				} else {
					return null;
				}
			}


		} else {

			// Get all attributes
			if (!isset($this->attributes)) {
				$items = $this->entityManager()->attribute->where('entity', $entity)->where('entityID', $entityID)->get();
				if (isset($items)) {
					$this->attributes = [];
					foreach ($items as $item) {
						// I don't think I should worry about serialization, do it manually for attributes
						#$this->attributes[$item->index] = $item->value ? String::unserialize($item->value) : null;
						#$this->attributes[$item->index] = $item->value ?: null;
						$this->attributes[$item->index] = $item->value;

					}
				}
			}
			return @$this->attributes;

		}
	}

	/**
	 * Get or set entity attributes
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed
	 */
	public function attributes($key = null, $value = null)
	{
		$primary = $this->store->map($this->store->attributes('primary'), true);
		$entity = $this->store->attributes('entity');
		$entityID = $this->$primary;

		// Not working on individual entity or attributes not available/enabled for this entity
		if (is_null($entityID) || !array_key_exists('attributes', $this->store->attributes('map')))	return 's';

		if (isset($key)) {

			// Attribute keys are always underscore
			$key = snake_case(str_replace("-", "_", str_slug($key)));

			if (isset($value)) {

				// Set a single attribute
				$originalValue = $this->attributes($key);
				if ($this->fireEvent('attributes.saving', ['key' => $key, 'value' => $value, 'original' => $originalValue]) === false) return false;

				$attributes = $this->entityManager()->attribute->where('entity', $entity)->where('entityID', $entityID)->first();
				if (isset($attributes)) {
					// Update attributes
					$blob = json_decode($attributes->value, true);
					$blob[$key] = $value;
					$attributes->value = json_encode($blob);
					$attributes->save();
				} else {
					// Create attributes
					$this->entityManager()->attribute->create([
						'entity' => $entity,
						'entityID' => $entityID,
						'value' => json_encode([$key => $value])
					]);
				}

				$this->fireEvent('attributes.saved', ['key' => $key, 'value' => $value, 'original' => $originalValue]);

				// Refresh attributes
				unset($this->attributes);
				return $this->attributes($key);

			} else {

				// Getting a single attribute
				if (isset($this->attributes[$key]) && $this->attributes[$key] != "") {
					return $this->attributes[$key];
				} else {
					return null;
				}
			}


		} else {

			// Get all attributes
			if (!isset($this->attributes)) {
				$items = $this->entityManager()->attribute->where('entity', $entity)->where('entityID', $entityID)->first();
				if (isset($items)) {
					$this->attributes = json_decode($items->value, true);
				}
			}
			return @$this->attributes;

		}
	}




	/**
	 * Forget an entity attribute
	 * @param  string $key
	 * @return mixed
	 */
	public function forgetAttribute($key)
	{
		$primary = $this->store->map($this->store->attributes('primary'), true);
		$entity = $this->store->attributes('entity');
		$entityID = $this->$primary;

		// Not working on individual entity or attributes not available/enabled for this entity
		if (is_null($entityID) || !array_key_exists('attributes', $this->store->attributes('map')))	return;

		// Deleting a key
		$value = $this->attributes($key);
		if ($this->fireEvent('attributes.deleting', ['key' => $key, 'value' => $value]) === false) return false;

		$attribute = $this->entityManager()->attribute->where('entity', $entity)->where('entityID', $entityID)->where('index', $key)->first();
		if (isset($attribute)) {
			$attribute->delete();
		}

		$this->fireEvent('attributes.deleted', ['key' => $key, 'value' => $value]);

		// Refresh attributes
		unset($this->attributes);
		$this->attributes();
		return $value;
	}

	/**
	 * Remove all entity attributes
	 * @return mixed
	 */
	public function truncateAttributes()
	{
		// I want to loop and delete, so that all individual forget events are fired
		unset($this->attributes);
		$attributes = $this->attributes();
		if (isset($attributes)) {
			foreach ($this->attributes as $key => $value) {
				$this->forgetAttribute($key);
			}
		}
		return $attributes;
	}

	/**
	 * Insert one or multiple records by array
	 * @param  array $data
	 * @return array|object|boolean
	 */
	public function insert($data)
	{
		return $this->store->insert($this->newInstance(), $data);
	}

	/**
	 * Alias to insert
	 * @param  array $data
	 * @return array|object|boolean
	 */
	public function create($data)
	{
		return $this->insert($data);
	}

	/**
	 * Update this object by array
	 * @param  array $data
	 * @return array|object|boolean
	 */
	public function update($data)
	{
		return $this->store->update($this, $data);
	}

	/**
	 * Save this object to the store
	 * @return array|object|boolean
	 */
	public function save()
	{
		return $this->store->save($this);
	}

	/**
	 * Delete this object from the store
	 */
	public function delete()
	{
		$this->store->delete($this);
	}

	/**
	 * Truncate all records
	 * @return void
	 */
	public function truncate()
	{
		$this->store->truncate();
	}

	/**
	 * Load these additional properties (query the lazy loads)
	 * ex: $package = find(42)->with('client');
	 * @param  array $properties list of properties to query
	 * @return $this
	 */
	public function with($properties) {
		if (!is_array($properties)) $properties = func_get_args();
		$primary = $this->store->map($this->store->attributes('primary'), true);

		if ($this->$primary) {
			foreach ($properties as $property) {
				// Single entity defined, using entity level ->with
				if (method_exists($this, $property)) {
					$this->$property();
				}
			}
		} else {
			// Entity not defined, using store level ->with
			$this->store->with($properties);
		}

		return $this;
	}

	/**
	 * Magic get method to get properties
	 * @param  string $key property name
	 * @return mixed
	 */
	public function __get($key)
	{
		if (method_exists($this, "$key")) {
			// Custom method exists, use it
			return $this->{"{$key}"}();
		} elseif (property_exists($this, $key)) {
			return $this->$key;
		}
	}

	/**
	 * Magic set method to set properties
	 * @param string $key property name
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		if (method_exists($this, "set$key")) {
			return $this->{"set{$key}"}($value);
		}
		$this->$key = $value;
	}

	/**
	 * Get the entity store repository
	 * @return object
	 */
	protected function store()
	{
		return $this->manager->getStoreInstance($this->repository);
	}

	/**
	 * Get or set the store repository classname
	 * @return string
	 */
	protected function repository($storeClassname = null)
	{
		if (isset($storeClassname)) {
			$this->repository = $storeClassname;
		}
		return $this->repository;
	}

	/**
	 * Get manager for entity
	 * @return object
	 */
	protected function entityManager()
	{
		return app($this->realNamespace());
	}

	/**
	 * Get the real master namespace in case of entity inheritance
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
		$parent = get_parent_class($this);
		$tmp = explode('\\', $parent);
		return $tmp[0].'\\'.$tmp[1];
	}

	/**
	 * Fire an entity event
	 * @param  string $event
	 * @param  mixed $payload
	 * @param  boolean $halt
	 * @return mixed
	 */
	protected function fireEvent($event, $payload = null, $halt = true)
	{
		// Get event name (ex: dynatron.iam.client.deleting)
		$namespace = strtolower($this->realNamespace());
		$entity = str_replace('_', '-', snake_case($this->store->attributes('entity')));
		$event = str_replace('\\', '.', "$namespace.$entity.$event");

		// Fire event
		$method = $halt ? 'until' : 'fire';
		return Event::$method($event, [$payload]);
	}

	/**
	 * Get this instance
	 * @return self
	 */
	public function getInstance()
	{
		return $this;
	}

	/**
	 * Get new instance
	 * @return entity
	 */
	public function newInstance()
	{
		return new static($this->repository());
	}

}
