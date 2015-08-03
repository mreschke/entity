<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class Fake2Seeder extends Seeder
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
        $this->call('Fake2ProductSeeder');
		$this->call('Fake2ClientInfoSeeder');
		#$this->call('Fake2ClientProductSeeder');
	}

}
