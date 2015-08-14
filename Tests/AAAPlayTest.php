<?php namespace Mreschke\Repository;

use TestCase;
use Mockery as m;

class AAAPlayTest extends TestCase
{
	public function init()
	{
		$this->fake = $this->app->make('Mreschke\Repository\Fake');
		$this->fake2 = $this->app->make('Mreschke\Repository\Fake2');
		$this->iam = $this->app->make('Dynatron\Iam');
		$this->vfi = $this->app->make('Dynatron\Vfi');
	}

	protected function play()
	{
		$clients = $this->fake->client->first();
		dd($clients);
	}

	protected function playDynatron()
	{
		$clients = $this->vfi->client->with('address', 'host', 'feeds')->find(5975);
		dump($clients);
	}

	public function testEmpty() {}
	public function tearDown() { m::close(); }
	public function setUp()
	{
		parent::setUp();
		$this->init();
		foreach ($_SERVER['argv'] as $arg) {
			if (str_contains($arg, 'play')) {
				$method = "play".studly_case(substr($arg, 5));
				$this->$method();
				exit();
			}
		}
	}


}
