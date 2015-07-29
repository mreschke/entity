<?php namespace Mreschke\Repository\Providers;

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
		$this->setupTesting();

	}

	public function setupTesting()
	{

		// Add our database connections (has to be outside env check because migrations use it)
		\Config::set('database.connections.test-repository-db', [
			'driver'   => 'sqlite',
			'database' => realpath(__DIR__.'/../Tests/Fake/Database/test.sqlite'),
			'prefix'   => '',
		]);
		#dd(\Config::get('database.connections'));

		// Testing - Entire fake entity and repository for testing
		if ($this->app->environment('testing')) {



			// Bind Main Class (singleton because it caches the entities and stores)
			$this->app->singleton('Mreschke\Repository\Fake\Fake', function($app) {
				return new Fake($app);
			});
			$this->app->alias('Mreschke\Repository\Fake\Fake', 'Mreschke\Repository\Fake');

			// Merge config
			$this->mergeConfigFrom(__DIR__.'/../Tests/Fake/Config/fake.php', 'mreschke.repository.fake');
		}

	}

}
