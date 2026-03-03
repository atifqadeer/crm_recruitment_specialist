<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ModuleNote extends Model
{
    protected $table = 'module_notes';

//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'module_note_uid', 'user_id', 'module_noteable_id', 'module_noteable_type', 'details', 'module_note_added_date', 'module_note_added_time', 'status'
    ];

    /**
     * Get all of the owning module_noteable models.
     */
    public function module_noteable()
    {
        return $this->morphTo();
    }
	
    /**
     * Get user associated with the module_note.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
