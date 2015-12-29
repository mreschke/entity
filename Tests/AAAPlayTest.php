<?php namespace Mreschke\Repository;

use TestCase;
use Mockery as m;

class AAAPlayTest extends TestCase
{
	public function init()
	{
		$this->fake = $this->app->make('Mreschke\Repository\Fake');
		$this->fake2 = $this->app->make('Mreschke\Repository\Fake2');
	}

	protected function play()
	{

		$x = $this->fake2->client;
		dump(get_class($x->store->manager)); #actual non-inherited
		dump(get_class($x->store->manager()));





		#$q1 = $this->fake->client->where('x', 'y');
		#dump($q1->store);

		#$q2 = $this->fake->client->where('a', 'b');
		#dd($q2->store);


		dd('done');






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
