<?php

namespace Horsefly\Observers;

use Horsefly\Audit;
use Horsefly\IpAddress;
use Illuminate\Support\Facades\Auth;

class IpAddressObserver
{
    /**
     * Handle the ip-address "created" event.
     *
     * @param  \Horsefly\IpAddress  $ip_address
     * @return void
     */
    public function created(IpAddress $ip_address)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $ip_address->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($ip_address),
            "message" => "IP Address {$ip_address->ip_address} has been created successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the ip-address "updated" event.
     *
     * @param  \Horsefly\IpAddress  $ip_address
     * @return void
     */
    public function updated(IpAddress $ip_address, $message = null, $col = null)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $columns = $ip_address->getDirty();
        $ip_address['changes_made'] = $col == null ? $columns : $col;

        $ip_address->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($ip_address),
            "message" => $message == null ? "IP Address {$ip_address->ip_address} has been updated successfully at {$time} on {$date}" : $message . " at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the ip_address "updated" event.
     *
     * @param  \Horsefly\IpAddress  $ip_address
     * @return void
     */
    public function deleted(IpAddress $ip_address)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $ip_address->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($ip_address),
            "message" => "IP Address {$ip_address->ip_address} has been deleted successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }
}