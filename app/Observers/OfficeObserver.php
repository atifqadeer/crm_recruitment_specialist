<?php

namespace Horsefly\Observers;

use Horsefly\Audit;
use Horsefly\Office;
use Illuminate\Support\Facades\Auth;

class OfficeObserver
{
    /**
     * Handle the office "created" event.
     *
     * @param  \Horsefly\Office  $office
     * @return void
     */
    public function created(Office $office)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $office->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($office),
            "message" => "Office {$office->office_name} has been created successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the office "updated" event.
     *
     * @param  \Horsefly\Office  $office
     * @return void
     */
    public function updated(Office $office)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $columns = $office->getDirty();
        $office['changes_made'] = $columns;

        $office->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($office),
            "message" => "Office {$office->office_name} has been updated successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    public function csvAudit($audit_data)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $audit = new Audit();
        $audit->user_id = Auth::id();
        $audit->data = json_decode(json_encode($audit_data, JSON_FORCE_OBJECT));
        $audit->message = "Head Offices CSV file imported successfully at {$time} on {$date}";
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_type = "Horsefly\Office";
        $audit->save();
    }
}
