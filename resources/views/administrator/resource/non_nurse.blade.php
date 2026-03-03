@extends('layouts.app')

@section('style')

    <script>

      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          $('#non_nurse_sample_1').DataTable({
               "aoColumnDefs": [{"bSortable": false, "aTargets": [0,14]}],
               "bProcessing": true,
               "bServerSide": true,
               "aaSorting": [[0, "desc"]],
               "sPaginationType": "full_numbers",
               "sAjaxSource": "{{ url('getNonNursingJob') }}",
               "aLengthMenu": [[10, 50, 100, 500], [10, 50, 100, 500]]

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
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
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
                    <h5 class="card-title">Active Direct Resources
                    @if($value=='0')
                        - Nurses</h5>
                    @elseif($value=='1')
                        - Non-nurses
                    @endif
                    </h5>
				<a href="{{ route('idle_applicants_non_nurse') }}" class="btn bg-teal legitRipple">
                        <i class="icon-plus-circle2"></i>
                        Idle Applicants</a>
                </div>


                <div class="card-body">
                    {{--With DataTables you can alter the ordering characteristics of the table at initialisation time. Using the <code>order</code> initialisation parameter, you can set the table to display the data in exactly the order that you want. The <code>order</code> parameter is an array of arrays where the first value of the inner array is the column to order on, and the second is <code>'asc'</code> or <code>'desc'</code> as required. The table below is ordered (descending) by the <code>DOB</code> column.--}}
                </div>

                <table class="table table-hover table-striped" id="non_nurse_sample_1">
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
                        <th>Status</th>
						<th>CV Limit</th>
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
