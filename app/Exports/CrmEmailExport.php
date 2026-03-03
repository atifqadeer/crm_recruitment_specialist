<?php

namespace Horsefly\Exports;

use Horsefly\Applicant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class CrmEmailExport implements FromCollection, WithHeadings
{
    protected $tab;

    public function __construct($tab)
    {
        $this->tab = $tab;
    }

    public function collection()
	{
		$query = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
		->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
		->join('offices', 'sales.head_office', '=', 'offices.id')
		->join('units', 'sales.head_office_unit', '=', 'units.id')
		->join('history', function ($join) {
			$join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
			$join->on('crm_notes.sales_id', '=', 'history.sale_id');
		})
		->select(
			'offices.office_name',
			'units.unit_name',
			'units.unit_postcode',
			'units.contact_email',
			'units.contact_name',
			'units.contact_phone_number'
		);

        if ($this->tab == 'paid') {
            $query->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'paid',
                'history.sub_stage' => 'crm_paid',
                'history.status' => 'active'
            ])
            ->whereIn('crm_notes.id', function($q){
                $q->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="paid" and sales_id=sales.id and applicants.id=applicant_id'));
            });
        }elseif($this->tab == 'dispute'){
            
            $query->where([
                    'applicants.status' => 'active',
                    'crm_notes.moved_tab_to' => 'dispute',
                    'history.sub_stage' => 'crm_dispute', 'history.status' => 'active'
                ])->whereIn('crm_notes.id', function($q){
                    $q->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="dispute" and sales_id=sales.id and applicants.id=applicant_id'));
                });
            
        }elseif($this->tab == 'start_date_hold'){
            
            $query->where([
                    'applicants.status' => 'active',
                    'crm_notes.moved_tab_to' => 'start_date_hold',
                    'history.status' => 'active'
                ])->whereIn('crm_notes.id', function($q){
                    $q->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="start_date_hold" and sales_id=sales.id and applicants.id=applicant_id'));
                })->whereIn('history.sub_stage', ['crm_start_date_hold', 'crm_start_date_hold_save']);
            
        }elseif($this->tab == 'not_attended'){
            
            $query->where([
                    "applicants.status" => "active",
                    "crm_notes.moved_tab_to" => "interview_not_attended",
                    "history.sub_stage" => "crm_interview_not_attended", "history.status" => "active"
                ])->whereIn('crm_notes.id', function($q){
                    $q->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="interview_not_attended" and sales_id=sales.id and applicants.id=applicant_id'));
                });
            
        }elseif($this->tab == 'crm_reject'){
            
            $query->where([
                    "applicants.status" => "active",
                    "history.status" => "active"
                ])->whereIn("crm_notes.moved_tab_to",['cv_sent_reject','cv_sent_reject_no_job'])
                ->whereIn('history.sub_stage',['crm_reject','crm_no_job_reject'])
                ->whereIn('crm_notes.id', function($q) {
                    $q->select(DB::raw('MAX(id)'))
                        ->from('crm_notes')
                        ->whereIn('moved_tab_to', ['cv_sent_reject', 'cv_sent_reject_no_job'])
                        ->where('sales_id', '=', DB::raw('sales.id'))
                        ->where('applicants.id', '=', DB::raw('applicant_id'));
                });
            
        }elseif($this->tab == 'declined'){
            
            $query->where([
                    'applicants.status' => 'active',
                    'crm_notes.moved_tab_to' => 'declined',
                    'history.status' => 'active'
                ])->whereIn('crm_notes.id', function($q){
                    $q->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="declined" and sales_id=sales.id and applicants.id=applicant_id'));
                })->where('history.sub_stage', '=', 'crm_declined');
            
        }

        $results = $query->orderBy('crm_notes.created_at', 'DESC')->get();
        return $results;
    }
	
    public function headings(): array
    {
        return [
            'Head Office',
            'Unit',
            'PostCode',
            'Email',
            'Contact Name',
            'Phone'
        ];
    }
}
