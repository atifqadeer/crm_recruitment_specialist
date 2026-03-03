<?php

namespace Horsefly\Observers;

use Horsefly\Applicant;
use Horsefly\Audit;
use Horsefly\Sale;
use Horsefly\User;
use Illuminate\Support\Facades\Auth;

class ActionObserver
{
    /**
     * Activity Log for Resource > Direct > action: Not Interested, Send CV, No Nursing Home
     * Activity Log for Quality > action: Clear, Reject
     *
     * @param $data
     * @return void
     */
    public function action($data, $module)
    {
        $auth_user = Auth::user();
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");
        $data['action_performed_by'] = $auth_user->name;
        $sale_info = Sale::where('sales.id', $data['sale'])
            ->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
            ->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
            ->select('sales.job_title', 'sales.postcode', 'sales.id', 'offices.office_name', 'units.unit_name')
            ->first();
        $applicant = Applicant::find($data['applicant']);
        $data['sale'] = $sale_info->job_title;
        $data['job_postcode'] = $sale_info->postcode;
        $data['applicant'] = @$applicant->applicant_email;
        $data['unit_name'] = $sale_info->unit_name;
        $data['office_name'] = $sale_info->office_name;

        $audit = new Audit();
        $audit->user_id = $auth_user->id;
        $audit->data = json_decode(json_encode($data, JSON_FORCE_OBJECT));
        $audit->message = "Action: {$data['action']} from {$module} performed successfully at {$time} on {$date}";
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_type = $module;
        $audit->save();
    }

    public function saleOpenClose($status, $sale, $columns)
    {
        $auth_user = Auth::user();
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $columns;
        $data['message'] = 'Sale ('.$sale->postcode.' - '.$sale->job_title.') '.($status == 'active' ? 'closed' : 'opened');

        $audit = new Audit();
        $audit->user_id = $auth_user->id;
        $audit->data = json_decode(json_encode($data, JSON_FORCE_OBJECT));
        $audit->message = $status == 'active' ? 'sale-closed' : 'sale-opened';
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_id = $sale->id;
        $audit->auditable_type = get_class($sale);
        $audit->save();
    }
	
	public function changeSaleStatus($sale, $columns)
    {
        $auth_user = Auth::user();
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $columns;
        $d_message = 'opened';
        $message = 'sale-opened';
        if ($columns['status'] == 'disable') {
            $d_message = 'closed';
            $message = 'sale-closed';
        } elseif ($columns['status'] == 'rejected') {
            $d_message = 'rejected';
            $message = 'sale-rejected';
        }
        $data['message'] = 'Sale ('.$sale->postcode.' - '.$sale->job_title.') '.$d_message;

        $audit = new Audit();
        $audit->user_id = $auth_user->id;
        $audit->data = json_decode(json_encode($data, JSON_FORCE_OBJECT));
        $audit->message = $message;
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_id = $sale->id;
        $audit->auditable_type = get_class($sale);
        $audit->save();
    }

    public function changeCvStatus($applicant_id, $columns, $msg)
    {
        $auth_user = Auth::user();
        date_default_timezone_set('Europe/London');
        $date = date('jS F Y');
        $time = date("h:i A");

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $columns;

        $audit = new Audit();
        $audit->user_id = $auth_user->id;
        $audit->data = json_decode(json_encode($data, JSON_FORCE_OBJECT));
        $audit->message = 'Applicant CV '.$msg;;
        $audit->audit_added_date = $date;
        $audit->audit_added_time = $time;
        $audit->auditable_id = $applicant_id;
        $audit->auditable_type = 'Horsefly\\Applicant';
        $audit->save();
    }
}
