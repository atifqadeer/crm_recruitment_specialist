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
                        <a href="#"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Send Message</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">User Messages</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Send Message</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <div class="row">
                <div class="col-md-3">

                </div>
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Send Message To Applicants</h5>
                                        <!-- <a href="{{ route('applicants.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <form id="form_send_message" action="#">
                                 @csrf
                                    <div class="form-group">
                                        <label>Enter Applicant Numbers:</label>
                                        </div>
                                    <div class="form-group border border-light rounded">
                                            <textarea  id="applicant_numbers" cols="20"
                                                       rows="3" class="form-control"
                                                       placeholder="Please enter comma seperated applicant numbers like 07500000000,07500000000"></textarea>
                                        </div>
                                        <div class="form-group">
                                        <label>Message:</label>
                                        </div>
                                        <div class="form-group border border-light rounded">
                                            <textarea  id="applicant_message" cols="40"
                                                       rows="10" required class="form-control"
                                                       placeholder="Write a message..."></textarea>
                                        </div>
                                    <div class="text-right">
                                        <a href="javascript:;" class="btn bg-teal legitRipple" id="send_message_app" ><i class="icon-paperplane"></i> Send</a>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /form centered -->
        </div>
		        @include('layouts.small_chat_box')

        <!-- /content area -->
@section('script')

@endsection 
@endsection
