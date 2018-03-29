<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovieCastTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movie_cast', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('movie_id')->unsigned();
            $table->integer('celeb_id')->unsigned();
            $table->text('relation');
            $table->timestamps();

            $table->foreign('movie_id')
                ->references('id')
                ->on('movie')
                ->onDelete('cascade');
            $table->foreign('celeb_id')
                ->references('id')
                ->on('celebrity')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movie_cast');
    }
}
