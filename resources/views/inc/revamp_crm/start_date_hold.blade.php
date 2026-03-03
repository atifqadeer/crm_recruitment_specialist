<!-- Main content -->
<div class="content-wrapper ml-0">
	<div class="card-header header-elements-inline justify-content-end">
		@can('applicant_open_to_paid')
		 <a href="{{ route('crm.export_email',['start_date_hold'])}}" class="btn bg-slate-800 legitRipple">
            <i class="icon-cloud-upload"></i>
            &nbsp;Export Email</a>
		@endcan
    </div>
    <!-- Content area -->
    <!-- <div class="content"> -->
        <table class="table table-hover table-striped" id="crm_start_date_hold_cv_sample">
            <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
				<th data-popup="tooltip" title="Un-searchable, Un-sortable">Sent By</th>
                <th>Name</th>
                <th>Title</th>
                <th>Postcode</th>
				<th>Phone#</th>
                <th>Landline</th>
                <th>Job Details</th>
                <th>Head Office</th>
                <th>Unit</th>
                <th>Job Postcode</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    <!-- </div> -->
</div>
