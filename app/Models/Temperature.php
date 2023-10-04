<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temperature extends Model
{
    use HasFactory;

    protected $fillable = ['dam', 'date', 'year', 'max_temp', 'min_temp', 'av_temp_per_day'];
}
