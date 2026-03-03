<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ApplicantUpdatedHistory extends Model
{
     protected $fillable=[
         'user_id','applicant_id','column_name'
     ];
}
