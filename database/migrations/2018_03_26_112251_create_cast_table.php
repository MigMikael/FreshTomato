<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCastTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('celebrity', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->string('birthday');
            $table->text('birthplace');
            $table->text('info');
            $table->text('image');
            $table->text('highest_rate');
            $table->text('lowest_rate');
            $table->text('url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('celebrity');
    }
}
