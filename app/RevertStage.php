<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class RevertStage extends Model
{
    protected $fillable=[
      'applicant_id','sale_id','revert_added_date','revert_added_time','stage','user_id','notes'
    ];
	  public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
