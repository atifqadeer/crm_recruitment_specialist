@extends('layouts.app')

@section('style')

    <script>

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#nurseryTable').DataTable({
                "aoColumnDefs": [{"bSortable": false, "aTargets": [0,12]}],
                "bProcessing": true,
                "bServerSide": true,
                "aaSorting": [[0, "desc"]],
                "sPaginationType": "full_numbers",
                "sAjaxSource": "{{ url('getNurseryJob') }}",
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
                        <span class="font-weight-semibold">Resource - Nursery</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <span class="breadcrumb-item">Direct</span>
						<a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Nursery</span>
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
                    <h5 class="card-title">Active Direct Resources - Nursery</h5>
                       
                </div>


                <div class="card-body">
                   
                </div>

                <table class="table table-hover table-striped" id="nurseryTable">
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
