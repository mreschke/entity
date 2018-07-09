<?php namespace Mreschke\Repository;

use MongoClient;

/**
 * Entity Repository Manager
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Manager
{
    /**
     * The application instance
     * @var \Illuminate\Foundation\Application
     */
    protected $ioc;

    /**
     * Base namespace
     * @var string
     */
    protected $namespace;

    /**
     * The array of resolvable entity classes
     * @var array
     */
    protected $resolvable;

    /**
     * The cached array of resolved entities
     * @var array
     */
    protected $entities;

    /**
     * The cached array of resolved stores
     * @var array
     */
    protected $stores;

    /**
     * One time store override
     * @var string
     */
    protected $storeKey;

    /**
     * Create a new manager instance
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($ioc)
    {
        $this->ioc = $ioc;
    }

    /**
     * Get an entity instance
     * @param  string $entityKey
     * @return object
     */
    protected function resolveEntity($entityKey)
    {
        // Get entity classname
        $entityClassname = $this->entityClassname($entityKey);

        // Get entity store instance
        $storeKey = $this->storeKey();
        $storeClassname = $this->storeClassname($storeKey, $entityKey);
        $store = $this->resolveStore($storeKey, $storeClassname, $entityKey, $entityClassname);

        // Create new entity instance if not found in cache
        $entityCacheKey = "$storeKey-$entityKey";
        if (!isset($this->entities[$entityCacheKey])) {
            $this->entities[$entityCacheKey] = $this->createEntity($entityClassname, $storeClassname);
        }

        // Cache debug
        #dump(array_keys($this->entities), array_keys($this->stores));

        // Return resolved entity class
        return $this->entities[$entityCacheKey];
    }

    /**
     * Get a store instance
     * @param  string $storeKey
     * @param  string $storeClassname
     * @param  string $entityKey
     * @param  string $entityClassnmae
     * @return object
     */
    protected function resolveStore($storeKey, $storeClassname, $entityKey, $entityClassname)
    {
        // Create new entity store instance if not found in cache
        if (!isset($this->stores[$storeClassname])) {
            $config = $this->entityConfig($storeKey, $entityKey);
            $driver = $config['driver'];

            // Check for custom store factory first
            $factory = "create".studly_case($driver).studly_case($entityKey)."Store";
            if (!method_exists($this, $factory)) {
                $factory = "create".studly_case($driver)."Store";
            }

            // Call the store factory
            $this->stores[$storeClassname] = $this->$factory($storeKey, $storeClassname, $config);
        }

        // Reset one time storeKey
        $this->storeKey = null;

        // Return resolved entity store class
        return $this->stores[$storeClassname];
    }

    /**
     * Create a new entity
     * @param  string $entityClassname
     * @param  mixed $storeClassname
     * @return object
     */
    protected function createEntity($entityClassname, $storeClassname)
    {
        return new $entityClassname($storeClassname);
    }

    /**
     * Create a new db store instance of the given entity store
     * @param  string $storeClassname entity store classname
     * @param  array $config
     * @return object
     */
    protected function createDbStore($storeKey, $storeClassname, $config)
    {
        // Make a database connection
        $connection = $this->ioc['db']->connection($config['connection']);

        // Create a new store instance
        return new $storeClassname($this, $storeKey, $connection);
    }

    /**
     * Create a new mongo store instance of the given entity store
     * @param  string $storeClassname entity store classname
     * @param  array $config
     * @return object
     */
    protected function createMongoStore($storeKey, $storeClassname, $config)
    {
        // Make a mongo connection
        $database = $config['database'];
        $mongo = new MongoClient("mongodb://".$config['host'].":".$config['port']);
        $connection = $mongo->$database;

        // Create a new store instance
        return new $storeClassname($this, $storeKey, $connection);
    }

    /**
     * Create a new HTTP store instance of the given entity store
     * @param  string $storeClassname entity store classname
     * @param  array $config
     * @return object
     */
    protected function createHttpStore($storeKey, $storeClassname, $config)
    {
        dd('Manager.php of mr/repo, createHttpStore()');
        // FIXME, left off here

        // Make a mongo connection
        #$database = $config['database'];
        #$mongo = new MongoClient("mongodb://".$config['host'].":".$config['port']);
        #$connection = $mongo->$database;

        // Create a new store instance
        #return new $storeClassname($this, $storeKey, $connection);
    }

    /**
     * Get entity classname from entity key
     * @param  string $entityKey
     * @return string
     */
    protected function entityClassname($entityKey)
    {
        return $this->namespace.'\\'.studly_case($entityKey);
    }

    /**
     * Get store classname from entityKey and storeKey
     * @param  string $storeKey
     * @param  string $entityKey
     * @return string
     */
    protected function storeClassname($storeKey, $entityKey)
    {
        $driver = $this->entityConfig($storeKey, $entityKey)['driver'];
        return $this->namespace.'\\Stores\\'.studly_case($driver).'\\'.studly_case($entityKey).'Store';
    }

    /**
     * Get store key from default, enity map override or one time property
     * @return string
     */
    protected function storeKey()
    {
        if ($this->storeKey) {
            // Use one time store
            $storeKey = $this->storeKey;
        } else {
            $storeKey = $this->config('default');
        }
        return $storeKey;
    }

    /**
     * Set a one time store variable for next entity chain
     * @param  string $storeKey
     * @return $this
     */
    public function store($storeKey)
    {
        $this->storeKey = $storeKey;
        return $this;
    }

    /**
     * Get the config configuration
     * @param  string $name
     * @return string
     */
    public function config($name)
    {
        return $this->ioc['config'][$this->configKey.".$name"];
    }

    /**
     * Get the config options for this store/entity
     * @param  string $storeKey
     * @param  string $entityKey
     * @return array
     */
    public function entityConfig($storeKey, $entityKey)
    {
        $connections = $this->config('stores')[$storeKey];
        foreach ($connections as $connection) {
            if (in_array($entityKey, $connection['entities'])) {
                unset($connection['entities']);
                return $connection;
            }
        }
    }

    /**
     * Get an array of all entities
     * @return array
     */
    public function entities()
    {
        $entities = [];
        $stores = $this->config('stores');
        foreach ($stores as $store) {
            foreach ($store as $connection) {
                foreach ($connection['entities'] as $entity) {
                    if (!in_array($entity, $entities)) {
                        $entities[] = $entity;
                    }
                }
            }
        }
        return $entities;
    }

    /**
     * Get distinct entities from config
     * @return array
     */
    protected function resolvable()
    {
        // Find distinct entites found in config and cache in $this->resolvable
        if (!isset($this->resolvable)) {
            $this->resolvable = [];
            $connections = $this->config('stores.'.$this->storeKey());
            foreach ($connections as $connection) {
                foreach ($connection['entities'] as $entity) {
                    if (!in_array($entity, $this->resolvable)) {
                        $this->resolvable[] = $entity;
                    }
                }
            }
        }
        return $this->resolvable;
    }

    /**
     * Get a store instance by full classname
     * @param  string $store store classname
     * @return object
     */
    public function getStoreInstance($store)
    {
        if (isset($this->stores[$store])) {
            return $this->stores[$store];
        }
    }

    /**
     * Dynamically call a method if the requested property does not exist
     * @param  string $property
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            return $this->{"{$property}"}();
        }
    }

    /**
     * Dynamically call entity classes
     * @param  string  $entityKey
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($entityKey, $parameters)
    {
        if (in_array($entityKey, $this->resolvable())) {
            return $this->resolveEntity($entityKey);
        }
    }
}
