<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsFake extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clients', function(Blueprint $table) {
			#$table->engine = 'InnoDB';
			$table->primary('id');
			$table->string('guid', 36)->unique();
			$table->integer('extract')->unique();
			$table->string('name', 100)->index();
			$table->string('host', 50)->index();
			$table->integer('address_id')->nullable()->unsigned();
			$table->boolean('disabled')->default(false)->index();
			$table->timestamps();

			// Relationships
			#$table->foreign('host')->references('key')->on('hosts');
			#$table->foreign('address_id')->references('id')->on('addresses');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('clients');
	}

}
