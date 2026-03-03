<?php

namespace Horsefly\Observers;

use Horsefly\Sales_notes;
use Illuminate\Support\Facades\Auth;

class Sales_notesObserver
{
    /**
     * Handle the sales_notes "created" event.
     *
     * @param  \Horsefly\Sales_notes  $salesNotes
     * @return void
     */
    public function created(Sales_notes $salesNotes)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $salesNotes->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($salesNotes),
            "message" => "Sale Note for sale: {$salesNotes->sale_id} has been created successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the sales_notes "updated" event.
     *
     * @param  \Horsefly\Sales_notes  $salesNotes
     * @return void
     */
    public function updated(Sales_notes $salesNotes)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $columns = $salesNotes->getDirty();
        $salesNotes['changes_made'] = $columns;

        $salesNotes->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($salesNotes),
            "message" => "Sale Note for sale: {$salesNotes->sale_id} has been updated successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the sales_notes "deleted" event.
     *
     * @param  \Horsefly\Sales_notes  $salesNotes
     * @return void
     */
    public function deleted(Sales_notes $salesNotes)
    {
        //
    }
}
