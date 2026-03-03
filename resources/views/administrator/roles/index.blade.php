@extends('layouts.app')

@section('content')

    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Roles</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Roles</a>
                        <span class="breadcrumb-item active">All</span>
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
                    <h5 class="card-title">Role Management</h5>
                    <div>
                    @can('role_create')
                    <a href="{{ route('roles.office_create') }}" class="btn bg-info legitRipple"><i
                        class="icon-plus-circle2"></i> Office Role</a>
                    <a href="{{ route('roles.create') }}" class="btn bg-teal legitRipple"><i
                                class="icon-plus-circle2"></i> Role</a>
                    @endcan
                    @can('role_assign-role')
                            <a href="#" data-controls-modal="#assign_role"
                               data-backdrop="static"
                               data-keyboard="false" data-toggle="modal"
                               data-target="#assign_role"
                               class="btn bg-dark legitRipple"
                            >
                                <i class="fas fa-user-tag"></i> Assign Role
                            </a>
                    @endcan
                    </div>
                </div>

                @if ($message = Session::get('success'))
                    <div class="alert alert-success">
                        <p>{{ $message }}</p>
                    </div>
                @endif

                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Role Name</th>
                        @canany(['role_view','role_edit','role_delete'])
                        <th width="280px">Action</th>
                        @endcanany
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($roles as $key => $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            @canany(['role_view','role_edit','role_delete'])
                            <td>
                                @can('role_view')
                                <a class="btn btn-info" href="{{ route('roles.show',$role->id) }}">Show</a>
                                @endcan
                                @can('role_edit')
                                    <a class="btn btn-primary" href="{{ route('roles.edit',$role->id) }}">Edit</a>
                                @endcan
                                @can('role_delete')
                                    {!! Form::open(['method' => 'DELETE','route' => ['roles.destroy', $role->id],'style'=>'display:inline']) !!}
                                    {!! Form::submit('Delete', ['class' => 'btn btn-danger', 'onclick' => 'return confirmDelete()']) !!}
                                    {!! Form::close() !!}
                                @endcan
                            </td>
                            @endcanany
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {!! $roles->render() !!}
            </div>
            <!-- /default ordering -->

        @can('role_assign-role')
            <!-- Assign Role -->
            <div id="assign_role" class="modal fade" >
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Role to Multiple Users</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('assign-role-to-users') }}" method="post" enctype="multipart/form-data">
                                @csrf()
                                <div class="form-group">
                                    <label for="role"> Role Name</label>
                                    <select name="role" id="role" class="form-control form-control-select2" required>
                                        <option value="">Select Role</option>
                                        @foreach($all_roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group" style="border-bottom: 1px solid #DDDDDD;">
                                    <label for="users"> Select Users</label>
                                    <select data-placeholder="Select a User..." multiple="multiple" class="form-control select-search" name="users[]" id="users" data-fouc required>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Assign Role -->
        @endcan

        </div>
        <!-- /content area -->
 		<script>
            function confirmDelete() {
                return confirm("Are you sure you want to delete?");
            }
        </script>
@endsection