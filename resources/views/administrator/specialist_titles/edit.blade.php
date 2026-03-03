@extends('layouts.app')

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
    <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="{{ route('specialist_titles.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Specialist Title</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="{{ route('specialist_titles.index') }}" class="breadcrumb-item">Specialist Title</a>
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
                                        <h5 class="card-title">Edit a specialist title</h5>
                                        <a href="{{ route('specialist_titles.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    {{ Form::open(array('route'=>['specialist_titles.update',$special_title->id],'method'=>'patch')) }}

                                    
                                    <div class="form-group">
                                        {{ Form::label('specialist_title','specialist_title') }}
                                        {{ Form::text('specialist_title',$special_title->specialist_title,array('class'=>'form-control','id'=>'specialist_title', 'readonly' => 'true')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::text('id',$special_title->id,array('id'=>'id', 'hidden' => 'true')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('specialist_prof','Specialist Profession') }}
                                        {{ Form::text('specialist_prof',$special_title->specialist_prof,array('id'=>'specialist_prof','class'=>'form-control')) }}
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
