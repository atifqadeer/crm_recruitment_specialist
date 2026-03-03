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
                                        <h5 class="card-title">Edit a Office Role</h5>
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

                                        {!! Form::model($role, ['method' => 'PATCH','route' => ['roles.update_office', $role->id]]) !!}

                                    <div class="form-group">
                                        {{ Form::label('name','Name', ['class' => 'font-weight-bold']) }}
                                        {!! Form::text('name', null, array('placeholder' => 'Name','class' => 'form-control')) !!}
                                    </div>

                                    <div class="form-group">
                                        <br/>

                                        <?php
                                            $module_permission = [];

                                        foreach ($permission as $v) {
                                           if (strpos($v->name, 'applicant_', 0) !== false) {
                                                $module_permission['Applicants'][$v->id]['id'] = $v->id;
                                                $module_permission['Applicants'][$v->id]['name'] = str_replace('applicant_', "", $v->name);
                                                $module_permission['Applicants'][$v->id]['original_name'] = $v->name;
                                            } elseif (strpos($v->name, 'Hoffice_', 0) !== false) {
                                                // $sub_module = explode('_', $v->name);
                                                // if(str_replace('resource_', "", $v->name) =='Nurses-list' || str_replace('resource_', "", $v->name) =='Non-Nurses-list') {
                                                    
                                                //     $module_permission['Resource'][$v->id]['id'] = $v->id;
                                                // $module_permission['Resource'][$v->id]['name'] = str_replace('resource_', "", $v->name);
                                                // $module_permission['Resource'][$v->id]['original_name'] = $v->name;
                                                //     // $module_permission['Resource'][$sub_module[1]][] = [
                                                //     //     'id' => $v->id,
                                                //     //     'name' => str_replace('resource_'.$sub_module[1].'_', "", $v->name),
                                                //     //     'original_name' => $v->name
                                                //     // ];
                                                // } 
                                                

                                                    $module_permission['Resource'][$v->id]['id'] = $v->id;
                                                $module_permission['Resource'][$v->id]['name'] = str_replace('Hoffice_', "", $v->name);
                                                $module_permission['Resource'][$v->id]['original_name'] = $v->name;
                                                    // $module_permission['Resource'][$sub_module[1]][] = [
                                                    //     'id' => $v->id,
                                                    //     'name' => str_replace('resource_'.$sub_module[1].'_', "", $v->name),
                                                    //     'original_name' => $v->name
                                                    // ];
                                                
                                                // else {
                                                //     $module_permission['Resource']['Sub-Links'][] = [
                                                //         'id' => $v->id,
                                                //         'name' => str_replace('resource_', "", $v->name),
                                                //         'original_name' => $v->name
                                                //     ];
                                                // }
                                            } elseif (strpos($v->name, 'quality_', 0) !== false) {
                                                $sub_module = explode('_', $v->name);
                                                $module_permission['Quality'][$sub_module[1]][] = [
                                                    'id' => $v->id,
                                                    'name' => str_replace('quality_'.$sub_module[1].'_', "", $v->name),
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
                                                        <a data-toggle="collapse" class="collapsed text-default" href="#accordion-control-right-group-{{ $key1 }}" aria-expanded="false"><h6 style="margin-bottom: 0;"><?php if($key1 =='Resource'){  echo $key1. ' Permissions '.(count($Hoffice_data)+2);}else{  echo  $key1.' Permissions('. count($value1) .')';}?></h6></a>
                                                    </h6>
                                                </div>

                                                <div id="accordion-control-right-group-{{ $key1 }}" class="collapse" data-parent="#accordion-control-right-edit">
                                                    <div class="card-body">
                                            @if( $key1 === 'Applicants')
                                                        @foreach($value1 as $val)
                                                        <div class="col-sm-3 list-inline-item">
                                                            <label>{{ Form::checkbox('permission[]', $val['id'], in_array($val['id'], $rolePermissions) ? true : false, array('class' => 'name')) }}
                                                                {{ $val['name'] }}</label>
                                                        </div>
                                                    @endforeach
                                            
                                             @elseif( $key1 === 'Resource')
                                                <div class="col-md-3 list-inline-item">
                                                    <label style="font-size: 14px;">{{ Form::checkbox('permission[]', 44, true, array('disabled'), array('class' => 'name')) }}
                                                                Nurses-list</label>
                                                </div>
                                                <div class="col-md-3 list-inline-item">
                                                
                                                                <label style="font-size: 14px;">{{ Form::checkbox('permission[]', 45, true, array('disabled'), array('class' => 'name')) }}
                                                                    Non-Nurses-list</label>
                                                </div>
                                                <br>
                                                <div class="container" style="margin-top: 30px; margin-left:0px; margin-bottom:30px;">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label>Search Head Office</label>
                                                                <input type="text" name="country" id="country" placeholder="Enter country name" class="form-control">
                                                            </div>
                                                            <div id="country_list"></div>                    
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="Hoffice-list-items">
                                                </div>
                                                @foreach($Hoffice_data as $key => $value)
                                               <?php
                                               $Hoffice_name = array();
                                                $Hoffice_name1 = str_replace('Hoffice_', "", $value);
                                                    $Hoffice_name1 = explode("-", $Hoffice_name1);
                                                    // echo $Hoffice_name1[1];exit();
                                               ?>
                                                    <div class="col-sm-3 list-inline-item">
                                                        <label>{{ Form::checkbox('head_office[]',$value, array('class' => 'name')) }}
                                                            {{ $Hoffice_name1[0] }}</label>
                                                    </div>
                                                @endforeach
                                            @elseif( $key1 === 'Quality')

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
                                            

                                            {{-- @foreach($value1 as $val)
                                                <div class="col-sm-3 list-inline-item">
                                                    <label>{{ Form::checkbox('permission[]', $val['id'], in_array($val['id'], $rolePermissions) ? true : false, array('class' => 'name')) }}
                                                        {{ $val['name'] }}</label>
                                                </div>
                                            @endforeach --}}

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
        <script>
            // jQuery wait till the page is fullt loaded
            $(document).ready(function () {
                        // keyup function looks at the keys typed on the search box
                        $('#country').on('keyup',function() {
                            $("#country_list").show();
                            // the text typed in the input field is assigned to a variable 
                            var query = $(this).val();
                            // call to an ajax function
                            $.ajax({
                                // assign a controller function to perform search action - route name is search
                                url:'{{URL::to('/search')}}',
                                // since we are getting data methos is assigned as GET
                                type:"GET",
                                // data are sent the server
                                data:{'country':query},
                                // if search is succcessfully done, this callback function is called
                                success:function (data) {
                                    // print the search results in the div called country_list(id)
                                    $('#country_list').html(data);
                            $('.list-group-item').css('cursor', 'pointer');
        
                                    // $('#country_list').item().css('cursor', 'hand');
                                }
                            })
                            // end of ajax call
                        });
        
                        // initiate a click function on each search result
                        $(document).on('click', 'li', function(e){
                            e.stopPropagation();
                            $("#country_list").show();
                            // declare the value in the input field to a variable
                            var value = $(this).text();
                            $(this).prop("disabled", true);
                            $(this).css("background-color","#26a69a");
                            var id = $(this).attr('id');
                            var office_value = 'Hoffice_'+value+'-'+id;
                            var html = '<div class="col-md-3 list-inline-item"><label style="font-size: 14px;"><input type="checkbox" checked class="name" id="'+id+'" name="head_office[]" value="'+office_value+'">'+
                                ''+value+'</label></div>';
                            
                                var elemLength = $('.Hoffice-list-items').find("input[type='checkbox'][id = " + id + "]").length;
                                if(elemLength==0)
                                {
                                    $('.Hoffice-list-items').append(html).checked;
                                   
                                }
                               
                            // console.log(html);
                            // assign the value to the search box
                            // $('#country').val(value);
                            // after click is done, search results segment is made empty
                        });
                    });
                    $(document).click(function(){
          $("#country_list").hide();
        });
        </script>
@endsection