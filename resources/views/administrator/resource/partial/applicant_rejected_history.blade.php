@php($index=1)
@php($crm_sub_stages=['cv_sent_reject' => 'crm_reject', 'request_reject' => 'CRM_request_reject', 'interview_not_attended' => 'CRM_interview_not_attended', 'start_date_hold' => 'CRM_start_date_hold', 'dispute' => 'CRM_dispute'])
@forelse($applicants_rejected_history as $key => $value)
    <div class="col-1"></div>
    <p>
         <span class="font-weight-semibold">{{ $index++ }}. Dated: </span>{{ date('jS M Y h:i A',strtotime($value->created_at)) }} |
        <span class="font-weight-semibold">Unit: </span>{{ $value->unit_name }} |
        <span class="font-weight-semibold">Job Title: </span>{{ strtoupper($value->job_title) }} |
        <span class="font-weight-semibold">Postcode: </span>{{ strtoupper($value->postcode) }} |
        <span class="font-weight-semibold">Stage: </span><span class="text-capitalize">{{ str_replace('_', ' ', $crm_sub_stages[$value->moved_tab_to]) }}</span>
    </p>
    <p>
        <span class="font-weight-semibold">Details: </span>{{ $value->details }}
    </p>
    <hr class="w-25 center">
@empty
    <div class="col-1"></div>
    <p>
        <span class="font-weight-semibold">No Rejected History found. </span>
    </p>
@endforelse