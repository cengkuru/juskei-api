<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddYearColumnToRainfallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rainfalls', function (Blueprint $table) {
            $table->integer('year')->nullable()->after('dateT');
        });

        // Update the 'year' column using values from the 'dateT' column
        DB::statement("UPDATE rainfalls SET year = YEAR(dateT)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rainfalls', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
}
