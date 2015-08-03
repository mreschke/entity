<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsFake2 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ProductTable', function(Blueprint $table) {
			$table->increments('ID');
			$table->string('Name');
			$table->decimal('Price', 6, 2);
			$table->boolean('Disabled')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('ProductTable');
	}

}
