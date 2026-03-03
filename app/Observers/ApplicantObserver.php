<?php

namespace Horsefly\Observers;

use Horsefly\Applicant;
use Horsefly\Audit;
use Illuminate\Support\Facades\Auth;

class ApplicantObserver
{
    /**
     * Handle the applicant "created" event.
     *
     * @param  \Horsefly\Applicant  $applicant
     * @return void
     */
    public function created(Applicant $applicant)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $applicant->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($applicant),
            "message" => "Applicant {$applicant->applicant_name} has been created successfully at {$time} on {$date}",
            "audit_added_date" => $date,
            "audit_added_time" => $time
        ]);
    }

    /**
     * Handle the applicant "updated" event.
     *
     * @param  \Horsefly\Applicant  $applicant
     * @return void
     */
    public function updated(Applicant $applicant)
    {
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $columns = $applicant->getDirty();
        $applicant['changes_made'] = $columns;

        $applicant->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_decode($applicant),
            "message" => "Applicant {$applicant->applicant_name} has been updated successfully at {$time} on {$date}",
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
        $audit->message = "Applicants CSV file imported successfully at {$time} on {$date}";
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_type = "Horsefly\Applicant";
        $audit->save();
    }
}
