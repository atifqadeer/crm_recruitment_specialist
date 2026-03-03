<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Horsefly\Events\Models\IpAddress as IpAddressEvent;

class IpAddress extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'ip_address', 'mac_address', 'device_type', 'ip_address_added_date', 'ip_address_added_time'
    ];

//    public function getDateFormat()
//    {
//        return 'Y-m-d H:i:s.u';
//    }

    /**
     *  The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => IpAddressEvent::class,
        'updated' => IpAddressEvent::class,
        'deleted' => IpAddressEvent::class,
    ];

    /**
     * Get all audits associated with the ip_address.
     */
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
}
