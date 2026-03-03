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
                        <span class="font-weight-semibold">Sales</span> - Non PSL Clients
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
						<a href="#" class="breadcrumb-item">Sales</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Non PSL Clients</span>
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
                    <h5 class="card-title">Active Non PSL Clients</h5>
                </div>

                <div class="card-body">
                    {{--With DataTables you can alter the ordering characteristics of the table at initialisation time. Using the <code>order</code> initialisation parameter, you can set the table to display the data in exactly the order that you want. The <code>order</code> parameter is an array of arrays where the first value of the inner array is the column to order on, and the second is <code>'asc'</code> or <code>'desc'</code> as required. The table below is ordered (descending) by the <code>DOB</code> column.--}}
                </div>

                <table class="table datatable-sorting table-hover table-striped">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Office Name</th>
                        <th>Office Postcode</th>
                        <th>Office Type</th>
                        <th>Contact Person</th>
                        <th>Contact Email</th>
                        <th>Contact Phone#</th>
                        <th>Notes</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($non_psl_office as $non_psl_data)
                        <tr >
                            <td>{{ $non_psl_data->office_added_date}}</td>
                            <td>{{ $non_psl_data->office_added_time}}</td>
                            <td>
                                <a data-toggle="collapse" data-target="#officeName{{$non_psl_data->id}}"
                                   href="#"
                                   style="font-weight: 500;font-family: sans-serif;text-decoration: none;">
                                    {{ $non_psl_data->office_name }}
                                </a>
                                @canany(['sale_non-psl-office-details','sale_non-psl-office-units'])
                                <div id="officeName{{ $non_psl_data->id }}" class="collapse">

                                    @can('sale_non-psl-office-details')
                                    <a href="#" class="btn bg-teal legitRipple" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#details{{$non_psl_data->id}}">
                                        <i class="icon-info3"></i> Details</a>
                                    @endcan

                                    @can('sale_non-psl-office-units')
                                        <a href="{{ Route('nonPslClientUnitDetails',$non_psl_data->id) }}" class="btn bg-teal legitRipple"><i class="icon-eye"></i> Associative Units</a>
                                    @endcan

                                    @can('sale_non-psl-office-details')
                                    <!-- Details Modal -->
                                    <div class="modal fade" tabindex="-1" id="details{{$non_psl_data->id}}" role="dialog">
                                        <div class="modal-dialog">

                                            <!-- Modal content-->
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">{{ $non_psl_data->office_name }} Details</h4>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <p style="margin-left: 15px;"><i class="fa fa-user"></i> {{ $non_psl_data->office_contact_name }}</p>
                                                            <p style="margin-left: 15px;"><i class="fa fa-phone"></i> {{ $non_psl_data->office_contact_phone }}</p>
                                                        </div>
                                                        <div class="col-6">
                                                            <p style="margin-left: 15px;"><i class="fa fa-envelope"></i> {{ $non_psl_data->office_email }}</p>
                                                            <p style="margin-left: 15px;"><i class="fa fa-globe"></i> {{ $non_psl_data->office_website }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Details Modal -->
                                    @endcan
                                </div>
                                @endcanany
                            </td>
                            <td>{{ strtoupper($non_psl_data->office_postcode) }}</td>
                            <td>{{ strtoupper($non_psl_data->office_type) }}</td>
                            <td>{{ $non_psl_data->office_contact_name }}</td>
                            <td>{{ $non_psl_data->office_email }}</td>
                            <td>{{ $non_psl_data->office_contact_phone }}</td>
                            <td>{{ $non_psl_data->office_notes }}</td>
                            <td> @if($non_psl_data->status == 'active')
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