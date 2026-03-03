@extends('layouts.app')

@section('style')

    <script>
        var columns = [
            { "data":"created_at", "name": "created_at" },
            { "data":"updated_at", "name": "updated_at" },
            { "data":"job_category", "name": "job_category" },
            { "data":"job_title", "name": "job_title" },
            { "data":"head_office", "name": "head_office" },
            { "data":"unit", "name": "unit" },
            { "data":"postcode", "name": "postcode"},
            { "data":"type", "name": "type" },
            { "data":"experience", "name": "experience" },
            { "data":"qualification", "name": "qualification"},
            { "data":"salary", "name": "salary" },
            { "data":"sent_cv", "name": "sent_cv" },

        ];
        var table;
        $(document).ready(function() {
            var start_date=$('#start_date').val();
            var end_date=$('#end_date').val();
            var url = "open_sale_detail_weekly" + '/' + start_date + '/' +end_date;
            $("#sale_filter_office").select2({ width: '99%' });
            $.fn.dataTable.ext.errMode = 'none';
            table = $('#sale_sample_1').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax":url,
                "order": [[ 1, 'desc' ]],
                "columns": columns,
                "drawCallback": function( row, data ) {
                    $('[data-popup="tooltip"]').tooltip();
                }

            });

            // table.destroy();

        });
    </script>

@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <input type="hidden" name="start_date" id="start_date" value="{{$start_date}}">
        <input type="hidden" name="end_date" id="end_date" value="{{$end_date}}">
        <!-- Page header -->
        {{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Sales</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Sales</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Open</span>
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
                    <div class="col-md-2">
                        <h5 class="card-title">Open Sales Weekly</h5>
                    </div>
                    <div class="header-elements col-md-10 justify-content-end">
                        <div class="col-md-12" style="padding-right: 0 !important;">

                        </div>
                    </div>
                </div>

                <div class="card-body row pb-0">
                    <div class="col-md-6">
                        @if ($message = Session::get('error'))
                            <div class="alert alert-danger border-0 alert-dismissible mb-0 p-2">
                                <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Error!</span> {{ $messsage }}
                            </div>
                        @endif
                        @if ($message = Session::get('success'))
                            <div class="alert alert-success border-0 alert-dismissible mb-0 p-2">
                                <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Success!</span> {{ $message }}
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">

                    </div>
                </div>


                <table class="table table-striped" id="sale_sample_1">
                    <thead>
                    <tr>
                        <th>Created Date</th>
                        <th>Updated Date</th>
                        <th>Category</th>
                        <th>Job Title</th>
                        <th>Head Office</th>
                        <th>Unit</th>
                        <th>Postcode</th>
                        <th>Type</th>
                        <th>Experience</th>
                        <th>Qualification</th>
                        <th>Salary</th>
                        <th>Sent CVs</th>
                        {{--                        <th>Action</th>--}}
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
