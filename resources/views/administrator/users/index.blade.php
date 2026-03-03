@extends('layouts.app')

@section('style')
    <style>
        /* Custom CSS for centering tabs and styling active tab */
        .nav-tabs {
            display: flex;
            justify-content: center;
            border-bottom: none; /* Remove default border */
        }

        .nav-tabs .nav-item {
            margin: 0 5px; /* Add spacing between tabs */
        }

        .nav-tabs .nav-link {
            color: #333; /* Default tab text color */
            border: 1px solid #ddd; /* Add border to tabs */
            border-radius: 5px 5px 0 0; /* Rounded corners for tabs */
            padding: 10px 50px; /* Add padding to tabs */
            transition: all 0.3s ease; /* Smooth transition for hover and active states */
        }

        .nav-tabs .nav-link.active {
            background-color: #009688; /* Green background for active tab */
            color: white; /* White text for active tab */
            border-color: #4CAF50; /* Match border color with background */
        }

        .nav-tabs .nav-link:hover {
            background-color: #009688; /* Light background on hover */
            border-color: #ddd; /* Match border color with background */
			color: #fff;
        }
    </style>

    <script>
        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#active_users_table').DataTable();
            $('#inactive_users_table').DataTable();
        });
    </script>
@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Users</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Users</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->

        <!-- Content area -->
        <div class="content">

            <!-- Tabs for Active and Inactive Users -->
            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Users</h5>
                    @can('user_create')
                        <a href="{{ route('users.create') }}" class="btn bg-teal legitRipple">
                            <i class="icon-plus-circle2"></i>
                            User
                        </a>
                    @endcan
                </div>

                <div class="card-body">
                    <!-- Tab navigation -->
                    <ul class="nav nav-tabs nav-tabs-bottom">
                        <li class="nav-item">
                            <a href="#active_users" class="nav-link active" data-toggle="tab">Active Users</a>
                        </li>
                        <li class="nav-item">
                            <a href="#inactive_users" class="nav-link" data-toggle="tab">Inactive Users</a>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content">
                        <!-- Active Users Tab -->
                        <div class="tab-pane fade show active" id="active_users">
                            <table class="table table-hover table-striped" id="active_users_table">
                                <thead>
                                    <tr>
                                        <th>Sr#</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        @canany(['user_edit','user_enable-disable','user_activity-log'])
                                            <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $serial = 1; // Initialize serial number
                                    @endphp
                                    @if(!empty($users))
                                        @foreach($users as $user)
                                            @if($user->is_active == 1)
                                                <tr>
                                                    <td>{{ $serial++ }}</td>
                                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                                    <td>{{ $user->created_at->format('h:i A') }}</td>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <?php $roles = implode($user->roles->pluck('name','name')->all()); ?>
                                                    <td>{{ empty($roles) ? '---' : ucwords($roles) }}</td>
                                                    <td>
                                                        <h5><span class="badge badge-success">Enabled</span></h5>
                                                    </td>
                                                    @canany(['user_edit','user_enable-disable','user_activity-log'])
                                                        <td>
                                                            <div class="list-icons">
                                                                <div class="dropdown">
                                                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                                        <i class="icon-menu9"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                        @can('user_edit')
                                                                            <a href="{{ route('users.edit',$user->id) }}" class="dropdown-item"> <i></i>Edit</a>
                                                                        @endcan
                                                                        @can('user_enable-disable')
                                                                            <a href="{{ route('userStatus',$user->id) }}" class="dropdown-item"><i></i>Disable</a>
                                                                        @endcan
                                                                        @can('user_activity-log')
                                                                            <a href="{{ route('activityLogs',$user->id) }}" class="dropdown-item"> <i></i>Activity Logs</a>
                                                                        @endcan
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endcanany
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Inactive Users Tab -->
                        <div class="tab-pane fade" id="inactive_users">
                            <table class="table table-hover table-striped" id="inactive_users_table">
                                <thead>
                                    <tr>
                                        <th>Sr#</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        @canany(['user_edit','user_enable-disable','user_activity-log'])
                                            <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $serial = 1; // Initialize serial number
                                    @endphp
                                    @if(!empty($users))
                                        @foreach($users as $user)
                                            @if($user->is_active == 0)
                                                <tr>
                                                    <td>{{ $serial++ }}</td>
                                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                                    <td>{{ $user->created_at->format('h:i A') }}</td>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <?php $roles = implode($user->roles->pluck('name','name')->all()); ?>
                                                    <td>{{ empty($roles) ? '---' : ucwords($roles) }}</td>
                                                    <td>
                                                        <h5><span class="badge badge-danger">Disabled</span></h5>
                                                    </td>
                                                    @canany(['user_edit','user_enable-disable','user_activity-log'])
                                                        <td>
                                                            <div class="list-icons">
                                                                <div class="dropdown">
                                                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                                        <i class="icon-menu9"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                        @can('user_edit')
                                                                            <a href="{{ route('users.edit',$user->id) }}" class="dropdown-item"> <i></i>Edit</a>
                                                                        @endcan
                                                                        @can('user_enable-disable')
                                                                            <a href="{{ route('userStatus',$user->id) }}" class="dropdown-item"><i></i>Enable</a>
                                                                        @endcan
                                                                        @can('user_activity-log')
                                                                            <a href="{{ route('activityLogs',$user->id) }}" class="dropdown-item"> <i></i>Activity Logs</a>
                                                                        @endcan
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endcanany
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->
    </div>
@endsection