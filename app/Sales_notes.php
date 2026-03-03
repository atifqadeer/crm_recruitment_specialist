<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Horsefly\Events\Models\Sales_notes as Sales_notesEvent;

class Sales_notes extends Model
{
//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }
  
	protected $fillable = [
        'sale_id',
        'sale_note',
        'sales_note_added_date',
        'sales_note_added_time',
        'user_id',
        'status'
    ];
    /**
    /**
     *  The event map for the model.
     *
     * @var array
     */
//    protected $dispatchesEvents = [
//        'created' => Sales_notesEvent::class,
//        'updated' => Sales_notesEvent::class,
//    ];

    /**
     * Get all audits associated with the office.
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
	
	/**
     * Get user associated with the sale_note.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
