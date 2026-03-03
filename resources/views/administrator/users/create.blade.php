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
                        <span class="font-weight-semibold">User</span> - Add
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="{{ route('users.index') }}" class="breadcrumb-item">User</a>
                        <span class="breadcrumb-item active">Add</span>
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
                <div class="col-md-5">
                    <div class="card border-top-teal-400 border-top-3">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Add a User</h5>
                                        <a href="{{ route('users.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
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
                                    {{ Form::open(array('route' => 'users.store','method' => 'POST' )) }}
                                    <div class="form-group">
                                        {{ Form::label('name','Name') }}
                                        {{ Form::text('name', null, array('id' => 'name', 'class' => 'form-control',
                                         'placeholder' => 'ENTER USER NAME')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('email_address', 'Email Address', array('class'=>'col-form-label')) }}
                                        {{ Form::email('email', null, array('id'=>'email_address_id','class'=>'form-control',
                                        'placeholder' => 'ENTER USER EMAIL ADDRESS')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('password', 'Password', array('class'=>'col-form-label')) }}
                                        {{ Form::password('password', array('id'=>'password_id','class'=>'form-control',
                                        'placeholder' => 'ENTER USER PASSWORD')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('roles','Roles') }}
                                        {!! Form::select('roles[]', ['' => 'Select User Role']+$roles,null, array('class' => 'form-control form-control-select2', 'required')) !!}
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
