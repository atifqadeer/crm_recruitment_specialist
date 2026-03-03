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
                        <span class="font-weight-semibold">Applicants With No Interest</span> - Reason
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

            <!-- Default ordering -->
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Active Applicants Note for no interest in job</h5>
                </div>

                <div class="card-body">
                    <div class="media flex-column flex-sm-row">

                        <div class="media-body">
                            <h6 class="media-title font-weight-semibold">
                                <a href="#">Reason For No Taking interest</a>
                            </h6>
                            @foreach($reason_note as $note)
                                <b>Reason:</b>{{$note}}
                                @endforeach
                        </div>
                    </div>
                </div>

            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()
