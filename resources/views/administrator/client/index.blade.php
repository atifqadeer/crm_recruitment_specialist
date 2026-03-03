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
                        <span class="font-weight-semibold">Clients</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Clients</span>
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
                    <h5 class="card-title">Active Clients</h5>
                    <a href="{{ route('clients.create') }}" class="btn bg-teal legitRipple">
                        <i class="icon-plus-circle2"></i> Client</a>
                </div>

                <div class="card-body">
                    <a href="#" class="btn bg-slate-800 legitRipple float-right">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Import</a>
                </div>
                <table class="table datatable-sorting data_table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Postcode</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th>Landline</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <td>{{ $client->client_name }}</td>
                            <td>{{ $client->client_postcode }}</td>
                            <td>{{ $client->client_email }}</td>
                            <td>{{ $client->client_phone }}</td>
                            <td>{{ $client->client_landline }}</td>
                            <td> @if($client->status == 'active')
                                    <h5><span class="badge badge-success">Active</span></h5>
                                     @else
                                    <h5><span class="badge badge-danger">Disable</span></h5>
                                @endif()
                            </td>
                            <td>
                                <div class="list-icons">
                                    <div class="dropdown">
                                        <a href="#" class="list-icons-item" data-toggle="dropdown">
                                            <i class="icon-menu9"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="{{ route('clients.edit',$client->id) }}" class="dropdown-item"> Edit</a>
                                            <a href="{{ route('clients.show',$client->id) }}" class="dropdown-item"> View </a>
                                            {{ Form::open(['route'=>['clients.destroy',$client->id],'method'=>'POST']) }}
                                            @method('DELETE')
                                            @if($client->status=="active")
                                                {{ Form::button('Disable',['type'=>'submit','class'=>'dropdown-item']) }}
                                            @elseif($client->status=="disable")
                                                {{ Form::button('Enable',['type'=>'submit','class'=>'dropdown-item']) }}
                                            @endif

                                            {{ Form::close() }}

                                        </div>
                                    </div>
                                </div>
                            </td>


                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>

        <!-- /content area -->

@endsection()
