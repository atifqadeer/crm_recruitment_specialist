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
                        <a href="{{ route('applicants.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Client</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Update</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Edit an Client</h5>
                                        <a href="{{ route('clients.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    {{ Form::open(['route'=>['clients.update',$client->id],'method'=>'PATCH' ]) }}
                                    {{--<form action="{{ route('clients.update', $client->id) }}" method="post">
                                        @method('PATCH')
                                        @csrf()--}}
                                    <div class="form-group">
                                        {{ Form::label('client_text','Name',['class'=>'col-form-label']) }}
                                        {{ Form::text('client_name',$client->client_name,['id'=>'client_id','class'=>'form-control']) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('postcode','Postcode') }}
                                        {{ Form::text('client_postcode',$client->client_postcode,['id'=>'client_postcode_id','class'=>'form-control']) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('phnumber','Phone Number') }}
                                        {{ Form::text('client_phone',$client->client_phone,['id'=>'phone_number_id','class'=>'form-control']) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('mobile','Landline') }}
                                        {{ Form::text('client_landline',$client->client_landline,array('id'=>'home_number_id','class'=>'form-control' )) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('email','Email Address') }}
                                        {{ Form::text('client_email',$client->client_email,['id'=>'email_address_id','class'=>'form-control']) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('weburl','Website') }}
                                        {{ Form::text('client_website',$client->client_website,['id'=>'website_id','class'=>'form-control']) }}
                                    </div>

                                    <div class="text-right">
                                        {{ Form::button('Save <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
                                    </div>
                                    {{ Form::close() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /form centered -->
        </div>
        <!-- /content area -->

@endsection()
