@extends('layouts.app')

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
                        <span class="font-weight-semibold"><b>{{ ucwords($office_name) }}</b>'s Unit</span> - All
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
                    <h5 class="card-title">Active Units</h5>
                </div>

                <div class="card-body">
                    {{--With DataTables you can alter the ordering characteristics of the table at initialisation time. Using the <code>order</code> initialisation parameter, you can set the table to display the data in exactly the order that you want. The <code>order</code> parameter is an array of arrays where the first value of the inner array is the column to order on, and the second is <code>'asc'</code> or <code>'desc'</code> as required. The table below is ordered (descending) by the <code>DOB</code> column.--}}
                </div>

                <table class="table datatable-sorting">
                    <thead>
                    <tr>
                        <th>Unit Name</th>
                        <th>Unit Postcode</th>
                        <th>Manager Name</th>
                        <th>Manager Phone Number</th>
                        <th>Manager Email</th>
                        <th>Website</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($units_list as $unit)
                        <tr>
                            <td>{{ $unit->unit_name }}</td>
                            <td>{{ $unit->unit_postcode}}</td>
                            <td>{{ $unit->contact_name}}</td>
                            <td>{{ $unit->contact_phone_number }}</td>
                            <td>{{ $unit->contact_email}}</td>
                            <td>{{ $unit->website}}</td>
                            <td>@if($unit->status == 'active')
                                    <h5><span class="badge badge-success">Active</span></h5>
                                @else
                                    <h5><span class="badge badge-danger">Disable</span></h5>
                            @endif()
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()
