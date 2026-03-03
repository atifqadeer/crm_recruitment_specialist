@extends('layouts.app')
@section('style')
    <script>

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#activity_logs').DataTable({
                "Processing": true,
                "ServerSide": true,
                "ajax":"user-logs/{{ $user->id }}",
                "order": [],
                "columns": [
                    { "data":"audit_added_date" },
                    { "data":"audit_added_time" },
                    { "data":"message" },
                    { "data":"details" },
                    { "data":"module" }
                ]
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
                        <span class="font-weight-semibold">Activity Logs</span> - {{ $user->name }}
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Users</a>
                        <span class="breadcrumb-item active">Activity Logs</span>
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
                    <h5 class="card-title">
                        Activity Logs
                    </h5>

{{--                    <div class="form-group d-inline-flex w-lg-50">--}}

{{--                        <select data-placeholder="Select user name" class="form-control form-control-select2" data-fouc>--}}
{{--                            <option></option>--}}
{{--                            <option value="all-users">All Users</option>--}}
{{--                            @foreach($users as $user)--}}
{{--                                <option value="{{ $user->id }}">{{ $user->name }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}

{{--                        <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>--}}

{{--                        <div class="input-group">--}}
{{--                            <input type="text" class="form-control daterange-left" value="03/18/2013 - 03/23/2013">--}}
{{--                            <span class="input-group-append">--}}
{{--                                <span class="input-group-text"><i class="icon-calendar22"></i></span>--}}
{{--                            </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                </div>

                <div class="card-body">
                </div>
                <table class="table" id="activity_logs">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Message</th>
                        <th>Details</th>
                        <th>Module</th>
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
