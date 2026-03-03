@extends('layouts.app')
@section('style')

    <script>

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#user_sample_1').DataTable();
        });

    </script>

@endsection
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
                        <span class="font-weight-semibold">Specialist Titles</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Specialist Titles</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->

        @if (session('success'))
  <div class="alert alert-success alert-dismissable custom-success-box" style="margin: 15px;">
     <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
     <strong> {{ session('success') }} </strong>
  </div>
  @elseif(session('error'))
  <div class="alert alert-success alert-dismissable custom-success-box" style="margin: 15px;">
     <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
     <strong> {{ session('error') }} </strong>
  </div>
@endif
        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Active Titles</h5>
                    <a href="{{ route('specialist_titles.create') }}" class="btn bg-teal legitRipple">
                        <i class="icon-plus-circle2"></i>
                        Specialist Title</a>
                </div>

                <div class="card-body">
                </div>
                <table class="table table-hover table-striped" id="user_sample_1">
                    <thead>
                    <tr>
                        <th>id#</th>
                        <th>Specialist Title</th>
                        <th>Title Profession</th>
                        <th>Date Time</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(!empty($all_titles))
                    @foreach($all_titles as $title)
                    <tr>
                        <td>{{ $title->id }}</td>
                        <td>{{ ucwords($title->specialist_title) }}</td>
                        <td>{{ ucwords($title->specialist_prof) }}</td>
                        <td>{{ $title->updated_at->format('d M Y h:i A') }}</td>

                        <td>
                            <div class="list-icons">
                                <div class="dropdown">
                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                        <i class="icon-menu9"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('specialist_title.edit',$title->id) }}" class="dropdown-item"> <i></i>Edit</a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()
