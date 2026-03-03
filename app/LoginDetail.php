<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class LoginDetail extends Model
{
    protected $fillable = ['user_id', 'login_time','login_date'];
}
