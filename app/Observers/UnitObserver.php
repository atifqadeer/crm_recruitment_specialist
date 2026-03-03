<?php

namespace Horsefly\Observers;

use Horsefly\Audit;
use Horsefly\Unit;
use Illuminate\Support\Facades\Auth;

class UnitObserver
{
    /**
     * Handle the unit "created" event.
     *
     * @param  \Horsefly\Unit  $unit
     * @return void
     */
    public function created(Unit $unit)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $unit->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($unit),
            "message" => "Unit {$unit->unit_name} has been created successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the unit "updated" event.
     *
     * @param  \Horsefly\Unit  $unit
     * @return void
     */
    public function updated(Unit $unit)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $columns = $unit->getDirty();
        $unit['changes_made'] = $columns;

        $unit->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($unit),
            "message" => "Unit {$unit->unit_name} has been updated successfully at {$time} on {$date}",
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
        $audit->message = "Units CSV file imported successfully at {$time} on {$date}";
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_type = "Horsefly\Office";
        $audit->save();
    }
}
