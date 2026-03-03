@extends('layouts.app')

@section('style')

    <script>
        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#applicant_history').DataTable({
                "aoColumnDefs": [{"bSortable": false, "aTargets": [3,7]}],
                "bProcessing": true,
                "bServerSide": false,
                "aaSorting": [[0, "desc"]],
                "sPaginationType": "full_numbers",
                "aLengthMenu": [[10, 50, 100, 500], [10, 50, 100, 500]]
            });
            //table.destroy();
        });

    </script>

@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-light">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicants</span> - History
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Applicants</a>
						<span class="breadcrumb-item">Current</span>
                        <span class="breadcrumb-item active">History</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Invoice template -->
            <div class="card">
                <div class="card-header bg-transparent header-elements-inline">
                    <h6 class="card-title">{{ ucwords(strtolower($applicant->applicant_name)) }}'s Job History</h6>
                    <span>
                        <span class="font-weight-semibold"></span>
                        <a href="#"
                           class="btn btn-sm btn-dark update-history"
                           data-applicant="{{ $applicant->id }}"
                           data-controls-modal="#update_history"
                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                           data-target="#update_history"
                        >Update History</a>
                    </span>

                    <div id="update_history" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <span class="font-weight-semibold">{{ ucwords(strtolower($applicant->applicant_name)) }}</span>'s Update History</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body" id="applicant_update_history" style="max-height: 500px; overflow-y: auto;">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card border-top-3 border-top-slate rounded-left-0">
                                <div class="card-body">
                                    <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                        <div>
                                            Name: <span
                                                    class="font-weight-semibold">{{ ucwords(strtolower($applicant->applicant_name)) }}</span>
                                            <ul class="list list-unstyled mb-0">
                                                <li>Title: <span
                                                            class="font-weight-semibold">{{ strtoupper($applicant->applicant_job_title) }}</span>
                                                </li>
                                                <li>Postcode: <span
                                                            class="font-weight-semibold">{{ strtoupper($applicant->applicant_postcode) }}</span>
                                                </li>
                                                <li>Category: <span
                                                                 class="font-weight-semibold">{{ strtoupper($applicant->job_category) }}</span>
                                                </li>
                                                <li>Source: <span
                                                            class="font-weight-semibold">{{ ucwords(strtolower($applicant->applicant_source)) }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="text-sm-left mb-0 mt-3 mt-sm-0 ml-auto">

                                            <ul class="list list-unstyled mb-0">
                                                <li>Phone No:
                                                    <span class="font-weight-semibold">{{ $applicant->applicant_phone }}</span>
                                                </li>
                                                <li>Home phone:
                                                    <span class="font-weight-semibold">{{ $applicant->applicant_homePhone }}</span>
                                                </li>
                                                <li>Email:
                                                    <span class="font-weight-semibold">{{ $applicant->applicant_email }}</span>
                                                </li>
                                                <li class="dropdown">
                                                    Notes:
                                                    <span class="font-weight-semibold">{{ empty($applicant->module_note) ? $applicant->applicant_notes : $applicant->module_note->details }}</span>
                                                </li>
                                                <li class="dropdown">
                                                    Status: &nbsp;
                                                         <a href="#" class="badge bg-teal align-top">{{ ucwords($applicant->status) }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center">
                                    <span>
                                        <span class="font-weight-semibold"></span>
                                        Posted On: <span
                                                class="font-weight-semibold">{{ \Carbon\Carbon::parse($applicant->applicant_created_at)->format('jS F Y') }}</span>
                                    </span>
									<span>
                                        <a href="#" class="btn-sm bg-orange-800" data-controls-modal="#no_nursing_home_notes{{ $applicant->id }}" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#no_nursing_home_notes{{ $applicant->id }}">No Nursing Home Notes</a>
                                        <a href="#" class="btn-sm bg-primary-600" data-controls-modal="#callback_notes{{ $applicant->id }}" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#callback_notes{{ $applicant->id }}">Callback Notes</a>
                                    </span>
                                    <div id="callback_notes{{ $applicant->id }}" class="modal fade" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header text-primary-600">
                                                    <h5 class="modal-title">{{ $applicant->applicant_name }}'s Callback Notes</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body" style="max-height: 550px; overflow-y: auto;">
                                                    @php($index=1)
                                                    @forelse($applicant->callback_notes as $note)
                                                        <p>
                                                            <span class="font-weight-semibold">{{$index }}.</span>
                                                            <span class="font-weight-semibold">DATE:</span> {{ $note->added_date }} &ensp;|&ensp;
                                                            <span class="font-weight-semibold">TIME:</span> {{ $note->added_time }} &ensp;|&ensp;
                                                            <span class="font-weight-semibold">STAGE:</span> {{ ucwords($note->moved_tab_to) }}&ensp;
                                                            @if($note->status == 'active')
                                                                <a href="#" class="badge bg-teal align-top">{{ $note->status }}</a>
                                                            @else
                                                                <a href="#" class="badge bg-danger align-top">{{ $note->status }}</a>
                                                            @endif
                                                        </p>
                                                        <p>
                                                            <span class="font-weight-semibold">NOTES:</span> {{ $note->details }}
                                                        </p>
                                                        @php($index++)
                                                        <hr class="w-25 center">
                                                    @empty
                                                        <p> No records found.</p>
                                                    @endforelse
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm bg-primary-600 legitRipple" data-dismiss="modal">Close</button>
												</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="no_nursing_home_notes{{ $applicant->id }}" class="modal fade" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header text-orange-800">
                                                    <h5 class="modal-title">{{ $applicant->applicant_name }}'s Callback Notes</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body" style="max-height: 550px; overflow-y: auto;">
                                                    @php($index=1)
                                                    @forelse($applicant->no_nursing_home_notes as $note)
                                                        <p>
                                                            <span class="font-weight-semibold">{{$index }}.</span>
                                                            <span class="font-weight-semibold">DATE:</span> {{ $note->added_date }} &ensp;|&ensp;
                                                            <span class="font-weight-semibold">TIME:</span> {{ $note->added_time }} &ensp;|&ensp;
                                                            <span class="font-weight-semibold">STAGE:</span> {{ ucwords($note->moved_tab_to) }}&ensp;
                                                            @if($note->status == 'active')
                                                                <a href="#" class="badge bg-teal align-top">{{ $note->status }}</a>
                                                            @else
                                                                <a href="#" class="badge bg-danger align-top">{{ $note->status }}</a>
                                                            @endif
                                                        </p>
                                                        <p>
                                                            <span class="font-weight-semibold">NOTES:</span> {{ $note->details }}
                                                        </p>
                                                        @php($index++)
                                                        <hr class="w-25 center">
                                                    @empty
                                                        <p> No records found.</p>
                                                    @endforelse
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm bg-orange-800 legitRipple" data-dismiss="modal">Close</button>	
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Details -->

				@if(!$applicants_in_crm->isEmpty() || !$applicantQualityRejectNote->isEmpty())
                            <table class="table table-hover table-striped" id="applicant_history">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Postcode</th>
                                    <th>Job Details</th>
                                    <th>Head Office</th>
                                    <th>Unit</th>
                                    <th>Stage</th>
                                    <th>Note</th>
                                </tr>
                                </thead>
                                <tbody>
									 
									@if(!$applicants_in_crm->isEmpty())
                                @foreach($applicants_in_crm as $app)
                                    <tr>
                                        <td data-sort="{{ strtotime($app->history_added_date) }}">{{ $app->history_added_date }}</td>
                                        <td>{{ strtoupper($app->sale_job_title) }}</td>
                                        <td>{{ strtoupper($app->sale_postcode) }}</td>
                                        <td>
                                            <a href="#" data-controls-modal="#job_details{{$app->app_id}}-{{$app->sale_id}}" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#job_details{{$app->app_id}}-{{$app->sale_id}}">View Details</a>
                                            <div id="job_details{{$app->app_id}}-{{$app->sale_id}}" class="modal fade" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content pl-3 pr-4">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{$app->applicant_name}}'s Job Details</h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="media flex-column flex-md-row mb-4">
                                                                <div class="media-body">
                                                                    <div class=" header-elements-sm-inline">
                                                                        <h5 class="media-title font-weight-semibold">
                                                                            {{$app->office_name}} / {{$app->unit_name}}
                                                                        </h5>
                                                                        <div>
                                                                            <span class="font-weight-semibold">Posted Date: </span>
                                                                            <span class="mb-3">{{ $app->posted_date }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <ul class="list-inline list-inline-dotted text-muted mb-0">
                                                                        <li class="list-inline-item">
                                                                            {{$app->sales_job_category}}, {{$app->sale_job_title}}
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Job Title: </h6>
                                                                    <p>{{$app->sale_job_title}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Postcode: </h6>
                                                                    <p class="mb-3">{{$app->sale_postcode}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Job Type: </h6>
                                                                    <p class="mb-3">{{$app->job_type}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Timings: </h6>
                                                                    <p class="mb-3">{{$app->timing}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Salary: </h6>
                                                                    <p class="mb-3">{{$app->salary}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Experience: </h6>
                                                                    <p class="mb-3">{{$app->experience}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Qualification: </h6>
                                                                    <p class="mb-3">{{$app->qualification}}</p>
                                                                </div>
                                                                <div class="col-8">
                                                                    <h6 class="font-weight-semibold">Benefits: </h6>
                                                                    <p class="mb-3">{{$app->benefits}}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $app->office_name }}</td>
                                        <td>{{ $app->unit_name }}</td>
                                        <td>{{ $app->sub_stage }}</td>
                                        <td>
                                            <p>
                                                <b>DATE: </b>
                                                {{ $app->crm_added_date }}
                                                <b> TIME: </b>
                                                {{ $app->crm_added_time }}

                                                <a href="#" data-controls-modal="#all_notes{{$app->app_id}}-{{$app->sale_id}}" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#all_notes{{$app->app_id}}-{{$app->sale_id}}">&emsp;<small>ALL NOTES</small></a>
                                                <div id="all_notes{{$app->app_id}}-{{$app->sale_id}}" class="modal fade" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{$app->applicant_name}}'s Notes History for {{$app->sale_job_title}} - {{$app->sale_postcode}}</h5>
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <div class="modal-body" style="max-height: 550px; overflow-y: auto;">
                                            @php($index=1)
                                            @forelse($applicant_crm_notes as $note)
												@if($note->sale_id == $app->sale_id && $note->app_id == $app->app_id)				
                                                <p>
                                                    <span class="font-weight-semibold">{{$index }}.</span>
                                                    <span class="font-weight-semibold">DATE:</span> {{ $note->crm_added_date }} &ensp;|&ensp;
                                                    <span class="font-weight-semibold">TIME:</span> {{ $note->crm_added_time }} &ensp;|&ensp;
                                                    <span class="font-weight-semibold">STAGE:</span> {{ $crm_stages[$note->moved_tab_to] }}
                                                </p>
                                                <p>
                                                    <span class="font-weight-semibold">NOTES:</span> {{ $note->details }}
                                                </p>
                                                @php($index++)
                                                <hr class="w-25 center">
												@endif				
                                            @empty
                                                <p> No records found.</p>
                            @endforelse
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                        </div>
                    </div>
            </div>
        </div>
        </p>
        <p>
            <b>NOTE: </b>
            {{ $app->details }}
        </p>
        </td>
        </tr>
        @endforeach
		@endif
						 @if(!$applicantQualityRejectNote->isEmpty())
                                    @foreach($applicantQualityRejectNote as $app)
                                    <tr>
                                        <td data-sort="{{ strtotime($app->quality_added_date) }}">{{ $app->quality_added_date }}</td>
                                        <td>{{ strtoupper($app->sale_job_title) }}</td>
                                        <td>{{ strtoupper($app->sale_postcode) }}</td>
                                        <td>
                                            <a href="#" data-controls-modal="#job_details{{$app->app_id}}-{{$app->sale_id}}" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#job_details{{$app->app_id}}-{{$app->sale_id}}">View Details</a>
                                            <div id="job_details{{$app->app_id}}-{{$app->sale_id}}" class="modal fade" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content pl-3 pr-4">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{$app->applicant_name}}'s Job Details</h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="media flex-column flex-md-row mb-4">
                                                                <div class="media-body">
                                                                    <div class=" header-elements-sm-inline">
                                                                        <h5 class="media-title font-weight-semibold">
                                                                            {{$app->office_name}} / {{$app->unit_name}}
                                                                        </h5>
                                                                        <div>
                                                                            <span class="font-weight-semibold">Posted Date: </span>
                                                                            <span class="mb-3">{{ $app->posted_date }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <ul class="list-inline list-inline-dotted text-muted mb-0">
                                                                        <li class="list-inline-item">
                                                                            {{$app->sales_job_category}}, {{$app->sale_job_title}}
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Job Title: </h6>
                                                                    <p>{{$app->sale_job_title}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Postcode: </h6>
                                                                    <p class="mb-3">{{$app->sale_postcode}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Job Type: </h6>
                                                                    <p class="mb-3">{{$app->job_type}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Timings: </h6>
                                                                    <p class="mb-3">{{$app->timing}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Salary: </h6>
                                                                    <p class="mb-3">{{$app->salary}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Experience: </h6>
                                                                    <p class="mb-3">{{$app->experience}}</p>
                                                                </div>
                                                                <div class="col-4">
                                                                    <h6 class="font-weight-semibold">Qualification: </h6>
                                                                    <p class="mb-3">{{$app->qualification}}</p>
                                                                </div>
                                                                <div class="col-8">
                                                                    <h6 class="font-weight-semibold">Benefits: </h6>
                                                                    <p class="mb-3">{{$app->benefits}}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $app->office_name }}</td>
                                        <td>{{ $app->unit_name }}</td>
                                        <td>{{'quality_'.$app->moved_tab_to }}</td>
                                        <td>
                                            <p>
                                                <b>DATE: </b>
                                                {{ $app->quality_added_date }}
                                                <b> TIME: </b>
                                                {{ $app->quality_added_time }}

                                                
                                            </p>
                                            <p>
                                                <b>NOTE: </b>
                                                {{ $app->details }}
                                            </p>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
        </tbody>
        </table>



    <!-- Details -->

    @else
        <div class="card-body" style="text-align: center">
            No Data Found Against this applicant
        </div>

    @endif
    </div>
<!-- /invoice template -->


</div>
<!-- /content area -->

@endsection

@section('script')
<script>
    // fetch applicant's update history
    $(document).on('click', '.update-history', function (event) {
        var applicant = $(this).data('applicant');

        $.ajax({
            url: "{{ route('updateHistory') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                module_key: applicant,
                module: "Applicant"
            },
            success: function(response){
                $('#applicant_update_history').html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#applicants_notes_history'+applicant).html(raw_html);
            }
        });
    });

</script>
@endsection
