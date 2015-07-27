<?php namespace Mreschke\Entity\Providers;

use Module;
#use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;


/**
 * Provides Entity services
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class EntityServiceProvider extends ServiceProvider {

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

		// Bind Main Class
		#$this->app->bind('Dynatron\Vfi\Vfi', function($app) {
		#	return new Vfi($app);
		#});

		// Bind and Alias Helper
		#$alias = function($abstract, $aliases = []) {
		#	foreach ($aliases as $alias) {$this->app->alias($abstract, $alias);}
		#};
		#$alias('Dynatron\Vfi\Vfi', ['Dynatron\Vfi']);

		// Merge config
		#$this->mergeConfigFrom(__DIR__.'/../Config/vfi.php', 'dynatron.vfi');

		// Register our Artisan Commands
		#$this->commands('Dynatron\Vfi\Console\Commands\ImportCommand'); // One time import...deployed 2015-07-17
		#$this->commands('Dynatron\Vfi\Console\Commands\PackageCommand');
		#$this->commands('Dynatron\Vfi\Console\Commands\ParseCommand');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function providesXXXXX()
	{
		return array();
	}

}
