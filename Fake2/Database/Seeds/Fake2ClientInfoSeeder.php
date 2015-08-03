<?php

use Illuminate\Database\Seeder;

class Fake2ClientInfoSeeder extends Seeder
{

	public function run()
	{
		$fake2 = App::make('Mreschke\Repository\Fake2');
		$stores = Config::get('mreschke.repository.fake2.stores');

		foreach ($stores as $storeKey => $value) {
			$store = $fake2->store($storeKey)->clientInfo;

			// Truncate records
			$store->truncate();

			// Bulk insert
			$data = [
				["clientID" => 1, "region" => "South", "saleDate" => "2014-01-01 11:31:01"],
				["clientID" => 2, "region" => "South", "saleDate" => "2014-01-02 12:32:22"],
				["clientID" => 3, "region" => "North", "saleDate" => "2014-01-03 13:33:33"],
				["clientID" => 4, "region" => "East", "saleDate" => "2014-01-04 14:34:44"],
				["clientID" => 5, "region" => "East", "saleDate" => "2014-01-05 15:35:55"],
				["clientID" => 6, "region" => "West", "saleDate" => "2014-01-06 16:36:56"],
			];
			$store->insert($data);
		}

	}

}
