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
	protected $app;

	/**
	 * Base namespace
	 * @var string
	 */
	protected $namespace;

	/**
	 * The array of resolvable entity classes
	 * @var array
	 */
	protected $resolvable = [];

	/**
	 * The cached array of resolved entities
	 * @var array
	 */
	protected $entities = [];

	/**
	 * The cached array of resolved stores
	 * @var array
	 */
	protected $stores = [];

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
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Get an entity instance
	 * @param  string $entityKey
	 * @return object
	 */
	protected function resolveEntity($entityKey)
	{
		// Get entity store instance
		$storeKey = $this->storeKey($entityKey);
		$storeClassname = $this->storeClassname($storeKey, $entityKey);
		$store = $this->resolveStore($storeKey, $entityKey);

		// Get entity classname
		$entityClassname = $this->entityClassname($entityKey);

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
	 * @param  string $entityKey
	 * @return object
	 */
	protected function resolveStore($storeKey, $entityKey)
	{
		// Get entity classname
		$entityClassname = $this->entityClassname($entityKey);

		// Get entity store classname
		$storeClassname = $this->storeClassname($storeKey, $entityKey);

		// Create new entity store instance if not found in cache
		if (!isset($this->stores[$storeClassname])) {
			$config = $this->config("stores.{$storeKey}");
			$driver = $this->config("stores.{$storeKey}.driver");

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
		$connection = $this->app['db']->connection($config['connection']);

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
		$driver = $this->config("stores.{$storeKey}.driver");
		return $this->namespace.'\\Stores\\'.studly_case($driver).'\\'.studly_case($entityKey).'Store';
	}

	/**
	 * Get store key from default, enity map override or one time property
	 * @param  string $entityKey
	 * @return string
	 */
	protected function storeKey($entityKey)
	{
		if ($this->storeKey) {
			// Use one time store
			$storeKey = $this->storeKey;
		} else {
			$entities = $this->config('entities');
			if (isset($entities[$entityKey])) {
				// Use custom entity store
				$storeKey = $entities[$entityKey]['store'];
			} else {
				// Use default store
				$storeKey = $this->config('default');
			}
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
		return $this->app['config'][$this->configKey.".$name"];
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
	 * @param  string  $entity
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($entity, $parameters)
	{
		if (in_array($entity, $this->resolvable)) {
			return $this->resolveEntity($entity);
		}
	}

}
