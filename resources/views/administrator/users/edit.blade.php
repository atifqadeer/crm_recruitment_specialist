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
                        <a href="{{ route('users.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">User</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="{{ route('users.index') }}" class="breadcrumb-item">User</a>
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
                    <div class="card border-top-teal-400 border-top-3">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Edit a User</h5>
                                        <a href="{{ route('users.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    {{ Form::open(array('route'=>['users.update',$user->id],'method'=>'patch')) }}

                                    <div class="form-group">
                                        {{ Form::label('name','Name') }}
                                        {{ Form::text('name',$user->name,array('class'=>'form-control','id'=>'name')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('email','Email Address') }}
                                        {{ Form::email('email',$user->email,array('id'=>'email_address_id','class'=>'form-control')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('password','Password') }}
                                        {{ Form::password('password',array('id'=>'password_id','class'=>'form-control','placeholder' => 'Re-type Your password')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('roles','Roles') }}
                                        {!! Form::select('roles[]', ['' => 'Select User Role']+$roles,$userRole, array('class' => 'form-control form-control-select2', 'required')) !!}
                                    </div>

                                    <div class="text-right">
                                        {{ Form::button('Update <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
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

@endsection
