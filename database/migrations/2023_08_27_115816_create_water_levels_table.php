<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaterLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('water_levels', function (Blueprint $table) {
            $table->id();
            $table->string('geositeinfo_dataowner');
            $table->string('geositeinfo_identifier');
            $table->float('geositeinfo_latitude');
            $table->float('geositeinfo_longitude');
            $table->string('waterlevel_referencepoint');
            $table->integer('year');
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
        Schema::dropIfExists('water_levels');
    }
}
