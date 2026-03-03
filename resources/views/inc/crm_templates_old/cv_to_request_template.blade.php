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
                <th>landline#</th>
                <th>Job Details</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($applicant_cvs_in_request as $applicant)
                {{--{{ $applicant }}--}}
                @php($disabled_confirm = "")
                @php($disabled_schedule = "")
                {{--@php($x=0)--}}
                @foreach($interview as $inter)
                   {{-- {{ $inter }}--}}

                    @if($inter->applicant_id == $applicant->id)
                        @if($inter->sale_id == $applicant->sale_id)
                            @php($disabled_schedule = "disabled")
                        @endif
                    @else
                        @php($disabled_confirm = "disabled")
                    @endif
                    {{--@php($x++)--}}
                @endforeach
                {{--{{ $x }}--}}

                <tr>
                    <td >{{ $applicant->crm_added_date }}</td>
                    <td >{{ $applicant->crm_added_time }}</td>
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
                    <td>@if(empty($crm_request_save_note->details))
                            {{
                            $applicant->details
                            }}
                        @else
                            {{
                            $crm_request_save_note->details
                             }}
                        @endif</td>
                    <td>
                        <div class="list-icons">
                            <div class="dropdown">
                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                        <a href="#" class="{{$disabled_schedule}} dropdown-item"
                                           data-controls-modal="#schedule_interview{{ $applicant->id }}"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#schedule_interview{{ $applicant->id }}">
                                            <i class="icon-file-confirm"></i>
                                            Schedule Interview
                                        </a>
                                        <a href="#" class="dropdown-item"
                                           data-controls-modal="#confirm_cv{{ $applicant->id }}" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#confirm_cv{{ $applicant->id }}">
                                            <i class="icon-file-confirm"></i>
                                            Move To Confirmation
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
                <!-- Confirmation CV Modal -->
                <div id="confirm_cv{{ $applicant->id }}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm CV Notes</h5>
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
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="request_reject" value="request_reject"
                                            class="btn bg-orange-800 legitRipple">Reject
                                    </button>
                                    <button type="submit" name="request_to_confirm" value="request_to_confirm"
                                            class="{{ $disabled_confirm }} btn bg-dark legitRipple">Confirm
                                    </button>
                                    <button type="submit" name="request_to_save" value="request_to_save" class="btn bg-teal legitRipple">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /Confirmation CV Modal -->
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
                <!-- Schedule Interview Modal -->
                <div id="schedule_interview{{ $applicant->id }}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">{{ $applicant->applicant_name }}</h3>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('scheduleInterview') }}" method="post">
                                    @csrf()
                                    <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                                    <div class="mb-4">
                                        <div class="input-group">
										<span class="input-group-prepend">
											<span class="input-group-text"><i class="icon-calendar5"></i></span>
										</span>
                                            <input type="text" class="form-control pickadate-year" name="schedule_date"
                                                   placeholder="Select Schedule Date">
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <div class="input-group">
										<span class="input-group-prepend">
											<span class="input-group-text"><i class="icon-watch2"></i></span>
										</span>
                                            <input type="text" class="form-control" id="anytime-time"
                                                   name="schedule_time" placeholder="Select Schedule Time">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn bg-teal legitRipple btn-block">Schedule
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Schedule Interview Modal -->
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
            @endforeach()
            </tbody>
        </table>
    </div>
</div>
