<?php namespace Mreschke\Repository;

use DB;
use stdClass;
use Exception;
use Illuminate\Database\ConnectionInterface;

/**
 * Laravel DB Query Builder Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
abstract class DbStore extends Store implements StoreInterface
{
    /**
     * The database connection instance
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * Create a new instance of this store
     * @param ConnectionInterface $connection
     */
    public function __construct($manager, $storeKey, ConnectionInterface $connection)
    {
        $this->manager = $manager;
        $this->storeKey = $storeKey;
        $this->connection = $connection;

        // Initialize store
        $this->init();
    }

    /**
     * Get all records for an entity
     * @param boolean $first = false use ->get() or ->first()
     * @return \Illuminate\Support\Collection
     */
    public function get($first = false)
    {
        if (isset($this->join)) {
            // Custom joins MUST also have custom custom columns or join collisions may occure
            if (isset($this->select)) {
                $results = $this->transaction(function () {
                    return $this->newQuery()->get();
                });

                // Unflatten collection (from address.city into address->city)
                $results = $this->expandCollection(collect($results));

                // Key by primary
                // This does happen in transaction(), but expandCollection removes the keyBy
                return $this->keyByPrimary($results);
            } else {
                throw new Exception("Must specify a custom select when using joins");
            }
            return null;
        }

        if (isset($this->with)) {
            // Custom ->get function
            return $this->mergeWiths($first);
        }

        return $this->transaction(function () use ($first) {
            #dump($this->newQuery()->toSQL());
            if ($first) {
                return $this->newQuery()->first();
            } else {
                return $this->newQuery()->get();
            }
        });
    }

    /**
     * Get a count of query records (null if error counting)
     * @param boolean $isTransaction = true when true, this is a terminating function (will clear query builder), if false it will not clear query builder
     * @return integer|null
     */
    public function count($isTransaction = true)
    {
        try {
            // You cannot COUNT at the same time you order_by or SQL errors with
            // Column "tbl_user.email" is invalid in the ORDER BY clause because it is not contained in either an aggregate function or the GROUP BY clause.
            // So CLEAR the default order_by before ->count()...to bypass auto-add in the ->addOrderByQuery() method
            unset($this->attributes['order_by']);

            if ($isTransaction) {
                // Clear query builder after running counts (a terminating ->count() method like ->get())
                $filteredRecords = $this->transaction(function () {
                    return $this->newQuery()->count();
                }, false);
            } else {
                // Do NOT clear query builder after running counts
                return $this->newQuery()->count();
            }
        } catch (Exception $e) {
            // If error, means we are filtering on a subentity column of a ->with()...which will break counts and is not supported
            // This means you cannot have any pager counts if filtering on subentity using ->with().  Either don't allow, or revert to primitive pager
            // or don't use server side paging (don't use buildDB, build from full collection instead).
            // Or if in same entity, use custom ->join() instead of ->with()
            $filteredRecords = null;
        }
        return $filteredRecords;
    }

    /**
     * Start a new query builder
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newQuery()
    {
        // Build new query
        $query = $this->table();
        $this->addSelectQuery($query);
        $this->addJoinQuery($query);
        $this->addFilterQuery($query);
        $this->addWhereQuery($query);
        $this->addOrderByQuery($query);
        $this->addGroupByQuery($query);
        $this->addLimitQuery($query);
        return $query;
    }

    /**
     * Add selects to query builder
     * @param \Illuminate\Database\Query\Builder $query object is by reference
     */
    protected function addSelectQuery($query)
    {
        // Convert id into clients.id as id
        // Convert host.serverNum into hosts.server_num as host.serverNum ...

        // This one is odd becuase if column is in this entity, use COLUMN names
        // not property names because we have to transformStore next which requires column style.
        // BUT if column is subentity, then use entity names not colum names because we have already transformed the subentity
        // ex:
        /*array:9 [▼
          0 => "id"
          1 => "name"
          2 => "addressID"
          3 => "host.key"
          4 => "host.serverNum"
          5 => "address.address"
          6 => "address.city"
          7 => "address.stateKey"
          8 => "disabled"
        ]*/
        // Translates to
        /*
        array:9 [▼
          0 => "clients.id as id"
          1 => "clients.name as name"
          2 => "clients.address_id as address_id"
          3 => "hosts.key as host.key"
          4 => "hosts.server_num as host.serverNum"
          5 => "addresses.address as address.address"
          6 => "addresses.city as address.city"
          7 => "addresses.state as address.stateKey"
          8 => "clients.disabled as disabled"
        ]
         */

        $selects = [];
        if (isset($this->select)) {
            foreach ($this->select as $select) {
                $mappedSelect = $this->map($select, true); // host.serverNum
                list($table, $item) = explode('.', $select);

                if (str_contains($mappedSelect, '.')) {
                    list($table, $item) = explode('.', $mappedSelect);
                    $selects[] = "$select as $table.$item";
                } else {
                    $selects[] = "$select as $item";
                }
            }
        }

        // Add withCount (count column)
        if ($this->withCount) {
            $selects[] = DB::raw('count(*) as count');
        }

        $query->select($selects ?: ['*']);

        // Add distinct
        if ($this->distinct) {
            $query->distinct();
        }
    }

    /**
     * Add joins to query builder
     * @param \Illuminate\Database\Query\Builder $query object is by reference
     */
    protected function addJoinQuery($query)
    {
        if (isset($this->join)) {
            foreach ($this->join as $join) {
                $query->join($join['table'], $join['one'], $join['operator'], $join['two']);
            }
        }
    }

    /**
     * Add global filter to query builder
     * @param \Illuminate\Database\Query\Builder $query object is by reference
     */
    protected function addFilterQuery($query)
    {
        if (isset($this->filter)) {
            $likable = null;
            $table = $this->attributes('table');
            $map = $this->attributes('map');
            foreach ($map as $property => $options) {
                if (isset($options['column']) && isset($options['likable']) && $options['likable'] == true) {
                    $likable[] = $table.".".$options['column'];
                }
            };
            if (isset($likable)) {
                $query->where(function ($query) use ($likable) {
                    foreach ($likable as $like) {
                        $query->orWhere($this->map($like), 'like', "%$this->filter%");
                    }
                });
            } else {
                // No columns are likable, just use primary with = (not like)
                $primary = $this->attributes('primary');
                if (isset($primary)) {
                    $query->where($primary, $this->filter);
                }
            }
        }
    }

    /**
     * Add where to query builder
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $subentity = null only add wheres with this subeneity
     */
    protected function addWhereQuery($query, $subentity = null)
    {
        // Ex with both filter and where: select * from `dms` where (`key` like ? or `name` like ?) and `disabled` = ?
        if (isset($this->where)) {
            foreach ($this->where as $where) {
                #$table = $where['table'];
                $column = $where['column'];
                $operator = $where['operator'];
                $value = $where['value'];

                if ($operator == 'in') {
                    #$query->whereIn("$table.$column", $value);
                    $query->whereIn($column, $value);
                } elseif ($operator == 'null') {
                    if ($value) {
                        $query->whereNull($column);
                    } else {
                        $query->whereNotNull($column);
                    }
                } else {
                    #$query->where("$table.$column", $operator, $value);
                    $query->where($column, $operator, $value);
                }
            }
        }
    }

    /**
     * Add order by to query builder
     * @param \Illuminate\Database\Query\Builder $query object is by reference
     */
    protected function addOrderByQuery($query)
    {
        if (isset($this->orderBy)) {
            $query->orderBy($this->orderBy['column'], $this->orderBy['direction']);
        } else {
            // Default order by
            if (isset($this->attributes['order_by'])) {
                $query->orderBy($this->attributes('order_by'), $this->attributes('order_by_dir'));
            }
            // Else omit the order by statement completely which will use tables clustered index as order
        }
    }

    /**
     * Add group by to query builder
     * @param Illuminate\Database\Query\Builder $query object is by reference
     */
    protected function addGroupByQuery($query)
    {
        if (isset($this->groupBy)) {
            foreach ($this->groupBy as $groupBy) {
                $query->groupBy($groupBy);
            }
        }
    }

    /**
     * Add limit to query builder
     * @param \Illuminate\Database\Query\Builder $query object is by reference
     */
    protected function addLimitQuery($query)
    {
        if (isset($this->limit)) {
            $query->skip($this->limit['skip'])->take($this->limit['take']);
        }
    }

    /**
     * Handle all bulk with merges
     * @param boolean $first = false
     * @return \Illuminate\Support\Collection
     */
    protected function mergeWiths($first = false)
    {
        // Save off and reset select before newQuery()
        $select = $this->select;
        $this->select = null;

        // Run query with joins and wheres to get final initial base result
        $entities = $this->transaction(function () use ($first) {

            // Include each subentity join method (if exists)
            // Yes, even ->with() will call joinXyz AND mergeXyz if joinXyz exists
            if (isset($this->with)) {
                foreach ($this->with as $with) {
                    $method = 'join'.studly_case($with);
                    if (method_exists($this, $method)) {
                        $this->$method();
                    }
                }
            }

            // Run query with any new joins
            $query = $this->newQuery();

            // Select only main table or ids and duplicated columns between joins will collide
            $query->select($this->attributes('table').'.*');

            if ($first) {
                // If $first=true we can utilize database level ->first() BUT we still
                // must return as collection for now, as merge methods below expect it
                return collect([$query->first()]);
            } else {
                return $query->get();
            }
        });

        $results = null;
        if (isset($entities)) {
            // Run new subentity query and merge with main query
            if (isset($this->with)) {
                foreach ($this->with as $with) {
                    $method = 'merge'.studly_case($with);
                    if (method_exists($this, $method)) {
                        $this->$method($entities);
                    }
                }
            }

            // Select columns from collection (post query)
            $results = $this->selectCollection($entities, $this->map($select, true));
        }
        $this->with = null;

        // We applied ->first() at database level for optimization, but we had
        // to still return a collection for merge methods above...now we can ->first on the collection
        if ($first) {
            return $results->first();
        } else {
            return $results;
        }
    }

    /**
     * Save one or multiple entity objects
     * @param  array|object $entities array of entities, or single entity
     * @return array|object|boolean
     */
    public function save($entities)
    {
        if ($this->fireEvent('saving', $entities) === false) {
            return false;
        }

        if (is_array($entities)) {

            // Save bulk records
            $records = [];
            foreach ($entities as $entity) {
                $records[] = $this->transformEntity($entity);
            }
            if ($this->fireEvent('creating', $entities) === false) {
                return false;
            }
            $this->table()->insert($records);
            $this->fireEvent('created', $entities);
        } else {

            // Save a single record
            $record = $this->transformEntity($entities);

            // Get Store attributes
            $primary = $this->attributes('primary');
            $increments = $this->attributes('increments');

            if (isset($primary)) {
                // Smart insert or update based on primary key selection
                $handle = $this->table()->where($primary, $entities->$primary);
                if (is_null($handle->first())) {
                    // Insert a new record
                    if ($this->fireEvent('creating', $entities) === false) {
                        return false;
                    }
                    if ($increments) {
                        $entities->$primary = $this->table()->insertGetId($record);
                    } else {
                        $this->table()->insert($record);
                    }
                    $this->fireEvent('created', $entities);
                } else {
                    // Updating an existing record
                    if ($this->fireEvent('updating', $entities) === false) {
                        return false;
                    }
                    $handle->update($record);
                    $this->fireEvent('updated', $entities);
                }
            } else {
                // Table has no primary (probably a linkage table)
                $this->table()->insert($record);
            }
        }
        $this->fireEvent('saved', $entities);
        return $entities;
    }

    /**
     * Update bulk records using query builder
     * @param  array $data
     * @return void
     */
    protected function amend($data)
    {
        if ($this->fireEvent('updating') === false) {
            return false;
        }
        if (isset($data) && is_array($data)) {

            // Translate entity column names to store column names
            foreach ($data as $column => $value) {
                unset($data[$column]);
                $data[$this->map($column)] = $value;
            }

            // Bulk update records!
            $this->transaction(function () use ($data) {
                $this->newQuery()->update($data);
            });
        }
        $this->fireEvent('updated');
    }

    /**
     * Delete one or multiple entity objects
     * @param  array|object|null $entities array of entities, or single entity
     * @return array|object|boolean
     */
    protected function destroy($entities = null)
    {
        $primary = $this->attributes('primary');
        if ($this->fireEvent('deleting', $entities) === false) {
            return false;
        }

        if (!isset($entities)) {
            // Delete bulk records from a query builder (most efficient)
            $this->transaction(function () {
                $this->newQuery()->delete();
            });
        } elseif (is_array($entities)) {
            // Delete bulk records from array
            if (isset($primary)) {
                // Delete by primary key IN statement
                $ids = [];
                foreach ($entities as $entity) {
                    $transformed = $this->transformEntity($entity);
                    $ids[] = $transformed[$primary];
                }
                $this->table()->whereIn($primary, $ids)->delete();
            } else {
                // Table has no primary, probably a linkage table
                // Have to where on all columns to find match
                $query = $this->table();
                foreach ($entities as $entity) {
                    $transformed = $this->transformEntity($entity);
                    $query->orWhere(function ($query) use ($transformed) {
                        foreach ($transformed as $column => $value) {
                            $query->where($column, $value);
                        }
                    });
                }
                $query->delete();
            }
        } else {
            // Delete a single record
            if (isset($primary)) {
                // Delete by primary key
                $entityPrimary = $this->map($primary, true);
                $id = $entities->$entityPrimary;

                // This will throw exception on errors like integrity constraint...good
                $this->table()->where($primary, $id)->delete();
            } else {
                // Table has no primary, probably a linkage table
                // Have to where on all columns to find match
                $query = $this->table();
                $transformed = $this->transformEntity($entities);
                foreach ($transformed as $column => $value) {
                    $query->where($column, $value);
                }
                $query->delete();
            }
        }
        $this->fireEvent('deleted', $entities);
    }

    /**
     * Truncate all records
     * @return void
     */
    public function truncate()
    {
        if ($this->fireEvent('truncating') === false) {
            return false;
        }
        // This SET statement fails with sqlite
        try {
            $this->connection->statement("SET foreign_key_checks=0");
        } catch (Exception $e) {
            // do nothing
        }

        $this->table()->truncate();

        try {
            $this->connection->statement("SET foreign_key_checks=1");
        } catch (Exception $e) {
            // do nothing
        }

        $this->fireEvent('truncated');
    }
}
