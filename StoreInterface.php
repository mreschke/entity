<?php namespace Mreschke\Repository;

/**
 * Provides a contractual interface for Mreschke\Repository\Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
interface StoreInterface
{

	/**
	 * Set a new select statement
	 * @param  array|func_get_args $columns
	 * @return $this
	 */
	public function select($columns);

	/**
	 * Add additional select(s) to the query
	 * @param  array
	 * @return $this
	 */
	public function addSelect($columns);

	/**
	 * Add a where clause to the query
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return $this
	 */
	public function where($column, $operator = null, $value = null);

	/**
	 * Set the search query filter
	 * @param  string
	 * @return $this
	 */
	public function filter($query);

	/*
	 * Set the orderBy column and direction for the query
	 * @param  string $column
	 * @param  string $direction
	 * @return $this
	 */
	public function orderBy($column, $direction);

	/**
	 * Set the limit and offset for the query
	 * @param  integer $offset
	 * @param  integer $limit
	 * @return $this
	 */
	public function limit($offset, $limit);

	/**
	 * Set the with get finalizer override method
	 * @param  string $properties
	 * @return $this
	 */
	public function with($properties);

	/**
	 * Check if with has been set
	 * @param  string  $with
	 * @return boolean
	 */
	public function hasWith($with);

	/**
	 * Find a record by id or key
	 * @param  mixed $id
	 * @return object
	 */
	public function find($id);

	/**
	 * Get first single record in query
	 * @return object
	 */
	public function first();

	/**
	 * Get all records for an entity
	 * @return \Illuminate\Support\Collection
	 */
	public function get();

	/**
	 * Get a key/value list
	 * @param  string $column
	 * @param  string $key
	 * @return \Illuminate\Support\Collection
	 */
	public function lists($column, $key);

	/**
	 * Get a count of query records (null if error counting)
	 * @return integer|null
	 */
	public function count();

	/**
	 * Save one or multiple entity objects
	 * @param  array|object $entities
	 * @return array|object
	 */
	public function save($entities);

	/**
	 * Insert one or multiple records by array
	 * @param  object $entity
	 * @param  array $data
	 * @return object
	 */
	public function insert($entity, $data);

	/**
	 * Update a record by array
	 * @param  object $entity
	 * @param  array $data
	 * @return object
	 */
	public function update($entity, $data);

	/**
	 * Delete one or multiple records by array or collection
	 * @param  object $entity
	 * @param  \Illuminate\Support\Collection|array|null $data
	 * @return array|object|boolean
	 */
	public function delete($entity, $data = null);

	/**
	 * Truncate all records
	 * @return void
	 */
	public function truncate();

	/**
	 * Translate entity property names to store column names (or visa versa)
	 * @param  array|string $items
	 * @param  boolean $reverse = false reverse the translation
	 * @return array
	 */
	public function map($items, $reverse = false);

	/**
	 * Get one or all store attributes
	 * @param  string $key
	 * @return mixed
	 */
	public function attributes($key = null);

}
