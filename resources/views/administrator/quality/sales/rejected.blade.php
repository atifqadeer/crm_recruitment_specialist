@extends('layouts.app')

@section('style')

    <script>
        var table;
        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            table = $('#sale_rejected_sample_1').DataTable({
                "aoColumnDefs": [{"bSortable": false, "aTargets": [0,10]}],
                "bProcessing": true,
                "bServerSide": true,
                "aaSorting": [[0, "desc"]],
                "sPaginationType": "full_numbers",
                "sAjaxSource": "{{ url('get-rejected-sales') }}",
                "aLengthMenu": [[10, 50, 100, 500], [10, 50, 100, 500]],
                "drawCallback": function( settings, json){
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

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Quality</span> - Sales
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Quality</a>
                        <a href="#" class="breadcrumb-item">Sales</a>
                        <span class="breadcrumb-item active">Rejected</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <div class="header-elements col-md-10 justify-content-end">
                    </div>
                </div>

                <table class="table table-hover table-striped" id="sale_rejected_sample_1">
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
                        <th>Status</th>
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
