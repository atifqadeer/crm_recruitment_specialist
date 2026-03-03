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
                        <span class="font-weight-semibold">Client</span> - Add
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Add</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->
            @if(session()->has('client_add_error'))
                <div class="alert alert-danger">
                    {{ session()->get('client_add_error') }}
                </div>

            @endif
        <!--For Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        <button type="button" class="btn btn-danger"
                                data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#myModal"
                                style="...">Replace With Note</button>
                        <!--Modal -->
                        <div class="modal fade" tabindex="-1" id="myModal" role="dialog">
                            <div class="modal-dialog">

                                <!--Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Write A Note for Client</h4>
                                        <button type="button" class="close" data-dismiss="modal">x</button>

                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Note Title</label>
                                            <input type="text" id="note_title_id" class="form-control" placeholder="NOTE TITLE">
                                        </div>

                                        <div class="form-group">
                                            <label>Write a Note</label>
                                            <textarea id="duplicate_note_for_clients_id" cols="30"
                                                      rows="5" class="form-control" placeholder="WRITE A NOTE FOR DUPLICATE CLIENTS HERE..."></textarea>
                                        </div>
                                        <input type="button" id="duplicate_note_id" class="btn btn-primary btn-block" value="Save"
                                               style="...">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </ul>
                </div>
        @endif

        <!-- End Validation Errors -->

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Add an Client</h5>
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
                                    {{ Form::open(['route'=>'clients.store','method'=>'POST']) }}
                                    {{--<form action="{{ route('clients.store') }}" method="post">
                                        @csrf()--}}
                                    <div class="form-group">
                                        {{ Form::label('head_office_text','Name',['class'=>'col-form-label']) }}
                                        {{ Form::text('client_name',null,['class'=>'form-control','id'=>'client_id','placeholder'=>'ENTER CLIENT NAME']) }}
                                        {{--<label for="head_office_text" class="col-form-label">Name</label>
                                        <input id="client_id" type="text" class="form-control" name="client_name" placeholder="ENTER CLIENT NAME">--}}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('postcode','Postcode') }}
                                        {{ Form::text('client_postcode',null,['id'=>'client_postcode_id','class'=>'form-control','placeholder'=>'ENTER CLIENT POSTCODE']) }}
                                        {{--<label for="postcode">Postcode</label>
                                        <input id="client_postcode_id" type="text" placeholder="ENTER CLIENT POSTCODE" class="form-control" name="client_postcode">--}}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('phnumber','Phone Number') }}
                                        {{ Form::text('client_phone',null,['id'=>'phone_number_id','class'=>'form-control','placeholder'=>'ENTER PHONE NUMBER']) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('mobile','Landline') }}
                                        {{ Form::text('client_landline',null,array('id'=>'home_number_id','class'=>'form-control',
                                        'placeholder' => 'ENTER CLIENT LANDLINE')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('email','Email Address') }}
                                        {{ Form::text('client_email',null,['id'=>'email_address_id','class'=>'form-control','placeholder'=>'ENTER EMAIL ADDRESS']) }}
                                        {{--<label for="email">Email Address</label>
                                        <input id="email_address_id" type="text" name="client_email" placeholder="ENTER EMAIL ADDRESS" class="form-control">--}}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('weburl','Website') }}
                                        {{ Form::text('client_website',null,['id'=>'website_id','class'=>'form-control','placeholder'=>'ENTER WEBSITE ADDRESS']) }}
                                        {{--<label for="weburl">Website</label>
                                        <input id="website_id" type="text" name="client_website" placeholder="ENTER WEBSITE ADDRESS" class="form-control">--}}
                                    </div>

                                    <div class="text-right">
                                        {{ Form::button('Save <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
                                        {{--<button type="submit" class="btn bg-teal legitRipple">Save <i class="icon-paperplane ml-2"></i></button>--}}
                                    </div>
                                    {{ Form::close() }}
                                    {{--</form>--}}
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
