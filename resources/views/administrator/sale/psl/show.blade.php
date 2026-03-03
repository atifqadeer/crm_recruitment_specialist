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
                        <span class="font-weight-semibold">All Units</span> - PSL
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">PSL Clients Units</span>
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
                    <h5 class="card-title">Active PSL Clients Units</h5>
                </div>

                <div class="card-body">
                    {{--With DataTables you can alter the ordering characteristics of the table at initialisation time. Using the <code>order</code> initialisation parameter, you can set the table to display the data in exactly the order that you want. The <code>order</code> parameter is an array of arrays where the first value of the inner array is the column to order on, and the second is <code>'asc'</code> or <code>'desc'</code> as required. The table below is ordered (descending) by the <code>DOB</code> column.--}}
                </div>

                <table class="table datatable-sorting">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Unit</th>
                        <th>Unit Postcode</th>
                        <th>Unit Name</th>
                        <th>Unit Phone#</th>
                        <th>Unit Email</th>
                        <th>Notes</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($units as $unit)
                        <tr >
                            <td>{{ $unit->units_added_date }}</td>
                            <td>{{ $unit->units_added_time }}</td>
                            <td>
                                <a data-toggle="collapse" class="" data-target="#unitName{{$unit->id}}"
                                   href="#"
                                   style="font-weight: 500;font-family: sans-serif;text-decoration: none;">
                                    {{ $unit->unit_name }}
                                </a>
                                <div id="unitName{{ $unit->id }}" class="collapse">
                                    <a href="#" class="btn bg-teal legitRipple" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#details{{$unit->id}}" >
                                        <i class="icon-info3"></i> Details</a>
                                    <!-- Details Modal -->
                                    <div class="modal fade" tabindex="-1" id="details{{$unit->id}}" role="dialog">
                                        <div class="modal-dialog">

                                            <!-- Modal content-->
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">{{ $unit->unit_name }} Details</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <p style="margin-left: 15px;"><i class="fa fa-user"></i> {{ $unit->contact_name }}</p>
                                                            <p style="margin-left: 15px;"><i class="fa fa-phone"></i> {{ $unit->contact_phone_number }}</p>
                                                        </div>
                                                        <div class="col-6">
                                                            <p style="margin-left: 15px;"><i class="fa fa-envelope"></i> {{ $unit->contact_email }}</p>
                                                            <p style="margin-left: 15px;"><i class="fa fa-globe"></i> {{ $unit->website }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Details Modal -->
                                </div>
                            </td>
                            <td>{{ $unit->unit_postcode }}</td>
                            <td>{{ $unit->contact_name }}</td>
                            <td>{{ $unit->contact_phone_number }}</td>
                            <td>{{ $unit->contact_email }}</td>
                            <td>{{ $unit->units_notes }}</td>
                            <td> @if($unit->status == 'active')
                                    <h5><span class="badge badge-success">Active</span></h5>
                                @endif()
                            </td>
                        </tr>

                    @endforeach()
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()