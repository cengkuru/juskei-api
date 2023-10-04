<?php

namespace App\Http\Controllers;

use App\Models\WaterLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Rainfall;
use App\Models\Temperature;


class SummaryController extends Controller
{
    // Return Water Level Summary Stats JSON
    public function waterLevelSummaryStats(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch year filter from request

        $baseQuery = WaterLevel::query();

        // Apply year filter if provided
        if ($yearFilter) {
            $baseQuery->where('year', $yearFilter);
        }

        // Basic yearly statistics
        $waterLevelSummaryStats = clone $baseQuery;
        $waterLevelSummaryStats = $waterLevelSummaryStats->selectRaw('year, COUNT(DISTINCT borehole_id) as numberOfBoreholes, AVG(WaterLevel) as avgWaterLevel, MIN(WaterLevel) as minWaterLevel, MAX(WaterLevel) as maxWaterLevel')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->keyBy('year');

        // Highest and lowest year averages
        $highestYearAverage = clone $baseQuery;
        $highestYearAverage = $highestYearAverage->selectRaw('year, AVG(WaterLevel) as avgWaterLevel')
            ->groupBy('year')
            ->orderBy('avgWaterLevel', 'desc')
            ->first();

        $lowestYearAverage = clone $baseQuery;
        $lowestYearAverage = $lowestYearAverage->selectRaw('year, AVG(WaterLevel) as avgWaterLevel')
            ->groupBy('year')
            ->orderBy('avgWaterLevel', 'asc')
            ->first();

        // Number of boreholes with increased and decreased water levels
        // Note: SQL queries for increasedLevels and decreasedLevels need to be implemented
        $increasedLevels = DB::select("/* SQL query for boreholes with increased levels */");
        $decreasedLevels = DB::select("/* SQL query for boreholes with decreased levels */");

        return [
            'summaryStats' => $waterLevelSummaryStats,
            'highestYearAverage' => $highestYearAverage,
            'lowestYearAverage' => $lowestYearAverage,
            'increasedLevels' => $increasedLevels,
            'decreasedLevels' => $decreasedLevels,
        ];
    }

    public function getYearlyBoreholeStats()
    {
        // The method calculates count of boreholes, average, maximum, and minimum water levels for each year
        $yearlyStats = DB::table('water_levels')
            ->select(DB::raw('year, COUNT(DISTINCT borehole_id) as numberOfBoreholes, AVG(WaterLevel) as avgWaterLevel, MAX(WaterLevel) as maxWaterLevel, MIN(WaterLevel) as minWaterLevel'))
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

        return $yearlyStats;
    }


    public function getWaterLevELrainfallScatterPlotData(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch year filter from request

        if (!$yearFilter) {
            return response()->json(['error' => 'Year filter is required'], 400);
        }

        // Fetch water level data
        $waterLevelData = WaterLevel::select(DB::raw('MONTHNAME(STR_TO_DATE(year, "%Y")) as month, AVG(WaterLevel) as avgWaterLevel'))
            ->where('year', $yearFilter)
            ->groupBy(DB::raw('MONTHNAME(STR_TO_DATE(year, "%Y"))'))
            ->orderBy(DB::raw('MONTH(STR_TO_DATE(year, "%Y"))'))
            ->get();

        // Fetch rainfall data
        $rainfallData = Rainfall::select(DB::raw('MONTHNAME(dateT) as month, AVG(rain) as avgRain'))
            ->whereYear('dateT', $yearFilter)
            ->groupBy(DB::raw('MONTHNAME(dateT)'))
            ->orderBy(DB::raw('MONTH(dateT)'))
            ->get();

        // Prepare data for scatter plot
        $scatterPlotData = $waterLevelData->map(function ($item, $key) use ($rainfallData) {
            $month = $item->month;
            $matchingRainfall = $rainfallData->firstWhere('month', $month);
            $avgRain = $matchingRainfall ? $matchingRainfall->avgRain : 0;

            return [
                'month' => $month,
                'avgWaterLevel' => $item->avgWaterLevel,
                'avgRain' => $avgRain
            ];
        });

        return response()->json($scatterPlotData);
    }



    public function getWaterLevelTempScatterPlotData(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch the year filter from the request

        if (!$yearFilter) {
            return response()->json(['error' => 'Year filter is required'], 400);
        }

        // Fetch average water levels per month
        $waterLevelData = WaterLevel::select(DB::raw('MONTHNAME(STR_TO_DATE(year, "%Y")) as month, AVG(WaterLevel) as avgWaterLevel'))
            ->where('year', $yearFilter)
            ->groupBy(DB::raw('MONTHNAME(STR_TO_DATE(year, "%Y"))'))
            ->orderBy(DB::raw('MONTH(STR_TO_DATE(year, "%Y"))'))
            ->get();

        // Fetch average temperature per month
        $temperatureData = Temperature::select(DB::raw('MONTHNAME(date) as month, AVG(av_temp_per_day) as avgTemp'))
            ->whereYear('date', $yearFilter)
            ->groupBy(DB::raw('MONTHNAME(date)'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get();

        // Prepare data for scatter plot
        $scatterPlotData = $waterLevelData->map(function ($item, $key) use ($temperatureData) {
            $month = $item->month;
            $matchingTemp = $temperatureData->firstWhere('month', $month);
            $avgTemp = $matchingTemp ? $matchingTemp->avgTemp : 0;

            return [
                'month' => $month,
                'avgWaterLevel' => $item->avgWaterLevel,
                'avgTemp' => $avgTemp
            ];
        });

        return response()->json($scatterPlotData);
    }




    public function getYearlyTemperatureExtremes()
    {
        // Query to get the highest and lowest temperatures grouped by year
        $temperatureExtremes = Temperature::select(DB::raw('year, MAX(av_temp_per_day) as highestTemp, MIN(av_temp_per_day) as lowestTemp'))
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

        // Query to get the month with the highest and lowest average temperatures for each year
        $highestLowestMonths = Temperature::select(DB::raw('year, MONTHNAME(date) as month, AVG(av_temp_per_day) as avgTemp'))
            ->groupBy(DB::raw('year, MONTH(date)'))
            ->orderBy(DB::raw('year, AVG(av_temp_per_day)'), 'desc')
            ->get()
            ->groupBy('year')
            ->map(function ($yearData) {
                return [
                    'highestMonth' => $yearData->first()->month,
                    'lowestMonth' => $yearData->last()->month
                ];
            });

        // Merge the data to create a comprehensive dataset
        $result = $temperatureExtremes->map(function ($item) use ($highestLowestMonths) {
            $year = $item->year;
            return [
                'year' => $year,
                'highestTemp' => $item->highestTemp,
                'lowestTemp' => $item->lowestTemp,
                'highestMonth' => $highestLowestMonths[$year]['highestMonth'] ?? null,
                'lowestMonth' => $highestLowestMonths[$year]['lowestMonth'] ?? null
            ];
        });

        return response()->json($result);
    }




    public function getDamTemperatureExtremes(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch the year filter from the request

        if (!$yearFilter) {
            return response()->json(['error' => 'Year filter is required'], 400);
        }

        // Query to get the highest and lowest temperatures grouped by dam
        $temperatureExtremes = Temperature::select(DB::raw('dam, MAX(av_temp_per_day) as highestTemp, MIN(av_temp_per_day) as lowestTemp'))
            ->where('year', $yearFilter)
            ->groupBy('dam')
            ->orderBy('dam', 'asc')
            ->get();

        // Query to get the month with the highest and lowest average temperatures for each dam
        $highestLowestMonths = Temperature::select(DB::raw('dam, MONTHNAME(date) as month, AVG(av_temp_per_day) as avgTemp'))
            ->where('year', $yearFilter)
            ->groupBy(DB::raw('dam, MONTH(date)'))
            ->orderBy(DB::raw('dam, AVG(av_temp_per_day)'), 'desc')
            ->get()
            ->groupBy('dam')
            ->map(function ($damData) {
                return [
                    'highestMonth' => $damData->first()->month,
                    'lowestMonth' => $damData->last()->month
                ];
            });

        // Merge the data to create a comprehensive dataset
        $result = $temperatureExtremes->map(function ($item) use ($highestLowestMonths) {
            $dam = $item->dam;
            return [
                'dam' => $dam,
                'highestTemp' => $item->highestTemp,
                'lowestTemp' => $item->lowestTemp,
                'highestMonth' => $highestLowestMonths[$dam]['highestMonth'] ?? null,
                'lowestMonth' => $highestLowestMonths[$dam]['lowestMonth'] ?? null
            ];
        });

        return response()->json($result);
    }



    public function getAverageAnnualTemperature()
    {
        // Query to get the average annual temperature by year
        $averageAnnualTemperature = Temperature::select(DB::raw('year, AVG(av_temp_per_day) as avgAnnualTemp'))
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

        return response()->json($averageAnnualTemperature);
    }



    public function getRainfallExtremesByYear()
    {
        // Query to get the highest and lowest average rainfall by year
        $rainfallExtremes = Rainfall::select(DB::raw('year, MAX(rain) as highestRain, MIN(rain) as lowestRain'))
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

        // Query to get the month with the highest and lowest average rainfall for each year
        $highestLowestMonths = Rainfall::select(DB::raw('year, MONTHNAME(dateT) as month, AVG(rain) as avgRain'))
            ->groupBy(DB::raw('year, MONTH(dateT)'))
            ->orderBy(DB::raw('year, AVG(rain)'), 'desc')
            ->get()
            ->groupBy('year')
            ->map(function ($yearData) {
                return [
                    'highestMonth' => $yearData->first()->month,
                    'lowestMonth' => $yearData->last()->month
                ];
            });

        // Merge the data to create a comprehensive dataset
        $result = $rainfallExtremes->map(function ($item) use ($highestLowestMonths) {
            $year = $item->year;
            return [
                'year' => $year,
                'highestRain' => $item->highestRain,
                'lowestRain' => $item->lowestRain,
                'highestMonth' => $highestLowestMonths[$year]['highestMonth'] ?? null,
                'lowestMonth' => $highestLowestMonths[$year]['lowestMonth'] ?? null
            ];
        });

        return response()->json($result);
    }


    public function getStationRainfallStats(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch the year filter from the request

        if (!$yearFilter) {
            return response()->json(['error' => 'Year filter is required'], 400);
        }

        // Query to get average rainfall by station for the selected year
        $averageRainfallByStation = Rainfall::select(DB::raw('station_name, AVG(rain) as avgRain'))
            ->where('year', $yearFilter)
            ->groupBy('station_name')
            ->orderBy('station_name', 'asc')
            ->get();

        // Query to get the month with the highest and lowest average rainfall for each station
        $highestLowestMonths = Rainfall::select(DB::raw('station_name, MONTHNAME(dateT) as month, AVG(rain) as avgRain'))
            ->where('year', $yearFilter)
            ->groupBy(DB::raw('station_name, MONTH(dateT)'))
            ->orderBy(DB::raw('station_name, AVG(rain)'), 'desc')
            ->get()
            ->groupBy('station_name')
            ->map(function ($stationData) {
                return [
                    'highestMonth' => $stationData->first()->month,
                    'lowestMonth' => $stationData->last()->month
                ];
            });

        // Merge the data to create a comprehensive dataset
        $result = $averageRainfallByStation->map(function ($item) use ($highestLowestMonths) {
            $station = $item->station_name;
            return [
                'station_name' => $station,
                'avgRain' => $item->avgRain,
                'highestMonth' => $highestLowestMonths[$station]['highestMonth'] ?? null,
                'lowestMonth' => $highestLowestMonths[$station]['lowestMonth'] ?? null
            ];
        });

        return response()->json($result);
    }



    public function getAverageRainfallByStationForAllYears()
    {
        // Query to get the average annual rainfall for each station and year
        $averageRainfallByStationAndYear = Rainfall::select(DB::raw('year, station_name, AVG(rain) as avgRain'))
            ->groupBy('year', 'station_name')
            ->orderBy('year', 'asc')
            ->orderBy('station_name', 'asc')
            ->get()
            ->groupBy('station_name');

        // Prepare the data in a format that can be easily used for line graph plotting
        $lineGraphData = $averageRainfallByStationAndYear->map(function ($stationData) {
            return $stationData->mapWithKeys(function ($item) {
                return [$item->year => $item->avgRain];
            });
        });

        return response()->json($lineGraphData);
    }


    public function getSelectedYearRainfallStats(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch the year filter from the request

        if (!$yearFilter) {
            return response()->json(['error' => 'Year filter is required'], 400);
        }

        $previousYear = $yearFilter - 1;

        // Total annual rainfall for selected year and previous year
        $totalRainfallCurrentYear = Rainfall::where('year', $yearFilter)->sum('rain');
        $totalRainfallPreviousYear = Rainfall::where('year', $previousYear)->sum('rain');

        // Highest and lowest annual rainfall for selected year and previous year
        $highestAnnualCurrentYear = Rainfall::where('year', $yearFilter)->max('rain');
        $highestAnnualPreviousYear = Rainfall::where('year', $previousYear)->max('rain');

        $lowestAnnualCurrentYear = Rainfall::where('year', $yearFilter)->min('rain');
        $lowestAnnualPreviousYear = Rainfall::where('year', $previousYear)->min('rain');

        // Average annual rainfall for the selected year
        $averageAnnualRainfall = Rainfall::where('year', $yearFilter)->avg('rain');

        // Number of stations above and below the average annual rainfall
        $stationsAboveAverage = Rainfall::where('year', $yearFilter)->where('rain', '>', $averageAnnualRainfall)->count('station_name');
        $stationsBelowAverage = Rainfall::where('year', $yearFilter)->where('rain', '<', $averageAnnualRainfall)->count('station_name');

        // Prepare the result
        $result = [
            'totalRainfall' => [
                'currentYear' => $totalRainfallCurrentYear,
                'previousYear' => $totalRainfallPreviousYear
            ],
            'highestAnnual' => [
                'currentYear' => $highestAnnualCurrentYear,
                'previousYear' => $highestAnnualPreviousYear
            ],
            'lowestAnnual' => [
                'currentYear' => $lowestAnnualCurrentYear,
                'previousYear' => $lowestAnnualPreviousYear
            ],
            'stationsAboveAverage' => $stationsAboveAverage,
            'stationsBelowAverage' => $stationsBelowAverage
        ];

        return response()->json($result);
    }




    public function getSelectedYearTemperatureStats(Request $request)
    {
        $yearFilter = $request->input('year');  // Fetch the year filter from the request

        if (!$yearFilter) {
            return response()->json(['error' => 'Year filter is required'], 400);
        }

        $previousYear = $yearFilter - 1;

        // Total average temperature for selected year and previous year
        $averageTempCurrentYear = Temperature::where('year', $yearFilter)->avg('av_temp_per_day');
        $averageTempPreviousYear = Temperature::where('year', $previousYear)->avg('av_temp_per_day');

        // Highest and lowest annual temperature for selected year and previous year
        $highestTempCurrentYear = Temperature::where('year', $yearFilter)->max('av_temp_per_day');
        $highestTempPreviousYear = Temperature::where('year', $previousYear)->max('av_temp_per_day');

        $lowestTempCurrentYear = Temperature::where('year', $yearFilter)->min('av_temp_per_day');
        $lowestTempPreviousYear = Temperature::where('year', $previousYear)->min('av_temp_per_day');

        // Number of dams above and below the average annual temperature
        $damsAboveAverage = Temperature::where('year', $yearFilter)->where('av_temp_per_day', '>', $averageTempCurrentYear)->count('dam');
        $damsBelowAverage = Temperature::where('year', $yearFilter)->where('av_temp_per_day', '<', $averageTempCurrentYear)->count('dam');

        // Prepare the result
        $result = [
            'averageTemperature' => [
                'currentYear' => $averageTempCurrentYear,
                'previousYear' => $averageTempPreviousYear
            ],
            'highestAnnualTemperature' => [
                'currentYear' => $highestTempCurrentYear,
                'previousYear' => $highestTempPreviousYear
            ],
            'lowestAnnualTemperature' => [
                'currentYear' => $lowestTempCurrentYear,
                'previousYear' => $lowestTempPreviousYear
            ],
            'damsAboveAverage' => $damsAboveAverage,
            'damsBelowAverage' => $damsBelowAverage
        ];

        return response()->json($result);
    }












}
