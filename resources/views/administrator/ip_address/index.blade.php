@extends('layouts.app')
@section('style')
    <script>
        var columns = [
            { "data":"ip_address_added_date" },
            { "data":"ip_address_added_time" },
            { "data":"ip_address" },
            { "data":"user_name" },
            { "data":"message" },
            { "data":"status" }
        ];
        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            <?php if (\Illuminate\Support\Facades\Auth::user()->hasAnyPermission(['ip-address_edit','ip-address_enable-disable','ip-address_delete'])): ?>
                columns.push({ "data":"action" });
            <?php endif; ?>
            $('#ip_addresses').DataTable({
                "Processing": true,
                "ServerSide": true,
                "ajax":"ip-addresses/all-ip",
                "order": [],
                "columns": columns
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
                        <span class="font-weight-semibold">IP Addresses</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">IP Addresses</span>
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
                    <h5 class="card-title">IP Addresses</h5>
                    @can('ip-address_create')
                    <a href="{{ route('ip-addresses.create') }}" class="btn bg-teal legitRipple">
                        <i class="icon-plus-circle2"></i>
                        IP Address</a>
                    @endcan
                </div>

                <div class="card-body">
                    <p>Server IP Address: <?php  ?></p>
                </div>
                <table class="table" id="ip_addresses">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>IP Address</th>
                        <th>User Name</th>
                        <th>Log Message</th>
                        <th>Status</th>
                        @canany(['ip-address_edit','ip-address_enable-disable','ip-address_delete'])
                        <th>Action</th>
                        @endcanany
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
