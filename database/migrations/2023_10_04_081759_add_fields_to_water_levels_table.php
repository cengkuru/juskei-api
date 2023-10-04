<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToWaterLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('water_levels', function (Blueprint $table) {
            $table->string('GeositeType')->nullable();
            $table->float('Latitude')->nullable();
            $table->float('Longitude')->nullable();
            $table->string('MeasuringMethod')->nullable();
            $table->string('WaterLevelStatus')->nullable();
            $table->float('WaterLevel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('water_levels', function (Blueprint $table) {
            $table->dropColumn([
                'GeositeType',
                'Latitude',
                'Longitude',
                'MeasuringMethod',
                'WaterLevelStatus',
                'WaterLevel'
            ]);
        });
    }
}
