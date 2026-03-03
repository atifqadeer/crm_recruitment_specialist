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
                        <span class="font-weight-semibold">Applicants</span> - Complete History
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">History</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Invoice template -->
            <div class="card">
                <div class="card-header bg-transparent header-elements-inline">
                    <h6 class="card-title"><b>{{ $applicant_name->applicant_name }}</b>'s History</h6>
                </div>

                <div class="card-body">

                    <div class="d-md-flex flex-md-wrap">
                        <div class="mb-4 mb-md-2">
                            <span class="text-muted">Quality Details:</span>
                            <ul class="list list-unstyled mb-0">
                                <li><b>Applicant#:</b>{{ $applicant_in_quality->applicant_id }}</li>
                                <li><b>Sale#:</b>{{ $applicant_in_quality->sale_id }}</li>
                                <li><b>Added On:</b>{{ $applicant_in_quality->quality_added_date }}</li>
                                <li><b>Added Time:</b>{{ $applicant_in_quality->quality_added_time }}</li>
                                <li><b>Status:</b>{{ $applicant_in_quality->moved_tab_to }}</li>
                                <li><b>Notes:</b>{{ $applicant_in_quality->details }}</li>
                            </ul>
                        </div>

                        <div class="mb-2 ml-auto">
                            <span class="text-muted">Track Applicant:</span>
                            <div class="d-flex flex-wrap wmin-md-400">
                                <ul class="list list-unstyled mb-0">
                                    <li><h5 class="my-2">Applicant Name:</h5></li>
                                    <li>Applicant Title:</li>
                                    <li>Applicant Postcode:</li>
                                    <li>Currently Active Stage:</li>
                                    <li>Currently Active Sub Stage:</li>
                                    <li>Added On:</li>
                                    <li>Added Time:</li>
                                </ul>

                                <ul class="list list-unstyled text-right mb-0 ml-auto">
                                    <li><h5 class="font-weight-semibold my-2">{{ $track_applicant_in_crm->applicant_name }}</h5></li>
                                    <li><span class="font-weight-semibold">{{ $track_applicant_in_crm->applicant_job_title }}</span></li>
                                    <li>{{ $track_applicant_in_crm->applicant_postcode }}</li>
                                    <li>{{ $track_applicant_in_crm->stage }}</li>
                                    <li>{{ $track_applicant_in_crm->sub_stage }}</li>
                                    <li><span class="font-weight-semibold">{{ $track_applicant_in_crm->history_added_date }}</span></li>
                                    <li><span class="font-weight-semibold">{{ $track_applicant_in_crm->history_added_time }}</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h5>Applicant's Activity In CRM</h5>
                </div>
                <table class="table datatable-sorting data_table">
                    <thead>
                    <tr>
                        <th>Id</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Active In</th>
                        <th>Notes</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 0;?>
                    @foreach($applicant_in_crm as $applicant)
                        <tr>
                            <td>{{$i++}}</td>
                            <td>{{ $applicant->crm_added_date }}</td>
                            <td>{{ $applicant->crm_added_time }}</td>
                            <td>{{ $applicant->moved_tab_to }}</td>
                            <td>{{ $applicant->details }}</td>
                            <td>@if($applicant->status == 'active')
                                    <h5><span class="badge badge-success">Active</span></h5>
                                @else
                                    <h5><span class="badge badge-danger">Disable</span></h5>
                                @endif
                            </td>
                        </tr>
                    @endforeach()
                    </tbody>
                </table>
            </div>
            <!-- /invoice template -->
        </div>
        <!-- /content area -->

@endsection()
