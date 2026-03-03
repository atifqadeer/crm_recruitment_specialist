<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class AvailableJobInRadius extends Model
{
    protected $table = 'available_job_in_radius';
    protected $fillable = ['applicant_id', 'status'];
}
