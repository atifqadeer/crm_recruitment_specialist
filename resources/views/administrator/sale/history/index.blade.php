@extends('layouts.app')

@section('style')

    <script>
        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#sale_history').DataTable({
                "aoColumnDefs": [{"bSortable": false, "aTargets": [8]}],
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
{{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Sale</span> - History
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Sale</a>
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
                    <h6 class="card-title">Sale {{ @$sale->postcode }}'s History</h6>
                    <span>
                        <span class="font-weight-semibold"></span>
                        <a href="#"
                           class="btn btn-sm bg-teal sale-all-notes"
                           data-sale="{{ @$sale->id }}"
                           data-controls-modal="#sale_all_notes"
                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                           data-target="#sale_all_notes"
                        >All Notes</a>
                        <a href="#"
                           class="btn btn-sm btn-dark sale-update-history"
                           data-sale="{{ @$sale->id }}"
                           data-controls-modal="#sale_update_history"
                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                           data-target="#sale_update_history"
                        >Update History</a>
                    </span>
                    <div id="sale_all_notes" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <span class="font-weight-semibold">{{ @$sale->job_title }} - {{ @$sale->postcode }}</span> All Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body" id="sales_notes_history" style="max-height: 500px; overflow-y: auto;">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div id="sale_update_history" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                    <span class="font-weight-semibold">{{ @$sale->job_title }} - {{ @$sale->postcode }}</span> Update History</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body" id="history_details" style="max-height: 500px; overflow-y: auto;">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sale Details -->
                @if($sale)
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card border-top-3 border-top-slate rounded-left-0">
                                    <div class="card-body">
                                        <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                            <div>
                                                <ul class="list list-unstyled mb-0">
                                                    <li>Title:
														<span class="font-weight-semibold">{{ ucwords($sale->job_title) }}<?php echo $sale->job_title_prof!=''? ' ('. ucwords($sec_job_data->specialist_prof).')':'';?></span>
													</li>
                                                    <li>Postcode:
                                                        <span class="font-weight-semibold">{{ $sale->postcode }}</span>
                                                    </li>
                                                    <li>Type:
                                                        <span class="font-weight-semibold">{{ ucwords($sale->job_type) }}</span>
                                                    </li>
                                                    <li>Head Office:
                                                        <span class="font-weight-semibold">{{ $sale->office->office_name }}</span>
                                                    </li>
                                                    <li>Qualification:
                                                        <span class="font-weight-semibold">{{ $sale->office->qualification }}</span>
                                                    </li>
                                                </ul>
                                            </div>

                                            <div class="text- -left mb-0 mt-3 mt-sm-0 ml-auto">

                                                <ul class="list list-unstyled mb-0">
                                                    <li>Salary:
                                                        <span class="font-weight-semibold">{{ $sale->salary }}</span>
                                                    </li>
                                                    <li>Job Category:
                                                        <span class="font-weight-semibold">{{ $sale->job_category }}</span>
                                                    </li>
                                                    <li>Experience:
                                                        <span class="font-weight-semibold">{{ $sale->experience }}</span>
                                                    </li>
                                                    <li>Unit:
                                                        <span class="font-weight-semibold">{{ $sale->unit->unit_name }}</span>
                                                    </li>
                                                    <?php
                                                        $status_class = "bg-teal";
                                                        if ($sale->status == "pending")
                                                            $status_class = "bg-warning";
                                                        elseif ($sale->status == "disable")
                                                            $status_class = "bg-danger";
                                                    ?>
                                                    <li>Status:
                                                        <span class="badge {{ $status_class }} align-top">{{ ucfirst($sale->status) }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center">
                                        <span>
                                            Sent CV: <span class="font-weight-semibold">{{ $sale->active_cvs_count }} out of {{ $sale->send_cv_limit }}</span>
                                        </span>

                                        <ul class="list-inline list-inline-condensed mb-0 mt-2 mt-sm-0">
                                            <li class="list-inline-item">
                                                Posted On: <span class="font-weight-semibold">{{ \Carbon\Carbon::parse($sale->created_at)->format('jS F Y') }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Applicants CVs' -->
                <div class="card-body">
                    <div class="row">
                        <table class="table table-hover table-striped" id="sale_history">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Postcode</th>
                                <th>Phone&num; <br> Landline&num;</th>
                                <th>Stage</th>
                                <th>Sub Stage</th>
                                <th>Note</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($applicants_in_crm as $app)
                                <tr>
                                    <td data-sort="{{ strtotime($app->history_added_date) }}">{{ $app->history_added_date }}</td>
                                    <td>{{ $app->applicant_name }}</td>
                                    <td>{{ ucwords($app->applicant_job_title) }}</td>
                                    <td>{{ $app->job_category }}</td>
                                    <td>{{ $app->applicant_postcode }}</td>
                                    <td>
                                        <span class="font-weight-bold">P&num; </span>{{ $app->applicant_phone }}
                                        <br>
                                        <span class="font-weight-bold">L&num; </span> {{ $app->applicant_homePhone }}
                                    </td>
                                    <td>{{ $app->sub_stage == 'quality_reject' ? 'Quality' : 'CRM' }}</td>
                                    <td>{{ $history_stages[$app->sub_stage] }}</td>
                                    <td>
                                        <p>
                                            <b>DATE: </b>
                                            {{ $app->note_added_date }}
                                            <b> TIME: </b>
                                            {{ $app->note_added_time }}

                                            <a href="#" data-controls-modal="#all_notes{{ $app->app_id }}-{{ $app->sale_id }}" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#all_notes{{ $app->app_id }}-{{ $app->sale_id }}">&emsp;<small>ALL NOTES</small></a>
                                            <div id="all_notes{{ $app->app_id }}-{{ $app->sale_id }}" class="modal fade" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{$app->applicant_name}}'s Notes History</h5>
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
																	
																	@if($note->moved_tab_to=='start_date_hold_save')
                                                                    {
                                                                    <span class="font-weight-semibold">STAGE:</span> {{ $crm_stages['start_date_hold'] }}
                                                                    }
                                                                    @else
                                                                    {
                                                                    <span class="font-weight-semibold">STAGE:</span> {{ $crm_stages[$note->moved_tab_to] }}
                                                                    }
																	@endif
                                                                    
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
                            @foreach($applicants_in_quality_reject as $app)
                                <tr>
                                    <td data-sort="{{ strtotime($app->history_added_date) }}">{{ $app->history_added_date }}</td>
                                    <td>{{ $app->applicant_name }}</td>
                                    <td>{{ ucwords($app->applicant_job_title) }}</td>
                                    <td>{{ $app->job_category }}</td>
                                    <td>{{ $app->applicant_postcode }}</td>
                                    <td>
                                        <span class="font-weight-bold">P&num; </span>{{ $app->applicant_phone }}
                                        <br>
                                        <span class="font-weight-bold">L&num; </span>{{ $app->applicant_homePhone }}
                                    </td>
                                    <td>{{ $app->sub_stage == 'quality_reject' ? 'Quality' : 'CRM' }}</td>
                                    <td>{{ $history_stages[$app->sub_stage] }}</td>
                                    <td>
                                        <p>
                                            <b>DATE: </b>
                                            {{ $app->note_added_date }}
                                            <b> TIME: </b>
                                            {{ $app->note_added_time }}
                                        </p>
                                        <p>
                                            <b>NOTE: </b>
                                            {{ $app->details }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
					@if($applicants_in_quality!='')
							@foreach($applicants_in_quality as $app)
                                <tr>
                                    <td data-sort="{{ strtotime($app->history_added_date) }}">{{ $app->history_added_date }}</td>
                                    <td>{{ $app->applicant_name }}</td>
                                    <td>{{ ucwords($app->applicant_job_title) }}</td>
                                    <td>{{ $app->job_category }}</td>
                                    <td>{{ $app->applicant_postcode }}</td>
                                    <td>
                                        <span class="font-weight-bold">P&num; </span>{{ $app->applicant_phone }}
                                        <br>
                                        <span class="font-weight-bold">L&num; </span>{{ $app->applicant_homePhone }}
                                    </td>
                                    <td>Quality</td>
                                    <td>CVs</td>
                                    <td>
                                        <p>
                                            <b>DATE: </b>
                                            {{ $app->note_added_date }}
                                            <b> TIME: </b>
                                            {{ $app->note_added_time }}
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
                    </div>
                </div>
            </div>
            <!-- /invoice template -->

        </div>
        <!-- /content area -->

@endsection

@section('script')
<script>
    // fetch notes history
    $(document).on('click', '.sale-all-notes', function (event) {
        var sale = $(this).data('sale');

        $.ajax({
            url: "{{ route('notesHistory') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                module_key: sale,
                module: "Sale"
            },
            success: function(response){
                $('#sales_notes_history').html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#sales_notes_history').html(raw_html);
            }
        });
    });
	
	// fetch sale's update history
    $(document).on('click', '.sale-update-history', function (event) {
        var sale = $(this).data('sale');

        $.ajax({
            url: "{{ route('sale-update-history') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                module_key: sale,
                module: "Sale"
            },
            success: function(response){
                $('#history_details').html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#history_details').html(raw_html);
            }
        });
    });
</script>
@endsection
