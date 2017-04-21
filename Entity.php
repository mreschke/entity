<?php namespace Mreschke\Repository;

use Event;
use Mreschke\Helpers\Str;

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
     * Set distinct statement
     * @param  boolean $distinct = true
     * @return $this
     */
    public function distinct($distinct = true)
    {
        $this->store->distinct($distinct);
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
    #    $this->store->join($table, $one, $operator, $two);
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
     * @param  mixed   $value
     * @return \Illuminate\Support\Collection
     */
    public function whereAttribute($column, $value = null)
    {
        $this->store->whereAttribute($column, $value);
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
     * Add a groupBy column to the query
     * @param  string $column
     * @return $this
     */
    public function groupBy($column)
    {
        $this->store->groupBy($column);
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
     * @param  string $key = null
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        return $this->store->pluck($column, $key);
    }

    /**
     * Get a count of query records (null if error counting)
     * @param boolean $isTransaction = true when true, this is a terminating function (will clear query builder), if false it will not clear query builder
     * @return integer|null
     */
    public function count($isTransaction = true)
    {
        return $this->store->count($isTransaction);
    }

    /**
     * Set the withCount statement to add a count to the select statement for the query
     * @param  boolean $withCount = true
     * @return $this
     */
    public function withCount($withCount = true)
    {
        $this->store->withCount($withCount);
        return $this;
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
        $entityKey = $this->$primary;

        // Not working on individual entity
        if (is_null($entityKey) || is_null($this->store->properties('attributes'))) {
            return null;
        }

        if (isset($key)) {

            // Attribute keys are always underscore
            $key = snake_case(str_replace("-", "_", str_slug($key)));

            if (isset($value)) {

                // Set a single attribute
                $originalValue = $this->attributes($key);
                if ($this->fireEvent('attributes.saving', ['entity' => $this, 'key' => $key, 'value' => $value, 'original' => $originalValue]) === false) {
                    return false;
                }

                $attributes = $this->manager->attribute->where('entity', $entity)->where('entityKey', $entityKey)->first();
                if (isset($attributes)) {
                    // Update a single key inside the attributes json blob
                    $blob = json_decode($attributes->value, true);
                    $blob[$key] = $value;
                    $attributes->value = json_encode($blob);
                    $attributes->save();
                } else {
                    // Create the initial attributes json blob with this first key
                    $this->manager->attribute->create([
                        'entity' => $entity,
                        'entityKey' => $entityKey,
                        'value' => json_encode([$key => $value])
                    ]);
                }

                // Update index (will smart insert/update because key is unique)
                $this->manager->attributeIndex->create([
                    'key' => "$entity-$entityKey-$key",
                    'entity' => $entity,
                    'entityKey' => $entityKey,
                    'index' => $key,
                    'value' => $value
                ]);

                #dd($this);

                $this->fireEvent('attributes.saved', ['entity' => $this, 'key' => $key, 'value' => $value, 'original' => $originalValue]);

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
                $this->attributes = null;
                $items = $this->manager->attribute->where('entity', $entity)->where('entityKey', $entityKey)->first();
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
        $entityKey = $this->$primary;

        // Deleting a key
        $value = $this->attributes($key);
        if ($this->fireEvent('attributes.deleting', ['entity' => $this, 'key' => $key, 'value' => $value]) === false) {
            return false;
        }

        $attributes = $this->manager->attribute->where('entity', $entity)->where('entityKey', $entityKey)->first();
        if (isset($attributes)) {
            $blob = json_decode($attributes->value, true);
            unset($blob[$key]);
            if (empty($blob)) {
                // Attributes are empty now, delete entire row
                $attributes->delete();
            } else {
                // Delete this one value from attributes
                $attributes->value = json_encode($blob);
                $attributes->save();
            }

            // Delete attribute index
            $index = $this->manager->attributeIndex->where('entity', $entity)->where('entityKey', $entityKey)->where('index', $key)->first();
            if (isset($index)) {
                $index->delete();
            }

            $this->fireEvent('attributes.deleted', ['entity' => $this, 'key' => $key, 'value' => $value]);

            // Refresh attributes
            unset($this->attributes);
            $this->attributes();
            return $value;
        }
    }

    /**
     * Set attribute if value, else forget (delete) attribute
     * NOTE: a zero (0) value will be considered empty and therefore forget the attribute.
     * @param string $key
     * @param mixed $value
     */
    public function setOrForgetAttribute($key, $value)
    {
        if ($value) {
            $this->attributes($key, $value);
        } else {
            $this->forgetAttribute($key);
        }
    }

    /**
     * Get a list of all or one entities properties and optionally an single option inside that property
     * @param string $property = null
     * @param $string $option = null
     * @return array|null
     */
    public function properties($property = null, $option = null)
    {
        $items = $this->store->properties();
        if (isset($property)) {
            // Get one property
            if (isset($items[$property])) {
                $property = $items[$property];
                if (isset($option)) {
                    // Get a single option
                    return isset($property[$option]) ? $property[$option] : null;
                } else {
                    // Get all options
                    return $property;
                }
            }
        } else {
            // Get all properties
            $properties = [];
            foreach ($items as $property => $options) {
                $properties[$property] = [
                    'property' => $property,
                    'column' => isset($options['column']) ? $options['column'] : null,
                    'type' => isset($options['type']) ? $options['type'] : null,
                    'size' => isset($options['size']) ? $options['size'] : null,
                    'round' => isset($options['round']) ? $options['round'] : null,
                    'nullable' => isset($options['nullable']) ? $options['nullable'] : null,
                    'default' => isset($options['default']) ? $options['default'] : null,
                    'trim' => isset($options['trim']) ? $options['trim'] : true,
                    'entity' => isset($options['entity']) ? $options['entity'] : null,
                    'table' => isset($options['table']) ? $options['table'] : null,
                    'filter' => isset($options['filter']) ? true : false,
                    'likable' => isset($options['likable']) ? $options['likable'] : false,
                    'save' => isset($options['save']) ? $options['save'] : true,
                ];
            }
            return $properties;
        }
    }

    /**
     * Format each entity column according to its store attribute rules
     * @return void
     */
    public function format()
    {
        $map = $this->store->properties();
        foreach ($map as $property => $options) {

            // No property type, no formatter
            if (!isset($options['type'])) continue;

            // Get options and defaults
            $type = $options['type'];
            $size = isset($options['size']) ? $options['size'] : null;
            $round = isset($options['round']) ? $options['round'] : null;
            $nullable = isset($options['nullable']) ? $options['nullable'] : false;
            $default = isset($options['default']) ? $options['default'] : null;
            $trim = isset($options['trim']) ? $options['trim'] : true;
            $ucwords = isset($options['ucwords']) ? $options['ucwords'] : false;
            $lowercase = isset($options['lowercase']) ? $options['lowercase'] : false;
            $uppercase = isset($options['uppercase']) ? $options['uppercase'] : false;
            $multiline = isset($options['multiline']) ? $options['multiline'] : false;
            $utf8 = isset($options['utf-8']) ? $options['utf-8'] : false;

            // Get properties value
            $value = $this->$property;

            // Determine empty string
            // Logic differs for numerics, booleans, null or empty strings
            $empty = false;
            if (is_numeric($value)) {
                // Numerics are never empty, even 0
                $empty = false;
            } elseif ($value === false) {
                // False booleans are not empty
                $empty = false;
            } elseif (!isset($value)) {
                // Null string are empty
                $empty = true;
            } elseif ($value == '') {
                // Empty strings are empty
                $empty = true;
            }

            // Handle EMPTY values
            if ($empty) {
                switch ($type) {
                    case "string":
                    case "json":
                    case "date":
                    case "datetime":
                        $value = $default ?: ($nullable ? null : '');
                        break;
                    case "integer":
                        $value = $default ?: ($nullable ? null : 0);
                        break;
                    case "decimal":
                        $value = $default ?: ($nullable ? null : 0.0);
                        break;
                    case "boolean":
                        $value = $default ?: ($nullable ? null : false);
                        break;
                    default:
                        $value = $default ?: ($nullable ? null : '');
                }
            } else {
                if ($type == 'json') {
                    $value = json_encode($value);
                }

                // If not multiline, strip carriage returns
                if (!$multiline) {
                    $value = strtr($value, ["\r\n"=>" ", "\n"=>" ", "\r"=>" "]);
                }

                // Don't allow UTF-8
                if (!$utf8) {
                    $encoding = mb_detect_encoding($value);
                    if ($encoding != "ASCII") {
                        #$value = utf8_encode($value);
                        $value = Str::toAscii($value);
                    }
                }

                // Trim if true (default=true)
                if ($trim) {
                    $value = trim($value);
                }

                switch ($type) {
                    case "string":
                        if ($lowercase) {
                            $value = strtolower($value);
                        }
                        if ($uppercase) {
                            $value = strtoupper($value);
                        }
                        if ($ucwords) {
                            $value = ucwords(strtolower($value));
                        }
                        break;
                    case "datetime":
                        $value = date("Y-m-d H:i:s", strtotime($value));
                        break;
                    case "date":
                        $value = date("Y-m-d", strtotime($value));
                        break;
                    case "integer":
                        $value = (int) $value;
                        break;
                    case "decimal":
                        if (isset($size)) {
                            // Add one to offset the decimal point in calculations
                            $options['size'] ++;
                            $size ++;
                        }
                        if (isset($round)) {
                            $value = round((float) $value, $round);
                        } else {
                            $value = (float) $value;
                        }
                        break;
                    case "boolean":
                        $value = (bool) $value;
                        break;
                }

                if (isset($size) && strlen($value) > $size) {
                    // Column size overflow
                    $this->fireEvent('overflow', array_merge($options, ['value' => $value, 'value_size' => strlen($value), 'repository' => $this->repository]));
                    $value = substr($value, 0, $size);
                }
            }
            $this->$property = $value;
        }
    }

    /**
     * Insert one or multiple records by array
     * @param  \Illuminate\Support\Collection|array $data
     * @return array|object|boolean
     */
    public function insert($data)
    {
        return $this->store->insert($this->newInstance(), $data);
    }

    /**
     * Alias to insert
     * @param  \Illuminate\Support\Collection|array $data
     * @return array|object|boolean
     */
    public function create($data)
    {
        return $this->insert($data);
    }

    /**
     * Update this object by array
     * @param  array|object $data
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
     * Delete one or multiple objects from the store
     * @param  \Illuminate\Support\Collection|array $data
     * @param  object|null $data
     */
    public function delete($data = null)
    {
        if (!isset($data)) {
            // Either ->delete() on single entity or query builder
            $primary = $this->store->map($this->store->attributes('primary'), true);
            if ($this->$primary) {
                // Single entity
                $data = $this;
            } else {
                // From query builder (most efficient)
                // Careful, make sure there really was NO argument passed or passing a NULL $data
                // could results in a select * query building being run which will wipe entire table
                if (func_num_args() == 1) {
                    // Passed in a NULL array, not trying to query build, so cancel
                    return;
                } else {
                    // Truely requesting a query builder delete
                    $data = null;
                }
            }
        }
        $this->store->delete($this->newInstance(), $data);
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
    public function with($properties)
    {
        if (!is_array($properties)) {
            $properties = func_get_args();
        }
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
    public function __set($key, $value)
    {
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
        $store = $this->manager(false)->getStoreInstance($this->repository);
        if (is_null($store)) {
            // Manager has never actually been loaded as a singleton.
            // This happens if you store an entity as a session, then try to do work
            // on that stored entity before the manager singleton has ever loaded
            $entity = lcfirst(studly_case(last(explode('\\', get_class($this)))));
            $this->manager(false)->$entity;
            $store = $this->manager(false)->getStoreInstance($this->repository);
        }
        return $store;
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
     * @param boolean $inherited = true, get the inherited namespace if this entity inherits from another
     * @return object
     */
    protected function manager($inherited = true)
    {
        if ($inherited) {
            // Get the inherited manager.
            // Example: if using $vfi->client, we actually get Dynatron/Iam here, NOT Dynatron/Vfi becuase Vfi inherits from Iam
            return app($this->realNamespace());
        } else {
            // Get the first non-inherited manager.
            // Example: if using $vfi->client, we actually get Dynatron/Vfi here, even though Vfi inherts from Iam, we want Vfi
            $class = get_class($this);
            $baseClass = substr($class, 0, strrpos($class, '\\'));
            return app($baseClass);
        }
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
        $event = 'repository.'.str_replace('\\', '.', "$namespace.$entity.$event");

        // Fire event
        $method = $halt ? 'until' : 'fire';
        return Event::$method($event, [$payload]);
    }

    /**
     * Lazy load a property with the given callback
     * @param  \Closire $callback
     * @return mixed
     */
    public function lazyLoad($callback)
    {
        // Entity is not even populated with data yet
        if (!$this->isPopulated) {
            return null;
        }

        // Property name is the calling function
        $property = debug_backtrace()[1]['function'];

        if (property_exists($this, $property)) {
            // Already lazy loaded, just return the already gathered value
            return $this->$property;
        } else {
            // Values not calculated yet.  Load it one time
            $this->$property = call_user_func($callback);
            return $this->$property;
        }
    }

    /**
     * Check if this entity has been populated with data (vs an empty fresh instantiated entity)
     * @return boolean
     */
    public function isPopulated()
    {
        $primary = $this->store->map($this->store->attributes('primary'), true);
        if (isset($this->$primary)) {
            return true;
        }
        return false;
    }

    /**
     * Clone this entire entity object
     * @return object
     */
    public function duplicate()
    {
        // Would have named function 'clone', but it's reserved in PHP < 7.0
        return clone $this;
    }

    /**
     * Remove all subentities from this $entity
     * Goes well with ( $entity)->simplify() or ->duplicate()->simplify() to create a second simplified object
     * @return object
     */
    public function simplify()
    {
        foreach ($this as $key => $value) {
            if (is_array($value) || is_object($value)) {
                unset($this->$key);
            }
        }
        return $this;
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
