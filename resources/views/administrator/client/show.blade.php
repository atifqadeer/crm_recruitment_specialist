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
                        <span class="font-weight-semibold">Client</span> - Details
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Client</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Details</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Inner container -->
            <div class="d-flex align-items-start flex-column flex-md-row">
                <!-- Left content -->
                <div class="w-100 order-2 order-md-1">

                    <!-- Details grid -->
                    <div class="row">
                        <div class="col-lg-3"></div>
                        <div class="col-lg-6">
                            <div class="card border-left-3 border-left-danger rounded-left-0">
                                <div class="card-body">
                                    <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                        <div>
                                            <h6 class="font-weight-semibold">{{ $client->client_name }}</h6>
                                            <ul class="list list-unstyled mb-0">
                                                <li class="font-weight-semibold">Client#: <a href="#">{{ $client->id }}</a></li>
                                                <li class="font-weight-semibold">Website: <span class="font-weight-semibold">{{$client->client_website}}</span></li>
                                            </ul>
                                        </div>

                                        <div class="text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
                                            <h6 class="font-weight-semibold"></h6>
                                            <ul class="list list-unstyled mb-0">
                                                <li>Postcode: <span class="font-weight-semibold">{{$client->client_postcode}}</span></li>
                                                <li class="dropdown">
                                                    Status: &nbsp;
                                                    <span class="badge badge-success">Active</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center">
										<span>
											<span class="badge badge-mark border-danger mr-2"></span>
											Email:
											<span class="font-weight-semibold">{{ $client->client_email }}</span>
										</span>

                                    <ul class="list-inline list-inline-condensed mb-0 mt-2 mt-sm-0">
                                        <li class="list-inline-item">
                                            Phone:{{ $client->client_phone }}
                                        </li>
                                        <a href="{{ route('clients.index') }}" class="btn bg-slate-800 legitRipple">Close</a>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Details grid -->

                </div>
                <!-- /left content -->

            </div>
            <!-- /inner container -->
        </div>
        <!-- /content area -->

@endsection()
