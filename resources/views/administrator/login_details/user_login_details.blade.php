@extends('layouts.app')
@section('style')

    <script>

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#users_login_details').DataTable({
        "order": [[ 5, "desc" ]]
    });
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
                        <span class="font-weight-semibold">User</span> - Details
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">User</a>
                        <span class="breadcrumb-item active">Details</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Users Login Details</h5>
                    <!-- @can('user_create')
                    <a href="{{ route('users.create') }}" class="btn bg-teal legitRipple">
                        <i class="icon-plus-circle2"></i>
                        User</a>
                    @endcan -->
                </div>

                <div class="card-body">
                </div>
                <table class="table table-hover table-striped" id="users_login_details">
                    <thead>
                    <tr>
                        <th>User#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Login Time</th>
                        <th>Login Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(!empty($users_data))
                    @foreach($users_data as $user)
                    <tr>
                        <td>{{ $sr_no++ }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{date("g:i A", strtotime($user->att_time))}}</td>
                        <td>{{ $user->login_date }}</td>
                        <td>
                            <!-- <div class="list-icons"> -->
                                <!-- <div class="dropdown"> -->
                                    <!-- <a href="#" class="list-icons-item" data-toggle="dropdown">
                                        <i class="icon-menu9"></i>
                                    </a> -->
                                    <!-- <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('view_login_details',$user->userId) }}" class="dropdown-item"> <i></i>View Details</a>

                                    </div> -->
                                <!-- </div> -->
                            <!-- </div> -->
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
