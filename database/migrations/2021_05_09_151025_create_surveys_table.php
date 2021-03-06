<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('description');
            $table->boolean('single_survey')->default(0);
            $table->boolean('total_in_right')->default(0);
            $table->boolean('total_in_bottom')->default(0);
            $table->boolean('average_in_right')->default(0);
            $table->boolean('average_in_bottom')->default(0);
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
        Schema::dropIfExists('surveys');
    }
}
