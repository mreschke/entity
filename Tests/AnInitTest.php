<?php

class AnInitTest extends TestCase
{

	/** @test */
	public function init()
	{
		echo "\n\nRunning ".__CLASS__;
	}

	public function startTest()
	{
	}

	/** @test */
	public function i_can_insert_one_client()
	{
		$this->fake->client->insert([
			'guid' => '2EFCF9E9-742C-4D48-B121-E56D51FBC71B',
			'name' => 'Auffenberg Kia',
			'extract' => 3508,
			'hostKey' => 'bgmo',
			'addressID' => null
		]);

	}

	/** @test */
	public function first()
	{
		#$this->startTest();
		#dd(Config::get('database.connections'));
		$x = $this->fake->client()->get();
		dd($x);
	}

	public function fake()
	{
		return app('Mreschke\Repository\Fake');
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

}