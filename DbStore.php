<?php namespace Mreschke\Repository;

use DB;
use Cache;
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
        // This is a ->join query
        if (isset($this->join)) return $this->getJoinResults($first);

        // This is a ->with query
        if (isset($this->with)) return $this->getWithResults($first);

        // This is a simple query
        return $this->transaction(function () use ($first) {
            // Build the Illumunate\Database\Query\Builder (but don't execute yet)
            $query = $this->newQuery();

            // Check if caching is enabled for this query
            // Now we have all our query params defined in $query, now the cache key will be unique
            if (isset($this->cache)) {
                // Cache key has to be based on ->first() or not
                $cacheKey = $first ? $this->cache['key'].'::first' : $this->cache['key'].'::all';
                return Cache::remember($cacheKey, $this->cache['expires'], function() use($query, $first) {
                    if ($first) return $query->first();
                    return $query->get();
                });
            } else {
                if ($first) {
                    return $query->first();
                } else {
                    return $query->get();
                }
            }
        });
    }

    /**
     * Handle query using with
     * @param boolean $first = false
     * @return \Illuminate\Support\Collection
     */
    protected function getWithResults($first = false)
    {
        // Save off and reset select before newQuery()
        $select = $this->select;
        $this->select = null;

        // Trach original cache, as its reset on ->transaction();
        $parentCache = null;

        // Run query with joins and wheres to get final initial base result
        $entities = $this->transaction(function () use ($first, &$parentCache) {

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

            // Build the Illumunate\Database\Query\Builder (but don't execute yet)
            $query = $this->newQuery();

            // Select only main table or ids and duplicated columns between joins will collide
            // This is a laravel DB level select, not this this DbStore level $select
            $query->select($this->attributes('table').'.*');

            if ($first) {
                // If $first=true we can utilize database level ->first() BUT we still
                // must return as collection for now, as merge methods below expect it

                if (isset($this->cache)) {
                    $this->cache['key'] .= '::first';
                    $parentCache = $this->cache;
                    $results = Cache::remember($parentCache['key'], $this->cache['expires'], function() use($query) {
                        return $query->first();
                    });
                } else {
                    $results = $query->first();
                }
                if (isset($results)) {
                    return collect([$results]);
                } else {
                    return $results;
                }

            } else {
                if (isset($this->cache)) {
                    $this->cache['key'] .= '::all';
                    $parentCache = $this->cache;
                    return Cache::remember($parentCache['key'], $this->cache['expires'], function() use($query) {
                        return $query->get();
                    });
                } else {
                    return $query->get();
                }

            }
        });

        // The above cache() only caches the parent entity, not all ->with() entities
        // Below we merge in all ->with() entities, then cache the entire thing.
        // So there ends up being 2 caches.  One for parent, one for parent+all ->with() merged in.

        // Run new subentity query and merge with main query
        $results = null;
        if (isset($entities)) {

            // Caching enabled
            if (isset($parentCache)) {
                $cacheKey = $parentCache['key'];
                if (isset($this->with)) {
                    $cacheKey .= '::with:';
                    foreach ($this->with as $with) {
                        $cacheKey .= ":".$with;
                    }
                }
                $results = Cache::remember($cacheKey, $parentCache['expires'], function() use(&$entities, $select) {
                    if (isset($this->with)) {
                        foreach ($this->with as $with) {
                            $method = 'merge'.studly_case($with);
                            if (method_exists($this, $method)) {
                                // Dymamically call store function mergeXyz() method which merges in children ->with()
                                $this->$method($entities);
                            }
                        }
                    }
                    // Select columns from collection (post query)
                    return $this->selectCollection($entities, $this->map($select, true));
                });

            // NO caching
            } else {
                if (isset($this->with)) {
                    foreach ($this->with as $with) {
                        $method = 'merge'.studly_case($with);
                        if (method_exists($this, $method)) {
                            // Dymamically call store function mergeXyz() method which merges in children ->with()
                            $this->$method($entities);
                        }
                    }
                }
                // Select columns from collection (post query)
                $results = $this->selectCollection($entities, $this->map($select, true));
            }
        }

        // Reset ->with query builder
        $this->with = null;

        // We applied ->first() at database level for optimization, but we had
        // to still return a collection for merge methods above...now we can ->first on the collection
        if ($first && isset($results)) {
            return $results->first();
        } else {
            return $results;
        }
    }

    /**
     * Handle query using joins
     * @param boolean $first = false
     * @return \Illuminate\Support\Collection
     */
    protected function getJoinResults($first)
    {
        // Custom joins MUST also have custom custom columns or join collisions may occure
        if (isset($this->select)) {
            $results = $this->transaction(function () use ($first) {
                if ($first) {
                    return collect([$this->newQuery()->first()]);
                } else {
                    return $this->newQuery()->get();
                }
            });

            // Unflatten collection (from address.city into address->city)
            $results = $this->expandCollection(collect($results));

            // Key by primary
            // This does happen in transaction(), but expandCollection removes the keyBy
            if ($first) {
                return $this->keyByPrimary($results)->first();
            } else {
                return $this->keyByPrimary($results);
            }

        } else {
            throw new Exception("Must specify a custom select when using joins");
        }
    }

    /**
     * Get a count of query records (null if error counting)
     * @param boolean $isTransaction = true when true, this is a terminating function (will clear query builder), if false it will not clear query builder
     * @return integer|null
     */
    public function count($isTransaction = true, $countColumn = null)
    {
        try {
            // You cannot COUNT at the same time you order_by or SQL errors with
            // Column "tbl_user.email" is invalid in the ORDER BY clause because it is not contained in either an aggregate function or the GROUP BY clause.
            // So CLEAR the default order_by before ->count()...to bypass auto-add in the ->addOrderByQuery() method
            unset($this->attributes['order_by']);

            // Convert countColumn to actual table column name (table.real_column)
            if (isset($countColumn)) $countColumn = $this->map($countColumn, false);

            if ($isTransaction) {
                // Clear query builder after running counts (a terminating ->count() method like ->get())
                $filteredRecords = $this->transaction(function () use($countColumn) {
                    if (isset($countColumn)) {
                        return $this->newQuery()->count($countColumn);
                    } else {
                        return $this->newQuery()->count();
                    }
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
     * Get min value of a given column
     * @param  string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->transaction(function () use ($column) {
            return $this->newQuery()->min($this->map($column));
        });
    }

    /**
     * Get max value of a given column
     * @param  string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->transaction(function () use ($column) {
            return $this->newQuery()->max($this->map($column));
        });
    }

    /**
     * Start a new query builder
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newQuery()
    {
        // Build new query from builder parameters

        // Get driver for this connection (mysql for example)
        $driver = strtolower($this->connection->getConfig('driver'));

        // Add in a USE INDEX() statement only if MySQL driver
        if ($driver == 'mysql' && isset($this->useIndex)) {
            // Get table name from attributes
            $table = $this->attributes('table');

            // Get indexes from ->useIndex query builder method
            $indexes = implode(',', $this->useIndex);

            // Build custom RAW FROM statement
            $query = $this->connection->table(
                DB::raw("$table USE INDEX ($indexes)")
            );
        } else {
            // Use standard ->table from Laravel DB Query Builder
            $query = $this->table();
        }

        // Add in all other query builder items
        $this->addSelectQuery($query);
        $this->addJoinQuery($query);
        $this->addFilterQuery($query);
        $this->addWhereQuery($query);
        $this->addOrderByQuery($query);
        $this->addGroupByQuery($query);
        $this->addLimitQuery($query);

        // Once all queries are added we can deduce a unique cache key from ->queryHash()
        if (isset($this->cache)) {
            $this->buildCacheKey();
        }

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

                // Repo->Laravel operator conversion
                if ($operator == '!like') $operator = 'not like';
                if ($operator == 'not in') $operator = '!in';

                if ($operator == 'in') {
                    $query->whereIn($column, $value);
                } elseif ($operator == '!in') {
                    $query->whereNotIn($column, $value);
                } elseif ($operator == 'null') {
                    if ($value) {
                        $query->whereNull($column);
                    } else {
                        $query->whereNotNull($column);
                    }
                } else {
                    // This works for laravel DB operators: =, !=, <>, >=, <=, <>, like
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
            foreach ($this->orderBy as $orderBy) {
                $query->orderBy($orderBy['column'], $orderBy['direction']);
            }
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
     * Save one or multiple entity objects
     * @param  array|object $entities array of entities, or single entity
     * @return array|object|boolean
     */
    public function save($entities)
    {
        if ($this->fireEvent('saving', $entities) === false) {
            return false;
        }

        // Get Store attributes
        $primaryColumn = $this->attributes('primary');
        $primary = $this->map($primaryColumn, true); // entity ID, NOT db column
        $increments = $this->attributes('increments');

        if (is_array($entities)) {

            // Save bulk records
            $records = [];
            foreach ($entities as $entity) {
                // If $primary is null, remove it so auto-increment will work in PostgreSQL
                if (isset($primary)) {
                    if (!isset($entity->$primary) || ($increments && $entity->$primary == 0)) {
                        unset($entity->$primary);
                    }
                }
                $records[] = $this->transformEntity($entity);
            }

            if ($this->fireEvent('creating', $entities) === false) {
                return false;
            }

            $this->table()->insert($records);
            $this->fireEvent('created', $entities);

        } else {

            // If $primary is null, remove it so auto-increment will work in PostgreSQL
            if (isset($primary)) {
                if (!isset($entities->$primary) || ($increments && $entities->$primary == 0)) {
                    unset($entities->$primary);
                }
            }

            if (isset($primary)) {
                $found = false;
                if (isset($entities->$primary)) {
                    // Smart insert or update based on primary key selection
                    $handle = $this->table()->where($primaryColumn, $entities->$primary);

                    // Check if record exists by executing TOP 1 on it (->first())
                    $found = !is_null($handle->select($primaryColumn)->first());

                    // Remove the limit 1 added from the ->first() above, for some reason it sticks
                    // And causes an UPDATE xyz LIMIT 1 statement, which is usually fine, but errors on some MySQL platforms (PlanetScale)
                    $handle->limit = null;
                }

                // Updating an existing record
                if ($found) {
                    // Translate entity, removing any updatable=false entries
                    $record = $this->transformEntity($entities, true);

                    if ($this->fireEvent('updating', $entities) === false) {
                        return false;
                    }
                    // Remove primary for proper update statement
                    unset($record[$primaryColumn]);

                    // Update record
                    $handle->update($record);
                    $this->fireEvent('updated', $entities);

                // Insert a new record
                } else {

                    // Translate entity, removing any save=false entries, but keeping any updatable=false entries
                    $record = $this->transformEntity($entities);

                    if ($this->fireEvent('creating', $entities) === false) {
                        return false;
                    }

                    if ($increments) {
                        $entities->$primary = $this->table()->insertGetId($record);
                    } else {
                        $this->table()->insert($record);
                    }
                    $this->fireEvent('created', $entities);
                }
            } else {
                // Table has no primary (probably a linkage table)
                // Translate entity, removing any save=false entries, but keeping any updatable=false entries
                $record = $this->transformEntity($entities);

                // Insert into table
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
