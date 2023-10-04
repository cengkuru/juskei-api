<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropFieldsFromWaterLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('water_levels', function (Blueprint $table) {
            $table->dropColumn([
                'geositeinfo_dataowner',
                'geositeinfo_identifier',
                'geositeinfo_latitude',
                'geositeinfo_longitude',
                'waterlevel_referencepoint'
            ]);
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
            // Add the columns back if you want to reverse the migration.
        });
    }
}
