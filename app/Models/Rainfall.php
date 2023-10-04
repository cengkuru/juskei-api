<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rainfall extends Model
{
    use HasFactory;

    protected $fillable = ['station_name', 'dateT', 'latitude', 'longitude', 'rain','year'];
}
