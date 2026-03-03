<div class="content-wrapper">
    <div class="content">
        <table class="table datatable-sorting">
            <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Name</th>
                <th>Job Title</th>
                <th>Postcode</th>
                <th>Phone#</th>
                <th>Landline#</th>
                <th>Job Details</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @if(!empty($applicant_with_cvs))
            @foreach($applicant_with_cvs as $applicant)
                <tr>
                    <td >{{ $applicant->quality_added_date }}</td>
                    <td >{{ $applicant->quality_added_time }}</td>
                    <td>{{ $applicant->applicant_name }}</td>
                    <td>{{ strtoupper($applicant->applicant_job_title) }}</td>
                    <td>
                        <a href="{{ route('15kmrange',$applicant->id) }}"
                           class="btn btn-link legitRipple">
                        {{ $applicant->applicant_postcode }}
                        </a>
                    </td>
                    <td>{{ $applicant->applicant_phone }}</td>
                    <td>{{ $applicant->applicant_homePhone }}</td>
                    <td><a href="#" data-controls-modal="#job_details{{ $applicant->id }}-{{$applicant->sale_id}}"
                           data-backdrop="static"
                           data-keyboard="false" data-toggle="modal"
                           data-target="#job_details{{ $applicant->id }}-{{$applicant->sale_id}}">View Details</a></td>

                    <td>@if(empty($crm_cv_sent_save_note->details))
                            {{
                            $applicant->details
                            }}
                            @else
                            {{
                            $crm_cv_sent_save_note->details
                             }}
                            @endif
                    </td>
                    <td>
                        <div class="list-icons">
                            <div class="dropdown">
                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="#" class="dropdown-item"
                                       data-controls-modal="#clear_cv{{ $applicant->id }}" data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#clear_cv{{ $applicant->id }}">
                                        <i class="icon-file-confirm"></i>
                                        Reject/Request
                                    </a>
                                    <a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details{{ $applicant->id }}" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details{{ $applicant->id }}">
                                        <i class="icon-file-confirm"></i>
                                        Manager Details
                                    </a>
                                    <a href="{{ route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]) }}" class="dropdown-item"
                                       ><i class="icon-file-confirm"></i>
                                        View All Notes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- Move CV Modal -->
                <div id="clear_cv{{ $applicant->id }}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">CRM Notes</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>

                            <form action="{{ route('processCv') }}"
                                  method="POST" class="form-horizontal">
                                @csrf()
                                <div class="modal-body">
                                    <div class="form-group row">
                                        <label class="col-form-label col-sm-3">Details</label>
                                        <div class="col-sm-9">
                                            <input type="hidden" name="applicant_hidden_id"
                                                   value="{{ $applicant->id }}">
                                            <input type="hidden" name="job_hidden_id"
                                                   value="{{ $applicant->sale_id }}">
                                            <textarea name="details" class="form-control" cols="30" rows="4"
                                                      placeholder="TYPE HERE.." required></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-form-label col-sm-3">Choose type:</label>
                                        <div class="col-sm-9">
{{--                                            <div class="custom-control custom-radio">--}}
{{--                                                <input type="radio" class="custom-control-input crm_select_reason" name="reject_reason"--}}
{{--                                                       id="custom_radio_stacked_unchecked" value="position filled">--}}
{{--                                                <label class="custom-control-label"--}}
{{--                                                       for="custom_radio_stacked_unchecked">Position Filled</label>--}}
{{--                                            </div>--}}

{{--                                            <div class="custom-control custom-radio">--}}
{{--                                                <input type="radio" class="custom-control-input crm_select_reason" name="reject_reason"--}}
{{--                                                       id="custom_radio_stacked_checked" value="sent by another agency">--}}
{{--                                                <label class="custom-control-label" for="custom_radio_stacked_checked">Sent--}}
{{--                                                    By Another Agency</label>--}}
{{--                                            </div>--}}
                                            <select name="reject_reason" class="form-control crm_select_reason">
                                                <option >Select Reason</option>
                                                <option value="position_filled">Position Filled</option>
                                                <option value="agency">Sent By Another Agency</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="cv_sent_reject" value="cv_sent_reject"
                                            class="btn bg-orange-800 legitRipple reject_btn" id="reject_btn" style="display: none">
                                        Reject
                                    </button>
                                    <button type="submit" name="cv_sent_request" value="cv_sent_request"
                                            class="btn bg-dark legitRipple">Request
                                    </button>
                                    <button type="submit" name="cv_sent_save" value="cv_sent_save"
                                            class="btn bg-teal legitRipple">Save
                                    </button>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /Move CV Modal -->
                <!-- Manager Details Modal -->
                <div id="manager_details{{ $applicant->id }}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Manager Details</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <ul class="list-group ">
                                    <li class="list-group-item active"><b>Name:</b>{{ $applicant->contact_name }}</li>
                                    <li class="list-group-item"><b>Email:</b>{{ $applicant->contact_email }}</li>
                                    <li class="list-group-item"><b>Phone:</b>{{ $applicant->contact_phone_number }}</li>
                                </ul>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Manager Details Modal -->
                <!-- Job Details Modal -->
                <div id="job_details{{ $applicant->id }}-{{$applicant->sale_id}}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $applicant->applicant_name }}'s Job Details</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="media flex-column flex-md-row mb-4">
                                    <div class="media-body">
                                        <h5 class="media-title font-weight-semibold">
                                            {{ $applicant->office_name }} / {{ $applicant->unit_name }}
                                        </h5>
                                        <ul class="list-inline list-inline-dotted text-muted mb-0">
                                            <li class="list-inline-item">
                                                {{ $applicant->job_category }}, {{ $applicant->job_title }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Job Title:</h6>
                                        <p>{{ $applicant->job_title }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Postcode:</h6>
                                        <p class="mb-3">{{ $applicant->postcode }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Job Type:</h6>
                                        <p class="mb-3">{{ $applicant->job_type }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Timings:</h6>
                                        <p class="mb-3">{{ $applicant->timing }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Salary:</h6>
                                        <p class="mb-3">{{ $applicant->salary }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Experience:</h6>
                                        <p class="mb-3">{{ $applicant->experience }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Qualification:</h6>
                                        <p class="mb-3">{{ $applicant->qualification }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Benefits:</h6>
                                        <p class="mb-3">{{ $applicant->benefits }}</p>
                                    </div>
                                    <div class="col-3"></div>
                                    <div class="col-3">
                                        <h6 class="font-weight-semibold">Posted Date:</h6>
                                        <p class="mb-3">{{ $applicant->posted_date }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Job Details Modal -->
                <!-- Clear CV Modal -->
{{--                <div id="clear_cv{{ $applicant->id }}" class="modal fade" tabindex="-1">--}}
{{--                    <div class="modal-dialog modal-lg">--}}
{{--                        <div class="modal-content">--}}
{{--                            <div class="modal-header">--}}
{{--                                <h5 class="modal-title">Clear CV Notes</h5>--}}
{{--                                <button type="button" class="close" data-dismiss="modal">&times;</button>--}}
{{--                            </div>--}}

{{--                            <form action="{{ route('updateToInterviewConfirmed',['id'=>$applicant->id , 'viewString'=>'applicantWithSentCv']) }}"--}}
{{--                                  method="GET" class="form-horizontal">--}}
{{--                                @csrf()--}}
{{--                                <div class="modal-body">--}}
{{--                                    <div class="form-group row">--}}
{{--                                        <label class="col-form-label col-sm-3">Details</label>--}}
{{--                                        <div class="col-sm-9">--}}
{{--                                            <input type="hidden" name="applicant_hidden_id"--}}
{{--                                                   value="{{ $applicant->id }}">--}}
{{--                                            <input type="hidden" name="job_hidden_id" value="{{ $applicant->sale_id }}">--}}
{{--                                            <textarea name="details" class="form-control" cols="30" rows="4"--}}
{{--                                                      placeholder="TYPE HERE.." required></textarea>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="modal-footer">--}}
{{--                                    <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close--}}
{{--                                    </button>--}}
{{--                                    <button type="submit" class="btn bg-teal legitRipple">Save</button>--}}
{{--                                </div>--}}
{{--                            </form>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
                <!-- /Clear CV Modal -->
                <!-- Reject CV Modal -->
                <div id="reject_cv{{ $applicant->id }}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject CV Notes</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>

                            <form action="{{ route('updateToRejectedCV',['id'=>$applicant->id , 'viewString'=>'applicantWithSentCv']) }}"
                                  method="GET" class="form-horizontal">
                                @csrf()
                                <div class="modal-body">
                                    <div class="form-group row">
                                        <label class="col-form-label col-sm-3">Details</label>
                                        <div class="col-sm-9">
                                            <input type="hidden" name="job_hidden_id" value="{{ $applicant->sale_id }}">
                                            <input type="hidden" name="applicant_hidden_id"
                                                   value="{{ $applicant->id }}">
                                            <textarea name="details" class="form-control" cols="30" rows="4"
                                                      placeholder="TYPE HERE.." required></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close
                                    </button>
                                    <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /Move CV Modal -->
            @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
