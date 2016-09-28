<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttributesFake extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('key', 255)->primary();
            $table->string('entity', 50);
            $table->integer('entity_id');
            $table->string('index', 200);
            $table->text('value');

            // Indexes
            $table->index(['entity', 'entity_id', 'key']);
            #$table->index(['key', 'value']); // NO, cannot add index on BLOG text column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attributes');
    }
}
