<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class Cv_note extends Model
{
//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }
	
	/**
     * Get sale associated with the cv_note.
     */
    public function sale()
    {
        return $this->hasOne(Sale::class, 'id', 'sale_id');
    }
	public function History()
    {
        return $this->hasOne(History::class,'applicant_id','applicant_id','sale_id','sale_id')->latest();
    }
	public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
