<?php

use Illuminate\Database\Seeder;

class FakeAddressSeeder extends Seeder
{

	public function run()
	{
		$fake = App::make('Mreschke\Repository\Fake');
		$stores = Config::get('mreschke.repository.fake.stores');

		foreach ($stores as $storeKey => $value) {
			$store = $fake->store($storeKey)->address;

			// Truncate records
			$store->truncate();

			// Bulk insert
			$data = [
				["address" => "100 Mreschke Toyota Lane", "city" => "DallasToyota", "state" => "TX", "zip" => 75061, "note" => "Address One Note Cool"],
				["address" => "102 Mreschke Ford Lane", "city" => "DallasFord", "state" => "TX", "zip" => 75062, "note" => null],
				["address" => "102 Mreschke Kia Lane", "city" => "DallasKia", "state" => "TX", "zip" => 75063, "note" => "Address Three Note"],
				["address" => "102 Mreschke Chevy Lane", "city" => "DallasChevy", "state" => "TX", "zip" => 75064, "note" => "Address Four Note"],
				["address" => "102 Mreschke BMW Lane", "city" => "DallasBMW", "state" => "TX", "zip" => 75065, "note" => null],
				["address" => "102 Mreschke Honda Lane", "city" => "DallasHonda", "state" => "TX", "zip" => 75066, "note" => "Address Six Note Cool"],
			];
			$store->insert($data);
		}

	}

}
