<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Horsefly\Events\Models\Unit as UnitEvent;

class Unit extends Model
{
	public function office(){
		return $this->belongsTo('Horsefly\Office');
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
//        'created' => UnitEvent::class,
//        'updated' => UnitEvent::class,
//    ];

    /**
     * Get all audits associated with the unit.
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * Get all module_notes associated with the unit.
     */
    public function module_notes()
    {
        return $this->morphMany(ModuleNote::class, 'module_noteable');
    }
	
	/**
     * Get user associated with the unit.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
