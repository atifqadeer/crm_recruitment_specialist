<?php

namespace Horsefly;

use Horsefly\Events\Models\Office as OfficeEvent;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
	public function units(){
		return $this->belongsTo('Horsefly\Unit');
	}
//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }

    /**
     *  The event map for the model.
     *
     * @var array
     */
//    protected $dispatchesEvents = [
//        'created' => OfficeEvent::class,
//        'updated' => OfficeEvent::class,
//    ];

    /**
     * Get all audits associated with the office.
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * Get all module_notes associated with the office.
     */
    public function module_notes()
    {
        return $this->morphMany(ModuleNote::class, 'module_noteable');
    }
	
	/**
     * Get user associated with the office.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
	
	 // Define the relationship to sales
    public function sales()
    {
        return $this->hasMany(Sale::class, 'head_office');
    }
}
