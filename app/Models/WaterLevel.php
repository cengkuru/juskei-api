<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterLevel extends Model
{
    use HasFactory;

    protected $fillable = ['year','GeositeType','Latitude','Longitude','MeasuringMethod','WaterLevelStatus','WaterLevel'];
}
