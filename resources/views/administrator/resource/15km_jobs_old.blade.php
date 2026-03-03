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
                        <span class="font-weight-semibold">Applicant's Jobs Within</span> - 15KM
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Direct</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <div class="card-header header-elements-inline">
                <h5 class="card-title">Applicant Details</h5>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="card border-left-3 border-left-slate rounded-left-0">
                        <div class="card-body">
                            <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                <div>
                                    Title:<span
                                            class="font-weight-semibold">{{ $applicant->applicant_job_title }}</span>
                                    <ul class="list list-unstyled mb-0">
                                        <li>Name: <span
                                                    class="font-weight-semibold">{{ $applicant->applicant_name }}</span>
                                        </li>
                                        <li>Postcode: <span
                                                    class="font-weight-semibold">{{ $applicant->applicant_postcode }}</span>
                                        </li>
                                        <li>Category: <span
                                                    class="font-weight-semibold">{{ $applicant->job_category }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
                                    Phone#:<span class="font-weight-semibold">{{ $applicant->applicant_phone }}</span>
                                    <ul class="list list-unstyled mb-0">
                                        <li>Landline: <span
                                                    class="font-weight-semibold">{{ $applicant->applicant_homePhone }}</span>
                                        </li>
                                        {{--                                        <li>Experience: <span class="font-weight-semibold">{{ $job->experience }}</span></li>--}}
                                        <li class="dropdown">
                                            Status: &nbsp;
                                            <a href="#" class="badge bg-teal align-top">{{ $applicant->status }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center">
										<span>
											<span class="font-weight-semibold"></span>
										</span>

                            <ul class="list-inline list-inline-condensed mb-0 mt-2 mt-sm-0">
                                <li class="list-inline-item">
                                    Created On:<span class="font-weight-semibold">{{ $applicant->created_at }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Default ordering -->
            <div class="card-header header-elements-inline">
                <h5 class="card-title">Active Applicant's Jobs Within 15KM</h5>
            </div>
            <div class="card">

                <div class="card-body">
                    {{--With DataTables you can alter the ordering characteristics of the table at initialisation time. Using the <code>order</code> initialisation parameter, you can set the table to display the data in exactly the order that you want. The <code>order</code> parameter is an array of arrays where the first value of the inner array is the column to order on, and the second is <code>'asc'</code> or <code>'desc'</code> as required. The table below is ordered (descending) by the <code>DOB</code> column.--}}
                </div>

                <table class="table datatable-sorting table-hover table-striped table-responsive">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Office</th>
                        <th>Unit</th>
                        <th>Job Title</th>
                        <th>Job Type</th>
                        <th>Job Postcode</th>
                        <th>Job Timing</th>
                        <th>Salary</th>
                        <th>Experience</th>
                        <th>Qualification</th>
                        <th>Interest</th>
                        <th>Quality</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php($disabled = "")
                    @if(!empty($jobs))
                        @foreach($jobs as $job)
                            <tr @if(
                            ($applicant->is_cv_in_quality == "no") && ($applicant->is_cv_in_quality_clear == "no")
                            && ($applicant->is_CV_reject == "no")
                            ) style="background-color: none;"
                                @else
                                style="background-color: green;color:white;"
                                    @php($disabled = "disabled")
                                    @endif>
                                @php($tableValue="clear")
                                @foreach($applicants_rejected as $app)

                                    @if($app->id==$applicant['id'])
                                       {{-- @if($app->sale_id == $job["id"])--}}
                                        @if($app->is_CV_reject == "yes" && $app->is_cv_in_quality == "yes" && $app->sale_id != $job["id"])
                                        @else
                                            @php($tableValue="reject")
                                            @php($disabled = "")
                                        @endif
                                        {{--@endif--}}
                                    @endif
                                @endforeach
                                @php($x=0)
                                @foreach($applicants_rejected_job as $app)

                                    @if($app->id==$applicant['id'])
                                            @if(!empty($sales_rejected_job[$x]))
                                        @foreach($sales_rejected_job[$x] as $sale)
                                            @if($sale==$job["id"])
                                                @php($tableValue="reject_job")
                                                @php($disabled = "disabled")
                                            @endif
                                        @endforeach
                                                @endif
                                    @endif
                                    @php($x++)
                                @endforeach
                                @if(!empty($applicants_crm_accepted))
                                @foreach($applicants_crm_accepted as $app)

                                    @if($app->id==$applicant['id'])
                                        @if($app->sale_id==$job["id"])
                                            @php($tableValue="not_reject")
                                            @php($disabled = "disabled")
                                        @endif
                                    @endif
                                @endforeach
                                @endif
                                <td class="{{ $tableValue }}">{{ $job["sale_added_date"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["sale_added_time"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["office_name"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["unit_name"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["job_title"] }} </td>
                                <td class="{{ $tableValue }}">{{ $job["job_type"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["postcode"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["timing"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["salary"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["experience"] }}</td>
                                <td class="{{ $tableValue }}">{{ $job["qualification"] }}</td>
                                <td class="{{ $tableValue }}">
                                    @if(($applicant['is_cv_in_quality'] == "no") && ($applicant['is_cv_in_quality_clear'] == "no")
                                    && ($applicant['is_CV_reject'] == "no"))

                                        <a href="#" class="{{ $disabled }} btn bg-teal legitRipple"
                                           data-controls-modal="#modal_form_horizontal{{ $job['id'] }}"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#modal_form_horizontal{{ $job['id'] }}">
                                            <i class="icon-thumbs-down3"></i> add to Not Interested
                                        </a>
                                    @elseif ($tableValue=="reject")
                                        <a href="#" class="{{ $disabled }} btn bg-teal legitRipple"
                                           data-controls-modal="#modal_form_horizontal{{ $job['id'] }}"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#modal_form_horizontal{{ $job['id'] }}">
                                            <i class="icon-thumbs-down3"></i> add to Not Interested
                                        </a>
                                    @else
                                        <a href="#" class="disabled btn bg-teal legitRipple">
                                            <i class="icon-blocked"></i> Not Allowed
                                        </a>
                                    @endif
                                </td>
                            
                                <td class="{{ $tableValue }}">
                                    @if(($applicant->is_cv_in_quality == "no") && ($applicant->is_cv_in_quality_clear == "no")
                                    && ($applicant->is_CV_reject == "no"))

                                        <a href="#" class="{{ $disabled }} btn bg-slate-800 legitRipple"
                                           data-controls-modal="#sent_cv{{ $job['id'] }}" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#sent_cv{{ $job['id'] }}"
                                           style="position:relative;right:30%;">
                                            <i class="icon-paperplane"> Send CV</i>
                                        </a>

                                    @elseif ($tableValue=="reject")

                                        <a href="#" class="{{ $disabled }} btn bg-slate-800 legitRipple"
                                           data-controls-modal="#sent_cv{{ $job['id'] }}" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#sent_cv{{ $job['id'] }}"
                                           style="position:relative;right:30%;">
                                            <i class="icon-paperplane"> Send CV</i>
                                        </a>

                                    @else

                                        <a href="#" class="disabled btn bg-slate-800 legitRipple"
                                           style="position:relative;right:31%;">
                                            <i class="icon-blocked"></i> not allowed</a>
                                    @endif
                                </td>

                                <td class="{{ $tableValue }}">
                                    <a href="#"
                                       class="{{ $disabled }} btn bg-slate-800 legitRipple"
                                       data-controls-modal="#no_nurse_home{{ $applicant['id'] }}" data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#no_nurse_home{{ $applicant['id'] }}"
                                       style="position:relative;right:30%;">
                                        <i class="icon-home"></i>&nbsp;No Nursing Home</a> |
                                    <a href="{{ route('sentToCallBackList',$applicant['id']) }}"
                                       class="{{ $disabled }} btn bg-slate-800 legitRipple"
                                       data-controls-modal="#call_back{{ $applicant['id'] }}" data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#call_back{{ $applicant['id'] }}"
                                       style="position:relative;right:30%;">
                                        <i class="icon-phone-outgoing"></i>&nbsp;Callback</a>
                                </td>

                            </tr>
                            <!-- No Nursing Home Modal -->
                            <div id="no_nurse_home{{ $applicant['id'] }}" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add No Nursing Home Below:</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>

                                        <form action="{{ route('sentToNurseHome') }}" method="GET"
                                              class="form-horizontal">
                                            @csrf()
                                            <div class="modal-body">
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3">Details</label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="applicant_hidden_id"
                                                               value="{{ $applicant['id'] }}">
                                                        <input type="hidden" name="sale_hidden_id" value="{{ $job['id'] }}">
                                                        <textarea name="details" class="form-control" cols="30" rows="4"
                                                                  placeholder="TYPE HERE.." required></textarea>
                                                    </div>
                                                </div>
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
                            <!-- /No Nursing Home Modal -->
                            <!-- CallBack Modal -->
                            <div id="call_back{{ $applicant['id'] }}" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add Callback Notes Below:</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>

                                        <form action="{{ route('sentToCallBackList') }}" method="GET"
                                              class="form-horizontal">
                                            @csrf()
                                            <div class="modal-body">
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3">Details</label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="applicant_hidden_id"
                                                               value="{{ $applicant['id'] }}">
                                                        <input type="hidden" name="sale_hidden_id" value="{{ $job['id'] }}">
                                                        <textarea name="details" class="form-control" cols="30" rows="4"
                                                                  placeholder="TYPE HERE.." required></textarea>
                                                    </div>
                                                </div>
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
                            <!-- /CallBack Modal -->
                            <!-- Send CV Modal -->
                            <div id="sent_cv{{ $job['id'] }}" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add CV Notes Below:</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>

                                        <form action="{{ route('sendCV',$applicant->id) }}" method="GET"
                                              class="form-horizontal">
                                            @csrf()
                                            <div class="modal-body">
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3">Details</label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="applicant_hidden_id"
                                                               value="{{ $applicant->id }}">
                                                        <input type="hidden" name="sale_hidden_id"
                                                               value="{{ $job['id'] }}">
                                                        <textarea name="details" class="form-control" cols="30" rows="4"
                                                                  placeholder="TYPE HERE.." required></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-link legitRipple"
                                                        data-dismiss="modal">
                                                    Close
                                                </button>
                                                <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- /Sent CV Modal -->

                            <!-- Add To Non Interest List Modal -->
                            <div id="modal_form_horizontal{{ $job['id'] }}" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Enter Interest Reason Below:</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>

                                        <form action="{{ route('markApplicant') }}" method="POST"
                                              class="form-horizontal">
                                            @csrf()
                                            <div class="modal-body">
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3">Reason</label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="applicant_hidden_id"
                                                               value="{{ $applicant->id }}">
                                                        <input type="hidden" name="job_hidden_id"
                                                               value="{{ $job['id'] }}">
                                                        <textarea name="reason" class="form-control" cols="30" rows="4"
                                                                  placeholder="TYPE HERE.." required></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-link legitRipple"
                                                        data-dismiss="modal">
                                                    Close
                                                </button>
                                                <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- /Add To Non Interest List Modal -->
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection()
