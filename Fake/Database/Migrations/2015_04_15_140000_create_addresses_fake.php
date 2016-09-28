<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesFake extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('AddressTable', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Address', 100);
            $table->string('City', 50);
            $table->string('State', 50);
            $table->integer('Zip');
            $table->string('Note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('AddressTable');
    }
}
