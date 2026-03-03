@extends('layouts.app')

@section('content')

    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-light">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="{{ route('users.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Head Office Role</span> - Create
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="{{ route('roles.index') }}" class="breadcrumb-item">Head Office Role</a>
                        <span class="breadcrumb-item active">Add</span>
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
                                        <h5 class="card-title">Create Head Office Role</h5>
                                        <a href="{{ route('roles.index') }}" class="btn bg-slate-800 legitRipple">
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

                                        <?php
                                        $module_permission = [];
                                        $permission_ids = [];

                                        foreach ($permission as $v) {
                                           if (strpos($v->name, 'applicant_', 0) !== false) {
                                                $permission_ids['Applicants'][] = $module_permission['Applicants'][$v->id]['id'] = $v->id;
                                                $module_permission['Applicants'][$v->id]['name'] = str_replace('applicant_', "", $v->name);
                                                $module_permission['Applicants'][$v->id]['original_name'] = $v->name;
                                            } 
                                            elseif (strpos($v->name, 'resource_', 0) !== false) {
                                                if(str_replace('resource_', "", $v->name) =='Nurses-list')
                                                {
                                                $permission_ids['Resource'][] = $module_permission['Resource'][$v->id]['id'] = $v->id;
                                                $module_permission['Resource'][$v->id]['name'] = str_replace('resource_', "", $v->name);
                                                $module_permission['Resource'][$v->id]['original_name'] = $v->name;
                                                }
                                                elseif(str_replace('resource_', "", $v->name) =='Non-Nurses-list')
                                                {
                                                    $permission_ids['Resource'][] = $module_permission['Resource'][$v->id]['id'] = $v->id;
                                                $module_permission['Resource'][$v->id]['name'] = str_replace('resource_', "", $v->name);
                                                $module_permission['Resource'][$v->id]['original_name'] = $v->name;
                                                }
                                                // $sub_module = explode('_', $v->name);
                                                // $permission_ids['Resource'][] = $v->id;
                                                // if (count($sub_module) == 3) {
                                                //     $module_permission['Resource'][$sub_module[1]][] = [
                                                //         'id' => $v->id,
                                                //         'name' => str_replace('resource_'.$sub_module[1].'_', "", $v->name),
                                                //         'original_name' => $v->name
                                                //     ];
                                                // } else {
                                                //     $module_permission['Resource']['Sub-Links'][] = [
                                                //         'id' => $v->id,
                                                //         'name' => str_replace('resource_', "", $v->name),
                                                //         'original_name' => $v->name
                                                //     ];
                                                // }
                                            } elseif (strpos($v->name, 'quality_', 0) !== false) {
                                                $sub_module = explode('_', $v->name);
                                                $permission_ids['Quality'][] = $v->id;
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
                                        // echo '<pre>';print_r($module_permission); echo '</pre>';exit();
                                        ?>

                                    {!! Form::open(array('route' => 'roles.office_store','method'=>'POST')) !!}
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-12 col-md-12" style="margin-bottom: 15px;">
                                            <div class="form-group">
                                                <strong>Name:</strong>
                                                {!! Form::text('name', null, array('placeholder' => 'Name','class' => 'form-control', 'required')) !!}
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="form-group">
                                                <div class="card-group-control card-group-control-right" id="accordion-control-right-create">
                                                @foreach($module_permission as $key1 => $value1)

                                                    @php($key1 = str_replace(' ','-',$key1))
                                                    <div class="card border-top-dark-alpha border-top-1" style="margin-bottom: 0;">
                                                        <div class="card-header">
                                                            <h6 class="card-title">
                                                                <a href="#" style="font-size: 12px;"><input type="checkbox" class="form-check-input-styled check-all" data-fouc data-permissions="{{ implode('-' , $permission_ids[$key1]) }}" data-permission-name="{{ $key1 }}"></a>
                                                                <a data-toggle="collapse" class="collapsed text-default" href="#accordion-control-right-group-{{ $key1 }}" aria-expanded="false"><h6 style="margin-bottom: 0;">{{ $key1 }} Permissions ({{ count($value1) }})</h6></a>
                                                            </h6>
                                                        </div>

                                                        <div id="accordion-control-right-group-{{ $key1 }}" class="collapse" data-parent="#accordion-control-right-create">
                                                            <div class="card-body">

                                                            @if($key1 === 'Quality')

                                                                @foreach($value1 as $key2 => $value2)
                                                                    <br>
                                                                    <h6 style="margin-bottom: 0;"><span class="badge badge-mark border-danger mr-2"></span>{{ $key2 }} ({{ count($value2) }})</h6>
                                                                    <br>

                                                                    @foreach($value2 as $value3)
                                                                        <div class="col-md-3 list-inline-item">
                                                                            <label style="font-size: 14px;">{{ Form::checkbox('permission[]', $value3['id'], false, array('class' => 'name', 'id' => $key1.'-'.$value3['id'])) }}
                                                                                {{ $value3['name'] }}</label>
                                                                        </div>
                                                                    @endforeach
                                                                    <br/>
                                                                @endforeach

                                                            @elseif($key1 === 'Resource')
                                                            @foreach($value1 as $val)

                                                            <div class="col-md-3 list-inline-item">
                                                                <label style="font-size: 14px;">{{ Form::checkbox('permission[]', $val['id'], true, array('disabled'), array('class' => 'name', 'id' => $key1.'-'.$val['id'])) }}
                                                                            {{ $val['name'] }}</label>
                                                            </div>
                                                        @endforeach
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
                                                           {{-- <div class="form-group" style="border-bottom: 1px solid #DDDDDD;">
                                                                <label for="users"> Select Head Offices</label>
                                                                <select data-placeholder="Select a User..." multiple="multiple" class="form-control select-search" name="users[]" id="users" data-fouc required>
                                                                    @foreach($offices as $office)
                                                                        <option value="{{ $office->id }}">{{ $office->office_name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div> --}}

                                                            @else

                                                                @foreach($value1 as $val)

                                                                    <div class="col-md-3 list-inline-item">
                                                                        <label style="font-size: 14px;">{{ Form::checkbox('permission[]', $val['id'], false, array('class' => 'name', 'id' => $key1.'-'.$val['id'])) }}
                                                                                    {{ $val['name'] }}</label>
                                                                    </div>
                                                                @endforeach

                                                            @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            </div>
                                            <div class="text-right">
                                                {{ Form::button('Save <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
                                            </div>
                                        </div>
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

@section('script')
<script>
    $('.form-check-input-styled').uniform();
    $(document).on('click', '.check-all', function (event) {
        $element = $(this);
        var permission_name = $element.data('permission-name');
        var permissions = $element.data('permissions');
        var single_perm = false;
        if (typeof permissions === 'number') {
            if($element.prop("checked") === true) {
                single_perm = true;
            }
            $( "#"+permission_name+"-"+permissions ).prop( "checked", single_perm );
        } else {
            permissions = permissions.split('-');
            if($element.prop("checked") === true) {
                permissions.forEach(function (item, i) {
                    $( "#"+permission_name+"-"+item ).prop( "checked", true );
                });
            } else {
                permissions.forEach(function (item, i) {
                    $( "#"+permission_name+"-"+item ).prop( "checked", false );
                });
            }
        }
    });

    // $('#search').on('keyup',function(){
    // $value=$(this).val();
    // $('.dropdown-wrapper').text('here');
    // $.ajax({
    // type : 'get',
    // url: '{{URL::to('/search')}}',
    // data:{'search':$value},
    // success:function(data){
    // $('#office_res').html(data);
    // }
    // });
    // });

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
