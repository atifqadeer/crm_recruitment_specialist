@extends('layouts.app')

@section('style')
<style>

</style>
    <script>

        $(document).ready(function() {
            var id = $("#region_id").val();
            var category = $("#category").val();
            $.fn.dataTable.ext.errMode = 'none';
            $('#nursing_sample_1').DataTable({
                "aoColumnDefs": [{"bSortable": false, "aTargets": [0,10]}],
                "Processing": true,
                "ServerSide": true,
                // "aaSorting": [[0, "desc"]],
                // "sPaginationType": "full_numbers",
                "sAjaxSource": "{{ url('/get-region-nurses-sales_close/'.$id.'/'.$category) }}",
                {{--"sAjaxSource": "{{ url('/get-region-nurses-sales/'.$id.'/'.$category) }}",--}}
                        {{--"sAjaxSource": "{{ url('/get-region-sales/'.$id.'/'.$category) }}", --}}
                "aLengthMenu": [[10, 50, 100, 500], [10, 50, 100, 500]],
               "iDisplayLength": 10,
			 //"autoWidth": false,
				 //"scrollY": false,

                "columns": [ // Define the columns to be displayed and mapped to the data returned from the server
                    { "data": "sale_added_date" }, // Column 1: sale_added_date
                    { "data": "sale_added_time" }, // Column 2: sale_added_time
                    { "data": "job_title" }, // Column 3: job_title
                    { "data": "office_name" }, // Column 4: office_name
                    { "data": "unit_name" }, // Column 5: unit_name
                    { "data": "postcode" }, // Column 6: postcode
                    { "data": "job_type" }, // Column 7: job_type
                    { "data": "experience" }, // Column 8: experience
                    { "data": "qualification" }, // Column 9: qualification
                    { "data": "salary" }, // Column 10: salary
                    { "data": "sale_note_dis" }, // Column 11: sale_note
                    { "data": "action" } // Column 11: sale_note
                ],
                "fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
                    // Adding DT_RowId attribute to each row
                    $(nRow).attr('id', 'row_' + aData.id);
                    return nRow;
                },
				

            });
            // table.destroy();

        });

    </script>

@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        {{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Resource</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i>Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Direct</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Disable Sales
                        @if($category=='44')
                            - Nurses</h5>
                    @else
                        - Non-nurses
                        @endif
                        </h5>
                </div>
                @if ($message = Session::get('error'))
                    <div class="alert alert-danger border-0 alert-dismissible mb-0 p-2">
                        <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                        <span class="font-weight-semibold">Error!</span> {{ $message }}
                    </div>
                @endif
                @if ($message = Session::get('success'))
                    <div class="alert alert-success border-0 alert-dismissible mb-0 p-2">
                        <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                        <span class="font-weight-semibold">Success!</span> {{ $message }}
                    </div>
                @endif
                <input type="hidden" name="region_id" id="region_id" value="{{$id}}"/>
                <input type="hidden" name="category" id="category" value="{{$category}}"/>

                <div class="card-body">
                    @if(\Illuminate\Support\Facades\Auth::id()==1 ||\Illuminate\Support\Facades\Auth::id()==101)
                        <a href="{{ route('sale_region.export', ['id' => $id,'job_category'=>$category]) }}" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
                            <i class="icon-cloud-upload"></i>
                            Export Sale
                        </a>
                    @endif
                </div>

                <table class="table" id="nursing_sample_1">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Job Title</th>
                        <th>Head Office</th>
                        <th>Unit</th>
                        <th>Postcode</th>
                        <th>Type</th>
                        <th>Experience</th>
                        <th>Qualification</th>
                        <th>Salary</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()
