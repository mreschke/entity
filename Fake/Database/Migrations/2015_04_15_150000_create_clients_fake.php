<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsFake extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ClientTable', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('GUID', 36)->unique();
            $table->string('Name', 100);
            $table->integer('AddressID')->unsigned();
            $table->string('Note')->nullable();
            $table->boolean('Disabled')->default(false)->index();

            // Relationships
            $table->foreign('AddressID')->references('ID')->on('addresses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ClientTable');
    }
}
