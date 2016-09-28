<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class FakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Order is Critical
        $this->call('FakeAddressSeeder');
        $this->call('FakeClientSeeder');
    }
}
