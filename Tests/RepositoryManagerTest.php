<?php

use Mockery as m;

class RepositoryManagerTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
		$this->iam = $this->app->make('Mreschke\Repository\Fake');
	}
	public function tearDown()
	{
		m::close();
	}


	public function testSomething()
	{
		$this->iam->client->truncate();

		$this->iam->client->create([
			'guid' => '4FGCF9E9-742C-4D48-B121-E56D51FBC71B',
			'extract' => 50000,
			'name' => 'Mreschke Toyota',
			'hostKey' => 'www1',
			'address_id' => null,
			'created' => '2015-07-31 03:22:22',
			'updated' => '2015-07-31 03:22:22',
			'disabled' => false
		]);

		dd($this->iam->client->all());



		#$mockedApp = m::mock('Illuminate\Foundation\Application');
		#$manager = new Mreschke\Repository\Fake\Fake($mockedApp);
		#$manager->
	}

	public function getFake()
	{
		#$app = m::mock('Illuminate\Foundation\Application');
		#return new Mreschke\Repository\Fake\Fake($this->app);
		#return m::mock('Mreschke\Repository\Fake\Fake');
		return app('Mreschke\Repository\Fake');
	}

	public function getStore()
	{
		#$manager = $this->getManager();
		#return new Mreschke\Repository\DbStore($manager, 'x', m::mock('Illuminate\Database\Connection'));

	}


	public function getManager()
	{
		#return new Mreschke\Repository\Manager($this->app);
	}


}


