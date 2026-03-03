@extends('layouts.app')

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicant's Jobs Within</span> - 15KM
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Direct</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <div class="card-header header-elements-inline">
                <h5 class="card-title">Applicant Details</h5>
                <input type="hidden" id="hidden_applicant_id" value="{{ $applicant->id }}">
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="card border-left-3 border-left-slate rounded-left-0">
                        <div class="card-body">
                            <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                <div>
                                    Title:<span
                                            class="font-weight-semibold">{{ $applicant->applicant_job_title }}</span>
                                    <ul class="list list-unstyled mb-0">
                                        <li>Name: <span
                                                    class="font-weight-semibold">{{ $applicant->applicant_name }}</span>
                                        </li>
                                        <li>Postcode: <span
                                                    class="font-weight-semibold">{{ $applicant->applicant_postcode }}</span>
                                        </li>
                                        <li>Category: <span
                                                    class="font-weight-semibold">{{ $applicant->job_category }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
                                    Phone#:<span class="font-weight-semibold">{{ $applicant->applicant_phone }}</span>
                                    <ul class="list list-unstyled mb-0">
                                        <li>Landline: <span
                                                    class="font-weight-semibold">{{ $applicant->applicant_homePhone }}</span>
                                        </li>
                                        {{--                                        <li>Experience: <span class="font-weight-semibold">{{ $job->experience }}</span></li>--}}
                                        <li class="dropdown">
                                            Status: &nbsp;
                                            <a href="#" class="badge bg-teal align-top">{{ $applicant->status }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center">
                                        <span>
                                            <span class="font-weight-semibold"></span>
                                        </span>

                            <ul class="list-inline list-inline-condensed mb-0 mt-2 mt-sm-0">
                                <li class="list-inline-item">
                                    Created On:<span class="font-weight-semibold">{{ $applicant->created_at }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Default ordering -->
            @if (\Session::has('notFoundCv'))
                <div class="alert alert-danger alert-dismissible" style="border-left: 3px solid; border-top: 0; border-right: 0; border-bottom: 0;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {!! \Session::get('notFoundCv') !!}
                </div>
            @endif
            <div class="card">

                <h4 class="font-weight-semibold text-center" style="padding-top: 10px !important;">The Applicant CV is currently closed.</h4>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection
