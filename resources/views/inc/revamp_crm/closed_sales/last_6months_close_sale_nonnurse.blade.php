<!-- Main content -->
<div class="content-wrapper">
	<div class="row">
    <div class="col-4">
        <form id="close_sale_nonnurse_form" class=" d-flex align-items-center">
			<svg xmlns="http://www.w3.org/2000/svg" height="15px" width="15px" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M3.9 54.9C10.5 40.9 24.5 32 40 32H472c15.5 0 29.5 8.9 36.1 22.9s4.6 30.5-5.2 42.5L320 320.9V448c0 12.1-6.8 23.2-17.7 28.6s-23.8 4.3-33.5-3l-64-48c-8.1-6-12.8-15.5-12.8-25.6V320.9L9 97.3C-.7 85.4-2.8 68.8 3.9 54.9z"/></svg>
            <select name="office_id" class="form-control office_id" id="office_id_nonnurse">
                <option value="" selected disabled>Select Head Office</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->office_name }}</option>
                @endforeach
            </select>
            <button id="clear_filter_close_nonnurse_btn" title="Refresh" class="btn btn-outline alpha-teal text-teal-400 border-teal-400 border-2 float-right ml-1">
                <i class="fas fa-sync"></i>
            </button>
        </form>
    </div>
		 <div class="col-4"></div>
        <div class="col-4">
			@can('sale_export_email')
             <a href="{{ route('sales.export_email',['job_category'=>'nonnurse','duration' => 'last_6_months'])}}" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
                <i class="icon-cloud-upload"></i>
                &nbsp;Export Email</a>
			@endcan
        </div>
    </div>
    <!-- Content area -->
     <!-- <div class="content"> -->
        <table class="table table-hover table-striped" id="close_sale_nonnurse_sample">
            <thead>
            <tr>
                <th>Created Date</th>
                <th>Closed Date</th>
                <th>Agent By</th>
                <th>Job Title</th>
                <th>Head Office</th>
                <th>Unit</th>
                <th>Postcode</th>
                <th>Type</th>
                <th>Experience</th>
                <th>Qualification</th>
                <th>Salary</th>
                <th>Status</th>
                <th>Notes</th>
                @canany(['sale_open','sale_closed-sale-notes'])
                    <th>Action</th>
                @endcanany
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
     <!-- </div> -->
</div>
