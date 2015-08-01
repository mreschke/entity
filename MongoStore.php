<?php namespace Mreschke\Repository;

use MongoDB;
use MongoId;
use stdClass;

/**
 * MongoDB Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class MongoStore extends Store implements StoreInterface
{
	/**
	 * The database connection instance
	 * @var \MongoDB
	 */
	protected $connection;

	/**
	 * Create a new instance of this store
	 * @param ConnectionInterface $connection
	 */
	public function __construct($manager, $storeKey, MongoDB $connection)
	{
		$this->manager = $manager;
		$this->storeKey = $storeKey;
		$this->connection = $connection;

		// Initialize store
		$this->init();

		// Ensure collection indexes
		foreach ($this->attributes('indexes') as $index) {
			$this->table()->ensureIndex($index['key'], $index['options']);
		}
	}

	/**
	 * Find a record by id or key
	 * @param  mixed $id
	 * @return object
	 */
	public function find($id)
	{
		#dump('----exec mongo find');
		// Determine ID column
		$idColumn = '_id';
		if ($this->attributes('increments') && strlen($id) <> 24) {
			$idColumn = $this->attributes('primary');
		}

		// Get result
		return $this->transaction(function() use($idColumn, $id) {
			return $this->table()->findOne([$idColumn => $id], $this->select ?: []);
		});
	}

	/**
	 * Get first single record in query
	 * @return object
	 */
	public function first()
	{
		return $this->transaction(function() {
			return $this->table()->findOne($this->buildWhereAnd(), $this->select ?: []);
		});
	}

	/**
	 * Get all records for an entity
	 * @return \Illuminate\Support\Collection
	 */
	public function get()
	{
		// Get results
		return $this->transaction(function() {
			$results = $this->table()->find($this->buildWhereAnd(), $this->select ?: []);
			#$results = $this->table()->find();
			#dd(array('name' => array('$in' => array('Joe', 'Wendy'))));
			#dd(array( '$and' => array(array(' class' =>12), array('marks'=>70))));
			dd($this->buildWhereAnd());
			#$results = $this->table()->find([], $this->select ?: []);
			$x = null;
			foreach ($results as $r) {
				$x[] = $r;
			}
			dd($x);
			#$results = $this->table()->find([], $this->select ?: []);
			#dd($results);
			return $results;
		});
	}

	/**
	 * Get a key/value list
	 * @param  string $column
	 * @param  string $key
	 * @return \Illuminate\Support\Collection
	 */
	public function lists($column, $key)
	{
		return $this->transaction(function() use($column, $key) {
			// This is a cursor, so need to itterate and convert to assoc by $keyMap
			$results = $this->table()->find($this->buildWhereAnd(), [$column, $key]);

			$keyMap = $this->map($key);
			$columnMap = $this->map($column);

			$lists = [];
			foreach ($results as $result) {
				$lists[$result[$keyMap]] = $result[$columnMap];
			}
			return collect($lists)->sort();
		}, false);
	}

	/**
	 * Get a count of query records
	 * @return integer
	 */
	public function count()
	{
		// fixme
	}

	/**
	 * Build mongo whereAnd query array
	 */
	protected function buildWhereAnd()
	{
		// FIXME, does not work for > < >= <= ... and more
		$where = [];
		$andWhere = null;
		$inWhere = null;
		if (isset($this->where)) {
			foreach ($this->where as $w) {
				extract($w);
				if ($operator == 'in') {
					#$inWhere[$column]['$in'][] = $value;
					if (!is_array($value)) $value = [$value];
					$andWhere[] = [$column => ['$in' => $value]];
				} else {
					$andWhere[] = [$column => $value];
				}
			}
			if (isset($andWhere)) $where['$and'] = $andWhere;
			#if (isset($inWhere)) $where['$in'] = $inWhere;

		}
		return $where;
	}

	/**
	 * Build mongo whereIn query array
	 */
	protected function buildWhereIn($column, $value)
	{
		$where = [];
		if (isset($value)) {
			foreach ($value as $w) {
				$addWhere[] = [$column => $w];
			}
			$where = array('$in' => $addWhere);
		}
		return $where;
	}

	/**
	 * Save one or multiple entity objects
	 * @param  array|object $entities
	 * @return array|object|boolean
	 */
	public function save($entities)
	{
		if ($this->fireEvent('saving', $entities) === false) return false;

		// Get Store attributes
		$primary = $this->attributes('primary');
		$increments = $this->attributes('increments');

		if (is_array($entities)) {

			// Save bulk records
			$records = [];
			foreach ($entities as $entity) {
				$record = $this->transformEntity($entity);

				if (!$increments) {
					$record['_id'] = $record[$primary];
				} else {
					unset($record['_id']);
				}
				$records[] = $record;
			}
			if ($this->fireEvent('creating', $entities) === false) return false;

			$this->table()->batchInsert($records);

			$this->fireEvent('created', $entities);

		} else {

			// Save a single record
			$record = $this->transformEntity($entities);

			if ($result = $this->table()->findOne([$primary => $record[$primary]])) {
				// Updating an existing record
				if ($this->fireEvent('updating', $entities) === false) return false;

				$this->table()->update([$primary => $record[$primary]], $record);

				$this->fireEvent('updated', $entities);
			} else {
				// Insert a new record
				if ($this->fireEvent('creating', $entities) === false) return false;

				if ($increments) {
					unset($record['_id']);
					$this->table()->insert($record, ['fsync' => true]);
					$entities->$primary = (string) $record['_id'];
				} else {
					$this->table()->insert($record);
				}

				$this->fireEvent('created', $entities);
			}
		}
		$this->fireEvent('saved', $entities);
		return $entities;
	}

	/**
	 * Delete this object from the store
	 * @param  object $entity
	 */
	public function delete($entity)
	{
		// ????????????????? don't forget deleting and deleted events
	}

	/**
	 * Truncate all records
	 * @return void
	 */
	public function truncate()
	{
		if ($this->fireEvent('truncating') === false) return false;
		$this->table()->drop();
		$this->fireEvent('truncated');
	}

	/**
	 * Collect and transform store results
	 * @param  array|object $results
	 * @return entity|\Illuminate\Support\Collection
	 */
	protected function collect($results)
	{
		// Mongo requires this collect override because a cursor always is_object
		if (is_object($results)) {
			// Results is multi row (MongoCursor object)
			$entities = [];
			foreach ($results as $result) {
				$entities[] = $this->transformStore((object) $result);
			}

			$primary = $this->map($this->attributes('primary'), true);
			if (isset($entities[0]->$primary)) {
				// Re-key assoc array by primary
				return collect($entities)->keyBy($primary);
			} else {
				return collect($entities);
			}
		} else {
			// Results is single row findOne()
			return $this->transformStore((object) $results);
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
		return $this->connection->$table;
	}

}
