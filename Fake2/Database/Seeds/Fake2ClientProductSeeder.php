<?php

use Illuminate\Database\Seeder;

class Fake2ClientProductSeeder extends Seeder
{
    public function run()
    {
        $fake2 = App::make('Mreschke\Repository\Fake2');
        $stores = Config::get('mreschke.repository.fake2.stores');

        foreach ($stores as $storeKey => $value) {
            $store = $fake->store($storeKey)->clientProduct;

            // Truncate records
            $store->truncate();

            // Bulk insert
            /*$data = [
                ["guid" => "100XB1C9-55E9-2EE2-9682-84101782417A", "name" => "Mreschke Toyota", "addressID" => 1, "note" => "Note one",  "disabled" => false],
                ["guid" => "200XB1C9-55E9-2EE2-9682-84101782417A", "name" => "Mreschke Ford",   "addressID" => 2, "note" => "Note two",  "disabled" => false],
                ["guid" => "300XB1C9-55E9-2EE2-9682-84101782417A", "name" => "Mreschke Kia",    "addressID" => 3, "note" => null,        "disabled" => false],
                ["guid" => "400XB1C9-55E9-2EE2-9682-84101782417A", "name" => "Mreschke Chevy",  "addressID" => 4, "note" => "Note four and stuff", "disabled" => false],
                ["guid" => "500XB1C9-55E9-2EE2-9682-84101782417A", "name" => "Mreschke BMW",    "addressID" => 5, "note" => "Note five and stuff", "disabled" => true ],
                ["guid" => "600XB1C9-55E9-2EE2-9682-84101782417A", "name" => "Mreschke Honda",  "addressID" => 6, "note" => null,        "disabled" => true ],

            ];
            $store->insert($data);*/
        }
    }
}
