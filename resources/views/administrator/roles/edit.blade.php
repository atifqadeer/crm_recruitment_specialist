@extends('layouts.app')

@section('content')

    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="{{ route('users.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Role</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="{{ route('roles.index') }}" class="breadcrumb-item">Role</a>
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
                                        <h5 class="card-title">Edit a Role</h5>
                                        <a href="{{ route('roles.index') }}" class="btn bg-slate-800 legitRipple text-right">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">

                                    @if (count($errors) > 0)
                                        <div class="alert alert-danger">
                                            <strong>Whoops!</strong> There were some problems with your input.<br><br>
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                        {!! Form::model($role, ['method' => 'PATCH','route' => ['roles.update', $role->id]]) !!}

                                    <div class="form-group">
                                        {{ Form::label('name','Name', ['class' => 'font-weight-bold']) }}
                                        {!! Form::text('name', null, array('placeholder' => 'Name','class' => 'form-control')) !!}
                                    </div>

                                    <div class="form-group">
                                        <br/>

                                        <?php
                                            $module_permission = [];

                                        foreach ($permission as $v) {
                                            if (strpos($v->name, 'dashboard_', 0) !== false) {
                                                $module_permission['Dashboard'][$v->id]['id'] = $v->id;
                                                $module_permission['Dashboard'][$v->id]['name'] = str_replace('dashboard_', "", $v->name);
                                                $module_permission['Dashboard'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'role_', 0) !== false) {
                                                $module_permission['Roles'][$v->id]['id'] = $v->id;
                                                $module_permission['Roles'][$v->id]['name'] = str_replace('role_', "", $v->name);
                                                $module_permission['Roles'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'applicant_', 0) !== false) {
                                                $module_permission['Applicants'][$v->id]['id'] = $v->id;
                                                $module_permission['Applicants'][$v->id]['name'] = str_replace('applicant_', "", $v->name);
                                                $module_permission['Applicants'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'user_', 0) !== false) {
                                                $module_permission['Users'][$v->id]['id'] = $v->id;
                                                $module_permission['Users'][$v->id]['name'] = str_replace('user_', "", $v->name);
                                                $module_permission['Users'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'office_', 0) !== false) {
                                                $module_permission['Head Offices'][$v->id]['id'] = $v->id;
                                                $module_permission['Head Offices'][$v->id]['name'] = str_replace('office_', "", $v->name);
                                                $module_permission['Head Offices'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'unit_', 0) !== false) {
                                                $module_permission['Units'][$v->id]['id'] = $v->id;
                                                $module_permission['Units'][$v->id]['name'] = str_replace('unit_', "", $v->name);
                                                $module_permission['Units'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'sale_', 0) !== false) {
                                                $module_permission['Sales'][$v->id]['id'] = $v->id;
                                                $module_permission['Sales'][$v->id]['name'] = str_replace('sale_', "", $v->name);
                                                $module_permission['Sales'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'ip-address_', 0) !== false) {
                                                $module_permission['Ip Address'][$v->id]['id'] = $v->id;
                                                $module_permission['Ip Address'][$v->id]['name'] = str_replace('ip-address_', "", $v->name);
                                                $module_permission['Ip Address'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'postcode-finder_', 0) !== false) {
                                                $module_permission['Postcode Finder'][$v->id]['id'] = $v->id;
                                                $module_permission['Postcode Finder'][$v->id]['name'] = str_replace('postcode-finder_', "", $v->name);
                                                $module_permission['Postcode Finder'][$v->id]['original_name'] = $v->name;
                                            }elseif (strpos($v->name, 'Region_', 0) !== false) {
                                                $module_permission['Region'][$v->id]['id'] = $v->id;
                                                $module_permission['Region'][$v->id]['name'] = str_replace('Region_', "", $v->name);
                                                $module_permission['Region'][$v->id]['original_name'] = $v->name;
                                            }
											elseif (strpos($v->name, 'resource_', 0) !== false) {
                                                $sub_module = explode('_', $v->name);
                                                if (count($sub_module) == 3) {
                                                    $module_permission['Resource'][$sub_module[1]][] = [
                                                        'id' => $v->id,
                                                        'name' => str_replace('resource_'.$sub_module[1].'_', "", $v->name),
                                                        'original_name' => $v->name
                                                    ];
                                                } else {
                                                    $module_permission['Resource']['Sub-Links'][] = [
                                                        'id' => $v->id,
                                                        'name' => str_replace('resource_', "", $v->name),
                                                        'original_name' => $v->name
                                                    ];
                                                }
                                            } elseif (strpos($v->name, 'quality_', 0) !== false) {
                                                $sub_module = explode('_', $v->name);
                                                $module_permission['Quality'][$sub_module[1]][] = [
                                                    'id' => $v->id,
                                                    'name' => str_replace('quality_'.$sub_module[1].'_', "", $v->name),
                                                    'original_name' => $v->name
                                                ];
                                            } elseif (strpos($v->name, 'CRM_', 0) !== false) {
                                                $sub_module = explode('_', $v->name);
                                                $module_permission['CRM'][$sub_module[1]][] = [
                                                    'id' => $v->id,
                                                    'name' => str_replace('CRM_'.$sub_module[1].'_', "", $v->name),
                                                    'original_name' => $v->name
                                                ];
                                            }
                                            /*** common links
                                            elseif (strpos($v->name, 'common-links_', 0) !== false) {
                                                $sub_module = explode('_', $v->name);
                                                $module_permission['Common-Actions'][] = [
                                                    'id' => $v->id,
                                                    'name' => str_replace('common-links_', "", $v->name),
                                                    'original_name' => $v->name
                                                ];
                                            }
                                            */
                                        }
                                        ?>

                                        <div class="card-group-control card-group-control-right" id="accordion-control-right-edit">
                                        @foreach($module_permission as $key1 => $value1)

                                            @php($key1 = str_replace(' ','-',$key1))
                                            <div class="card border-top-dark-alpha border-top-1" style="margin-bottom: 0;">
                                                <div class="card-header">
                                                    <h6 class="card-title">
                                                        <a data-toggle="collapse" class="collapsed text-default" href="#accordion-control-right-group-{{ $key1 }}" aria-expanded="false"><h6 style="margin-bottom: 0;">{{ $key1 }} Permissions ({{ count($value1) }})</h6></a>
                                                    </h6>
                                                </div>

                                                <div id="accordion-control-right-group-{{ $key1 }}" class="collapse" data-parent="#accordion-control-right-edit">
                                                    <div class="card-body">

                                            @if($key1 === 'Resource' || $key1 === 'Quality' || $key1 === 'CRM')

                                                @foreach($value1 as $key2 => $value2)
                                                    <br>
                                                    <h6 style="margin-bottom: 0;"><span class="badge badge-mark border-danger mr-2"></span>{{ $key2 }} ({{ count($value2) }})</h6>
                                                    <br>

                                                    @foreach($value2 as $value3)
                                                        <div class="col-sm-3 list-inline-item">
                                                            <label>{{ Form::checkbox('permission[]', $value3['id'], in_array($value3['id'], $rolePermissions) ? true : false, array('class' => 'name')) }}
                                                                {{ $value3['name'] }}</label>
                                                        </div>
                                                    @endforeach
                                                    <br/>
                                                @endforeach
                                            @else

                                            @foreach($value1 as $val)
                                                <div class="col-sm-3 list-inline-item">
                                                    <label>{{ Form::checkbox('permission[]', $val['id'], in_array($val['id'], $rolePermissions) ? true : false, array('class' => 'name')) }}
                                                        {{ $val['name'] }}</label>
                                                </div>
                                            @endforeach

                                            @endif
                                            <br/>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        </div>

{{--                                        @foreach($permission as $value)--}}
{{--                                            <div class="col-md-4 d-inline">--}}
{{--                                            <label>{{ Form::checkbox('permission[]', $value->id, in_array($value->id, $rolePermissions) ? true : false, array('class' => 'name')) }}--}}
{{--                                                {{ $value->name }}</label>--}}
{{--                                            </div>--}}
{{--                                        @endforeach--}}

                                    </div>

                                    <div class="text-right">
                                        {{ Form::button('Update <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
                                    </div>
                                        {!! Form::close() !!}
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