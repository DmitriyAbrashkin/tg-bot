<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullableToTableSubjectStats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subject_stats', function (Blueprint $table) {
            $table->integer('1kt')->nullable()->change();
            $table->integer('2kt')->nullable()->change();
            $table->integer('3kt')->nullable()->change();
            $table->integer('4kt')->nullable()->change();
            $table->integer('1pr')->nullable()->change();
            $table->integer('2pr')->nullable()->change();
            $table->integer('3pr')->nullable()->change();
            $table->integer('4pr')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subject_stats', function (Blueprint $table) {
            $table->integer('1kt')->change();
            $table->integer('2kt')->change();
            $table->integer('3kt')->change();
            $table->integer('4kt')->change();
            $table->integer('1pr')->change();
            $table->integer('2pr')->change();
            $table->integer('3pr')->change();
            $table->integer('4pr')->change();
        });
    }
}
