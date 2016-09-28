<?php

use Illuminate\Database\Seeder;

class Fake2ProductSeeder extends Seeder
{
    public function run()
    {
        $fake2 = App::make('Mreschke\Repository\Fake2');
        $stores = Config::get('mreschke.repository.fake2.stores');

        foreach ($stores as $storeKey => $value) {
            $store = $fake2->store($storeKey)->product;

            // Truncate records
            $store->truncate();

            // Bulk insert
            $data = [
                ["name" => "Monitor", "price" => 149.99, "disabled" => false],
                ["name" => "Keyboard", "price" => 49.99, "disabled" => true],
                ["name" => "Mouse", "price" => 19.99, "disabled" => false],
                ["name" => "Case", "price" => 89.99, "disabled" => false],
                ["name" => "Power Supply", "price" => 99.99, "disabled" => true],
            ];
            $store->insert($data);
        }
    }
}
