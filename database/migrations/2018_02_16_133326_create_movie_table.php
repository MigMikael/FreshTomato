<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movie', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('year')->nullable();
            $table->string('rating')->nullable();
            $table->text('genre')->nullable();
            $table->text('director')->nullable();
            $table->text('writer')->nullable();
            $table->integer('runtime')->nullable();
            $table->string('critics_score')->nullable();
            $table->string('audience_score')->nullable();
            $table->string('fresh_rotten')->nullable();
            $table->text('info')->nullable();
            $table->text('poster')->nullable();
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
        Schema::dropIfExists('movie');
    }
}
