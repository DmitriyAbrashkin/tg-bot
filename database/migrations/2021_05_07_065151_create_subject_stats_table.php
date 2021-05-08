<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubjectStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subject_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('1kt');
            $table->integer('2kt');
            $table->integer('3kt');
            $table->integer('4kt');
            $table->integer('1pr');
            $table->integer('2pr');
            $table->integer('3pr');
            $table->integer('4pr');
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
        Schema::dropIfExists('subject_stats');
    }
}
