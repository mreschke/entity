<?php namespace Mreschke\Repository;

use stdClass;
use Illuminate\Database\ConnectionInterface;
use Mreschke\Dbal\Mssql;


// This is under construction and does NOT work yet
// this will be for mreschke/dbal store
// For now I just use laravel db even while connecting to mssql

/**
 * Mreschke Dbal Mssql Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class MssqlStore extends Store implements StoreInterface
{

	/**
	 * The database connection instance
	 * @var \Mreschke\Dbal\Mssql
	 */
	protected $connection;

	/**
	 * Create a new instance of this store
	 * @param ConnectionInterface $connection
	 */
	public function __construct($manager, $storeKey, Mssql $connection)
	{
		$this->manager = $manager;
		$this->storeKey = $storeKey;
		$this->connection = $connection;

		// Initialize store
		$this->init();
	}

	/**
	 * Find a record by id or key
	 * @param  mixed $id
	 * @return object
	 */
	public function find($id)
	{
		// Determine ID Columns
		$idColumn = $this->attributes('primary');
		if (!is_numeric($id)) $idColumn = 'key';

		// Get result
		return $this->transaction(function() use($idColumn, $id) {
			$query =  $this->newQuery()->where($idColumn, $id);
			dump($query);
			dd($query->first());
		});
	}


	/**
	 * Get all records for an entity
	 * @return \Illuminate\Support\Collection
	 */
	public function all()
	{
		// Get results
		return $this->transaction(function() {
			return $this->newQuery()->get();
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
			return collect($this->newQuery()->lists($this->map($column), $this->map($key)))->sort();
		}, false);
	}

	/**
	 * Get a count of query records
	 * @return integer
	 */
	public function count()
	{
		return $this->newQuery()->count();
	}

	/**
	 * Start a new query builder
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newQuery()
	{
		$columns = $this->select ?: ['*'];
		$query = $this->table()->select($columns);
		if (isset($this->where)) {
			foreach ($this->where as $where) {
				$query->where($where['column'], $where['operator'], $where['value']);
			}
		}

		// orderBy
		if (isset($this->orderBy)) {
			$query->orderBy($this->orderBy['column'], $this->orderBy['direction']);
		}

		// limit/offset
		if (isset($this->limit)) {
			$query->skip($this->limit['skip'])->take($this->limit['take']);
		}

		return $query;
	}

	/**
	 * Save one or multiple entity objects
	 * @param  array|object $entities
	 * @return array|object
	 */
	public function save($entities)
	{
		if (is_array($entities)) {

			// Save bulk records
			$records = [];
			foreach ($entities as $entity) {
				$records[] = $this->transformEntity($entity);
			}
			$this->table()->insert($records);

		} else {

			// Save a single record
			$record = $this->transformEntity($entities);

			// Get Store attributes
			$primary = $this->attributes('primary');
			$increments = $this->attributes('increments');

			// Query handle
			$handle = $this->table()->where($primary, $entities->$primary);

			if (is_null($handle->first())) {
				if ($increments) {
					$entities->$primary = $this->table()->insertGetId($record);
				} else {
					$this->table()->insert($record);
				}
			} else {
				$handle->update($record);
			}

		}
		return $entities;
	}

	public function delete()
	{
		// ?????????????????
	}

	/**
	 * Truncate all records
	 * @return void
	 */
	public function truncate()
	{
		$this->connection->statement("SET foreign_key_checks=0");
		$this->table()->truncate();
		$this->connection->statement("SET foreign_key_checks=1");
	}

}
