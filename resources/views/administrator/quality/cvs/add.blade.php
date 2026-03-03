<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 4/25/2019
 * Time: 11:42 AM
 */?>

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
                        <span class="font-weight-semibold">Applicants</span> - CVs
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Quality</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">CVs</span>
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
                    <h5 class="card-title">Added Applicants With Their CVs</h5>
                </div>

                <div class="card-body">
                </div>

                <table class="table datatable-sorting"> {{--table-responsive--}}
                    <thead>
                    <tr class="no-gutters">
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Postcode</th>
                        <th>Email Address</th>
                        <th>Resume</th>
                        <th class="d-inline-bloc col-3">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($applicant_with_cvs as $applicant)
                        <tr>
                            <td >{{ $applicant->applicant_name }}</td>
                            <td >{{ strtoupper($applicant->applicant_job_title) }}</td>
                            <td >{{ $applicant->applicant_postcode }}</td>
                            <td >{{ $applicant->applicant_email }}</td>
                            <td >
                                <a href="{{ route('downloadCv',$applicant->id) }}"
                                   class="btn btn-link legitRipple">
                                    <i class="icon-arrow-down132"></i>
                                    {{ substr($applicant->applicant_cv,8) }}
                                </a>
                            </td>

                            <td >
                                <a href="{{ route('updateToSentCV',['id'=>$applicant->id , 'viewString'=>'applicantWithAddCv']) }}" class="btn bg-teal legitRipple">Sent</a>
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

