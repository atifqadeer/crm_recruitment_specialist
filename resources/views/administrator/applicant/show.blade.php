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
                        <span class="font-weight-semibold">Applicants</span> - Details
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
						<a href="#" class="breadcrumb-item">Applicants</a>
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
                                            <h6 class="font-weight-semibold">Name: {{ ucwords(strtolower($applicant->applicant_name)) }}</h6>
                                            <ul class="list list-unstyled mb-0">
												<li><strong>Job Title:</strong> {{ strtoupper($applicant->applicant_job_title) }} 
						<?php echo $sec_job_data && $sec_job_data->specialist_prof != '' ? ' ('. $sec_job_data->specialist_prof.')':'';?></li>
												<li><strong>Mobile:</strong> {{ $applicant->applicant_phone }}</li>
												<li><strong>Landline:</strong> {{ $applicant->applicant_homePhone }}</li>
												<li><strong>Email:</strong> <a href="mailto:{{ $applicant->applicant_email }}">{{ $applicant->applicant_email }}</a></li>
                                            </ul>
                                        </div>

                                        <div class="text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
                                            <h6 class="font-weight-semibold">ID#: {{ $applicant->id }}</h6>
                                            <ul class="list list-unstyled mb-0">
												<li><strong>Source:</strong> {{ ucwords($applicant->applicant_source) }}</li>
                                                <li><strong>Postcode:</strong> {{ strtoupper($applicant->applicant_postcode) }}</li>
                                                <li class="dropdown">
                                                    <strong>Status:</strong> &nbsp;
                                                    <span class="badge badge-success">Active</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-sm-flex justify-content-sm-end align-items-sm-center">
									 <a href="{{ route('applicants.index') }}" class="btn bg-slate-800 legitRipple">Close</a>
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
