<div class="content-wrapper">
    <div class="content">
        <table class="table datatable-sorting">
            <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Schedule Date</th>
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
            @foreach($is_in_crm_confirm as $applicant)
                <tr>
                    <td >{{ $applicant->crm_added_date }}</td>
                    <td >{{ $applicant->crm_added_time }}</td>
                    <td>{{ $applicant->schedule_date }}<br><a href="#" style="margin-left: 15px;">{{ $applicant->schedule_time }}</a></td>
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
                    <td>@if(empty($crm_confirm_save_note->details))
                            {{
                            $applicant->details
                            }}
                        @else
                            {{
                            $crm_confirm_save_note->details
                             }}
                        @endif</td>
                    <td>
                        <div class="list-icons">
                            <div class="dropdown">
                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                        <a href="#" class="dropdown-item"
                                           data-controls-modal="#confirm_cv{{ $applicant->id }}" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#after_interview{{ $applicant->id }}">
                                            <i class="icon-file-confirm"></i>
                                            Accept
                                        </a>
                                    <a href="#" class="dropdown-item"
                                       data-controls-modal="#confirm_cv{{ $applicant->id }}" data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#manager_details{{ $applicant->id }}">
                                        <i class="icon-file-confirm"></i>
                                        Manager Details
                                    </a>
{{--                                    <a href="{{ route('revertCv',['id'=>$applicant->id , 'viewString'=>'applicantWithConfirmCv']) }}"--}}
{{--                                       class="dropdown-item"><i class="icon-file-confirm"></i> revert to sent</a>--}}
                                    <a href="{{ route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]) }}" class="dropdown-item"
                                    ><i class="icon-file-confirm"></i>
                                        View All Notes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- After Interview Note Modal -->
                <div id="after_interview{{ $applicant->id }}" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Interview Notes</h5>
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
                                    <button type="submit" name="interview_not_attend" value="interview_not_attend"
                                            class="btn bg-orange-800 legitRipple">Not Attend
                                    </button>
                                    <button type="submit" name="interview_attend" value="interview_attend"
                                            class="btn bg-dark legitRipple">Attend
                                    </button>
                                    <button type="submit" name="interview_save" value="interview_save" class="btn bg-teal legitRipple">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- ./After Interview Note Modal -->
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
            @endforeach()
            </tbody>
        </table>
    </div>
</div>
