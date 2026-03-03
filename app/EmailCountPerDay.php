<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class EmailCountPerDay extends Model
{
    protected $fillable=[
        'Email_count_per_day','date'
    ];
}
