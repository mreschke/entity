<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientProductsFake2 extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ClientProductTable', function (Blueprint $table) {
            $table->integer('ClientID')->unsigned();
            $table->integer('ProductID')->unsigned();

            // Relationships
            #$table->foreign('ClientID')->references('ID')->on('NO-OTHER-ENTITY'); #NO, clients is in Fake not Fake2
            $table->foreign('ProductID')->references('ID')->on('ProductTable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ClientProductTable');
    }
}
