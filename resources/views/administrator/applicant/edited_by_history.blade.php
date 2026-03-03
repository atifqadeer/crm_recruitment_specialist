@extends('layouts.app')

@section('style')

    <script>

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            var applicant = $("#hidden_applicant_id").val();
            // alert(applicant);

            $('#jobs_within_15km_sample').DataTable({
                "Processing": true,
                "ServerSide": true,
                "ajax":"{!! url('get_edited_by_history') !!}/"+applicant,
                "order": [],
                "columns": [
                    { "data":"date" },
                    { "data":"time", "orderable": false },
                    { "data":"user_name" ,},
                    { "data":"applicant_name", },
                    { "data":"column_name", "orderable": false },


                ],
                // "rowCallback": function( row, data ) {
                //     var dateCell = data.updated_at;
                //     var sortedDate = dateSorting (dateCell);
                //     $('td:eq(0)', row).html(sortedDate);
                // }
                /***
                 ,
                 "columnDefs": [
                 {
                        "width": "10%",
                        "targets": 0
                    }
                 ],
                 "drawCallback": function (response) {
                    console.log('iDrawError ', response.iDrawError);
                 }
                 */
            });

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
                        <span class="font-weight-semibold">Applicant's Edited BY </span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Applicant</a>
                        <a href="#" class="breadcrumb-item">Current</a>
{{--                        <span class="breadcrumb-item active">Direct</span>--}}
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <div class="card-header header-elements-inline">
{{--                <h5 class="card-title">Applicant Details</h5>--}}
                <input type="hidden" id="hidden_applicant_id" value="{{ $applicantHistory->applicant_id}}">
            </div>

            <!-- Default ordering -->

            <div class="card-header header-elements-inline">
                <h5 class="card-title">Active Applicant's Edited BY</h5>
            </div>
            <div class="card">

                <div class="card-body">
                    {{--With DataTables you can alter the ordering characteristics of the table at initialisation time. Using the <code>order</code> initialisation parameter, you can set the table to display the data in exactly the order that you want. The <code>order</code> parameter is an array of arrays where the first value of the inner array is the column to order on, and the second is <code>'asc'</code> or <code>'desc'</code> as required. The table below is ordered (descending) by the <code>DOB</code> column.--}}
                </div>

                <table class="table"  id="jobs_within_15km_sample">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Edited By</th>
                        <th>Applicant Name</th>
                        <th>Column Name</th>

                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

        @endsection

        @section('script')

@endsection