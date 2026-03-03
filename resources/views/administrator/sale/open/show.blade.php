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
                        <span class="font-weight-semibold">Sale</span> - Details
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        @if($sale->status == 'active')
                            <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Sale</a>
                            <a href="#" class="breadcrumb-item">Open</a>
                        @elseif($sale->status == null)
                            <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Quality</a>
                            <a href="#" class="breadcrumb-item">Sale</a>
                        @endif
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
                    <div class="row justify-content-center">
{{--                        <div class="col-lg-3"></div>--}}
                        <div class="col-lg-10">
                            <div class="card border-left-3 border-left-danger rounded-left-0">
                                <div class="card-body">
                                    <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                        <div>
                                            <h6 class="font-weight-semibold ">Job Title: <span class="text-uppercase">
												{{ $sale->job_title}} <?php echo $sale->job_title_prof && $sale->job_title_prof!=''? ' ('. ucwords($sec_job_data->specialist_prof).')':'';?></span></h6>
                                            <ul class="list list-unstyled mb-0">
                                                <li class="font-weight-semibold">Qualification: <span class="font-weight-semibold">{{ $sale->qualification }}</span></li>
                                                <li><span class="font-weight-semibold">Salary:</span> {{ $sale->salary }}</li>
                                                <li><span class="font-weight-semibold">Benefits:</span> {{ $sale->benefits }}</li>
                                            </ul>
                                        </div>

                                        <div class="col-md-4 text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
                                            <h6 class="font-weight-semibold text-capitalize">Job Type: {{ $sale->job_type }}</h6>
                                            <ul class="list list-unstyled mb-0">
                                                <li>Postcode: <span class="font-weight-semibold">{{$sale->postcode}}</span></li>
                                                <li>Experience: <span class="font-weight-semibold">{{$sale->experience}}</span></li>
                                                <li class="dropdown">
                                                    Status: &nbsp;
                                                    <span class="badge badge-{{ $sale->status ? 'success' : 'warning' }} text-capitalize">{{ $sale->status ? $sale->status : 'Pending' }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center">
                                    <span>
                                        <span class="badge badge-mark border-danger mr-2"></span>
                                        Head Office: <span class="font-weight-semibold">{{ $sale->office->office_name }}</span>
                                    </span>

                                    <ul class="list-inline list-inline-condensed mb-0 mt-2 mt-sm-0">
                                        <li class="list-inline-item">
                                            Unit: {{ $sale->unit->unit_name }}
                                        </li>

                                        <li class="list-inline-item">
                                            Timing: {{ $sale->timing }}
                                        </li>
                                        <?php $back_url = ($sale->status && ($sale->status == 'active')) ? 'sales.index' : 'quality-sales'; ?>
                                        <a href="{{ route($back_url) }}" class="btn bg-slate-800 legitRipple">Close</a>
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

@endsection
