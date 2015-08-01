<?php namespace Mreschke\Repository\Providers;

use Config;
use Module;
use Mreschke\Repository\Fake\Fake;
use Illuminate\Support\ServiceProvider;

/**
 * Provides Repository Services
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class RepositoryServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Setup Testing Environment
		$this->setupTestingEnvironment();

	}

	/**
	 * Sorry...dont know how to test properly...this is all I got right now
	 * @return void
	 */
	public function setupTestingEnvironment()
	{
		// Testing - Entire fake entity and repository for testing
		if ($this->app->environment('testing') || $this->app->runningInConsole()) {

			// Add our database connections (has to be outside env check because migrations use it)
			Config::set('database.connections.fake-repository', [
				'driver'   => 'sqlite',
				'database' => realpath(__DIR__.'/../Fake/Database/test.sqlite'),
				'prefix'   => '',
			]);

			// Bind Main Class (singleton because it caches the entities and stores)
			$this->app->singleton('Mreschke\Repository\Fake\Fake', function($app) {
				return new Fake($app);
			});
			$this->app->alias('Mreschke\Repository\Fake\Fake', 'Mreschke\Repository\Fake');

			// Merge config
			$this->mergeConfigFrom(__DIR__.'/../Fake/Config/fake.php', 'mreschke.repository.fake');
		}

	}

}
