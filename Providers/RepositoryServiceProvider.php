<?php namespace Mreschke\Repository\Providers;

use Config;
use Module;
use Mreschke\Repository\Fake\Fake;
use Mreschke\Repository\Fake2\Fake2;
use Illuminate\Support\ServiceProvider;

/**
 * Provides Repository Services
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class RepositoryServiceProvider extends ServiceProvider
{

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

        // Debug listener
        /*\DB::listen(function ($query) {
            dump($query->sql, $query->bindings);
            // $query->sql
            // $query->bindings
            // $query->time
        });*/
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
     * If running in test environment, append test configs and singletons
     *
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
            $this->app->singleton('Mreschke\Repository\Fake\Fake', function ($app) {
                return new Fake($app);
            });
            $this->app->alias('Mreschke\Repository\Fake\Fake', 'Mreschke\Repository\Fake');

            // Merge config
            $this->mergeConfigFrom(__DIR__.'/../Fake/Config/fake.php', 'mreschke.repository.fake');

            // Fake 2 (second fake test repository)
            Config::set('database.connections.fake2-repository', [
                'driver'   => 'sqlite',
                'database' => realpath(__DIR__.'/../Fake2/Database/test.sqlite'),
                'prefix'   => '',
            ]);

            // Bind Main Class (singleton because it caches the entities and stores)
            $this->app->singleton('Mreschke\Repository\Fake2\Fake2', function ($app) {
                return new Fake2($app);
            });
            $this->app->alias('Mreschke\Repository\Fake2\Fake2', 'Mreschke\Repository\Fake2');

            // Merge config
            $this->mergeConfigFrom(__DIR__.'/../Fake2/Config/fake2.php', 'mreschke.repository.fake2');
        }
    }
}
