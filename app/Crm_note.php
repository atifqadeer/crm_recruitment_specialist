<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class Crm_note extends Model
{
//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }

    /**
     * Get latest module_note associated with the applicant.
     */
    public function module_note()
    {
        return $this->hasOne(ModuleNote::class, 'module_noteable_id', 'applicant_id')
            ->where('module_noteable_type', 'Horsefly\\Applicant')->latest();
    }
	 public function History()
    {
        return $this->hasOne(History::class,'applicant_id','applicant_id','sale_id','sale_id')->latest();
    }
}
