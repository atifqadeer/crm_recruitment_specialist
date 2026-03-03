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
                        <span class="font-weight-semibold">Role</span> - {{ $role->name }}
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Role</a>
                        <span class="breadcrumb-item active">View</span>
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
                                        <h5 class="card-title">Role - {{ $role->name }}</h5>
                                        <a href="{{ route('roles.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">

                                    @if(!empty($rolePermissions))
                                        <?php
                                        $module['Dashboard'] = '';
                                        $module['Roles'] = '';
                                        $module['Applicants'] = '';
                                        $module['Users'] = '';
                                        $module['Head Offices'] = '';
                                        $module['Units'] = '';
                                        $module['Sales'] = '';
                                        $module['Ip Address'] = '';
                                        $module['Postcode Finder'] = '';
//                                        $module['Common Actions'] = '';
                                        $module['Resource'] = [
                                            'Potential-Callback' => '',
                                            'Sub-Links' => ''
                                        ];
                                        $module['Quality'] = [
                                            'CVs' => '',
                                            'CVs-Rejected' => '',
                                            'CVs-Cleared' => '',
                                            'Sales' => '',
                                            'Sales-Cleared' => '',
                                            'Sales-Rejected' => ''
                                        ];
                                        $module['CRM'] = [
                                            'Sent-CVs' => '',
                                            'Rejected-CV' => '',
                                            'Request' => '',
                                            'Rejected-By-Request' => '',
                                            'Confirmation' => '',
											'Rebook' => '',
                                            'Attended' => '',
											'Declined' => '',
                                            'Not-Attended' => '',
                                            'Start-Date' => '',
                                            'Start-Date-Hold' => '',
                                            'Invoice' => '',
                                            'Dispute' => '',
                                            'Paid' => ''
                                        ];

                                        foreach ($rolePermissions as $v) {
                                            if (strpos($v->name, 'dashboard_', 0) !== false) {
                                                $module['Dashboard'] .= ' | '.str_replace('dashboard_', "", $v->name);
                                            } elseif (strpos($v->name, 'role_', 0) !== false) {
                                                $module['Roles'] .= ' | '.str_replace('role_', "", $v->name);
                                            } elseif (strpos($v->name, 'applicant_', 0) !== false) {
                                                $module['Applicants'] .= ' | '.str_replace('applicant_', "", $v->name);
                                            } elseif (strpos($v->name, 'user_', 0) !== false) {
                                                $module['Users'] .= ' | '.str_replace('user_', "", $v->name);
                                            } elseif (strpos($v->name, 'office_', 0) !== false) {
                                                $module['Head Offices'] .= ' | '.str_replace('office_', "", $v->name);
                                            } elseif (strpos($v->name, 'unit_', 0) !== false) {
                                                $module['Units'] .= ' | '.str_replace('unit_', "", $v->name);
                                            } elseif (strpos($v->name, 'sale_', 0) !== false) {
                                                $module['Sales'] .= ' | '.str_replace('sale_', "", $v->name);
                                            } elseif (strpos($v->name, 'ip-address_', 0) !== false) {
                                                $module['Ip Address'] .= ' | '.str_replace('ip-address_', "", $v->name);
                                            } elseif (strpos($v->name, 'postcode-finder_', 0) !== false) {
                                                $module['Postcode Finder'] = '| '.str_replace('postcode-finder_', "", $v->name);
                                            } elseif (strpos($v->name, 'resource_', 0) !== false) {
                                                $permission = explode('_', $v->name);
                                                if (count($permission) == 3) {
                                                    //$module['Resource'][$permission[1]] .= ' | '.str_replace('resource_'.$permission[1].'_', "", $v->name);
                                                } else {
                                                    //$module['Resource']['Sub-Links'] .= ' | '.str_replace('resource_', "", $v->name);
                                                }
                                            }
                                            /*** common links
                                            elseif (strpos($v->name, 'common-links_', 0) !== false) {
                                                $module['Common Actions'] .= ' | '.str_replace('common-links_', "", $v->name);
                                            }
                                            */
                                            elseif (strpos($v->name, 'quality_', 0) !== false) {
                                                $permission = explode('_', $v->name);
                                                $module['Quality'][$permission[1]] .= ' | '.str_replace('quality_'.$permission[1].'_', "", $v->name);
                                            } elseif (strpos($v->name, 'CRM_', 0) !== false) {
                                                $permission = explode('_', $v->name);
                                                $module['CRM'][$permission[1]] .= ' | '.str_replace('CRM_'.$permission[1].'_', "", $v->name);
                                            }
                                        }
                                        ?>
                                        @foreach($module as $key => $value)
                                            @if($value === '') @continue @endif
                                            <span>
                                        <span class="badge badge-mark border-danger mr-2"></span>
                                        <strong>{{ $key }}</strong><br>
                                        </span>
                                            @if($key == 'Resource')
                                                @if($value['Potential-Callback'] !== '') <p><strong>Potential Callback </strong>{{ $value['Potential-Callback'] }}</p> @endif
                                                @if($value['Sub-Links'] !== '') <p><strong>Other-Links </strong>{{ $value['Sub-Links'] }}</p> @endif
                                            @elseif($key == 'Quality')
                                                @if($value['CVs'] !== '') <p><strong>CVs </strong>{{ $value['CVs'] }}</p> @endif
                                                @if($value['CVs-Rejected'] !== '') <p><strong>CVs-Rejected </strong>{{ $value['CVs-Rejected'] }}</p> @endif
                                                @if($value['CVs-Cleared'] !== '') <p><strong>CVs-Cleared </strong>{{ $value['CVs-Cleared'] }}</p> @endif
									            @if($value['Sales'] !== '') <p><strong>Sales </strong>{{ $value['Sales'] }}</p> @endif
                                                @if($value['Sales-Cleared'] !== '') <p><strong>Sales-Cleared </strong>{{ $value['Sales-Cleared'] }}</p> @endif
                                                @if($value['Sales-Rejected'] !== '') <p><strong>Sales-Rejected </strong>{{ $value['Sales-Rejected'] }}</p> @endif
                                            @elseif($key == 'CRM')
                                                @if($value['Sent-CVs'] !== '') <p><strong>Sent CVs </strong>{{ $value['Sent-CVs'] }}</p> @endif
                                                @if($value['Rejected-CV'] !== '') <p><strong>Rejected CV </strong>{{ $value['Rejected-CV'] }}</p> @endif
                                                @if($value['Request'] !== '') <p><strong>Request </strong>{{ $value['Request'] }}</p> @endif
                                                @if($value['Rejected-By-Request'] !== '') <p><strong>Rejected By Request </strong>{{ $value['Rejected-By-Request'] }}</p> @endif
                                                @if($value['Confirmation'] !== '') <p><strong>Confirmation </strong>{{ $value['Confirmation'] }}</p> @endif
												@if($value['Rebook'] !== '') <p><strong>Rebook </strong>{{ $value['Rebook'] }}</p> @endif
                                                @if($value['Attended'] !== '') <p><strong>Attended To Pre-Start Date </strong>{{ $value['Attended'] }}</p> @endif
												@if($value['Declined'] !== '') <p><strong>Declined </strong>{{ $value['Declined'] }}</p> @endif
                                                @if($value['Not-Attended'] !== '') <p><strong>Not Attended </strong>{{ $value['Not-Attended'] }}</p> @endif
                                                @if($value['Start-Date'] !== '') <p><strong>Start Date </strong>{{ $value['Start-Date'] }}</p> @endif
                                                @if($value['Start-Date-Hold'] !== '') <p><strong>Start Date Hold </strong>{{ $value['Start-Date-Hold'] }}</p> @endif
                                                @if($value['Invoice'] !== '') <p><strong>Invoice </strong>{{ $value['Invoice'] }}</p> @endif
                                                @if($value['Dispute'] !== '') <p><strong>Dispute </strong>{{ $value['Dispute'] }}</p> @endif
                                                @if($value['Paid'] !== '') <p><strong>Paid </strong>{{ $value['Paid'] }}</p> @endif
                                            @else
                                                <p>{{ $value }}</p>
                                            @endif
                                        @endforeach
                                    @endif
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
