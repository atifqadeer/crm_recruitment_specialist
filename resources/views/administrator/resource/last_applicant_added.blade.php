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
                        <span class="font-weight-semibold">Applicant Added</span> -
                        @if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) Last 2 Months @endif
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">@if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) Last 2 Months @endif</span>
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
                    <h5 class="card-title">Active Applicants Added
                        @if($interval == 7)
                            Last 7 Days - Nurses
                        @elseif($interval == 21)
                            Last 21 Days
                        @elseif($interval == 60)
                            Last 2 Months
                        @endif
                    </h5>
                </div>

                <div class="card-body">
                </div>
                <table class="table datatable-sorting table-hover table-striped">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Job Title</th>
                        <th>Name</th>
                        <th>Postcode</th>
                        <th>Phone</th>
                        <th>Landline</th>
                        <th>Source</th>
                        <th>Notes</th>
                        {{--<th>Action</th>--}}
                    </tr>
                    </thead>
                    <tbody>
                    @if($interval == 7)
                    @foreach($appData7days as $applicant)
                        @if(($applicant['is_in_nurse_home'] == 'no') && ($applicant['is_callback_enable'] == 'no'))
                        <tr @if (($applicant['is_cv_in_quality'] == "no") && ($applicant['is_cv_in_quality_clear'] == "no")
                            && ($applicant['is_CV_reject'] == "no"))
                            style="background-color: none;"
                            @else
                            style="background-color: green;color:white;"
                                @endif>

                            @php($tableValue="clear")
                            @foreach($applicants_rejected as $app)

                                @if($app->id==$applicant['id'])
                                    @php($tableValue="reject")
                                @endif
                            @endforeach()

                            @foreach($applicants_crm_accepted as $app)

                                @if($app->id==$applicant['id'])
                                    @php($tableValue="not_reject")
                                    @php($disabled = "disabled")
                                @endif
                            @endforeach

                            <td class="{{ $tableValue }}">{{ $applicant['applicant_added_date'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_added_time'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_job_title'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_name'] }}</td>
                            <td class="{{ $tableValue }}">
                                <a href="{{ route('15kmrange',$applicant['id']) }}"
                                   class="btn btn-link legitRipple">
                                    {{ $applicant['applicant_postcode'] }}
                                </a>
                            </td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_phone'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_homePhone'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_source'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_notes'] }}</td>
                            {{--<td class="{{ $tableValue }}">
                                @if(
                            ($applicant['is_cv_in_quality'] == "no") && ($applicant['is_cv_in_quality_clear'] == "no")
                            && ($applicant['is_CV_reject'] == "no")
                            )
                                <a href="{{ route('sentToNurseHome',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                    <i class="icon-home"></i>&nbsp;No Nursing Home</a> |
                                <a href="{{ route('sentToCallBackList',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                    <i class="icon-phone-outgoing"></i>&nbsp;Callback</a>
                                @elseif($tableValue=="reject")
                                    <a href="{{ route('sentToNurseHome',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                        <i class="icon-home"></i>&nbsp;No Nursing Home</a> |
                                    <a href="{{ route('sentToCallBackList',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                        <i class="icon-phone-outgoing"></i>&nbsp;Callback</a>
                                @else
                                    <a href="#" class="disabled btn bg-slate-800 legitRipple">
                                        <i class="icon-blocked"></i>&nbsp;Not allowed</a>
                                @endif
                            </td>--}}
                        </tr>
                        @endif
                    @endforeach
                        @elseif($interval == 21)
                        @foreach($appData21days as $applicant)
                            @if(($applicant['is_in_nurse_home'] == 'no') && ($applicant['is_callback_enable'] == 'no'))
                        <tr @if(
                            ($applicant['is_cv_in_quality'] == "no") && ($applicant['is_cv_in_quality_clear'] == "no")
                            && ($applicant['is_CV_reject'] == "no")
                            ) style="background-color: none;"
                            @else
                            style="background-color: green;color:white;"
                                @endif>

                            @php($tableValue="clear")
                            @foreach($applicants_rejected as $app)

                                @if($app->id==$applicant->id)
                                    @php($tableValue="reject")
                                @endif
                            @endforeach()

                            @foreach($applicants_crm_accepted as $app)

                                @if($app->id==$applicant['id'])
                                    @php($tableValue="not_reject")
                                    @php($disabled = "disabled")
                                @endif
                            @endforeach

                            <td class="{{ $tableValue }}">{{ $applicant['applicant_added_date'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_added_time'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_job_title'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_name'] }}</td>
                            <td class="{{ $tableValue }}">
                                <a href="{{ route('15kmrange',$applicant['id']) }}"
                                    class="btn btn-link legitRipple">
                                    {{ $applicant['applicant_postcode'] }}
                                </a>
                            </td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_phone'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_homePhone'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_source'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_notes'] }}</td>>
                            {{--@if ($tableValue!="reject")
                                <td class="{{ $tableValue }}">
                                    <a href="{{ route('sentToNurseHome',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                        <i class="icon-home"></i>&nbsp;No Nursing Home</a> |
                                    <a href="{{ route('sentToCallBackList',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                        <i class="icon-phone-outgoing"></i>&nbsp;Callback</a>
                                </td>
                            @endif--}}
                        </tr>
                        @endif
                        @endforeach
                        @elseif($interval == 60)
                        @foreach($appData60days as $applicant)
                            @if(($applicant['is_in_nurse_home'] == 'no') && ($applicant['is_callback_enable'] == 'no'))
                        <tr @if(
                            ($applicant['is_cv_in_quality'] == "no") && ($applicant['is_cv_in_quality_clear'] == "no")
                            && ($applicant['is_CV_reject'] == "no")
                            ) style="background-color: none;"
                            @else
                            style="background-color: green;color:white;"
                                @endif>

                            @php($tableValue="clear")
                            @foreach($applicants_rejected as $app)

                                @if($app->id==$applicant->id)
                                    @php($tableValue="reject")
                                @endif
                            @endforeach()

                            @foreach($applicants_crm_accepted as $app)

                                @if($app->id==$applicant['id'])
                                    @php($tableValue="not_reject")
                                    @php($disabled = "disabled")
                                @endif
                            @endforeach

                            <td class="{{ $tableValue }}">{{ $applicant['applicant_added_date'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_added_time'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_job_title'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_name'] }}</td>
                            <td class="{{ $tableValue }}">
                                <a href="{{ route('15kmrange',$applicant['id']) }}"
                                   class="btn btn-link legitRipple">
                                    {{ $applicant['applicant_postcode'] }}
                                </a>
                            </td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_phone'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_homePhone'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_source'] }}</td>
                            <td class="{{ $tableValue }}">{{ $applicant['applicant_notes'] }}</td>
                            {{--@if ($tableValue!="reject")
                                <td class="{{ $tableValue }}">
                                    <a href="{{ route('sentToNurseHome',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                        <i class="icon-home"></i>&nbsp;No Nursing Home</a> |
                                    <a href="{{ route('sentToCallBackList',$applicant['id']) }}" class="btn bg-slate-800 legitRipple">
                                        <i class="icon-phone-outgoing"></i>&nbsp;Callback</a>
                                </td>
                            @endif--}}
                        </tr>
                        @endif
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()
