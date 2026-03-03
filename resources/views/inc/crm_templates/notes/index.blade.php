@extends('layouts.app')

@section('content')
<!-- Main content -->
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">
        <div class="page-header-content header-elements-inline">
            <div class="page-title">
                <h5>
                    <i class="icon-arrow-left52 mr-2"></i>
                    <span class="font-weight-semibold">CRM</span> - Notes History
                </h5>
            </div>
        </div>

        <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
            <div class="d-flex">
                <div class="breadcrumb">
                    <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                    <a href="#" class="breadcrumb-item">Current</a>
                    <span class="breadcrumb-item active">CRM</span>
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
                <h6 class="card-title">Note's History</h6>
            </div>
            @if((!empty($cv_send_in_quality_notes)) || (!empty($applicant_in_quality)) || (!empty($applicant_in_crm)))
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <span class="text-muted">CV Search Note:</span>
                        <div class="card border-top-3 border-top-slate rounded-left-0">
                            <div class="card-body">
                                <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                    @empty($cv_send_in_quality_notes)
                                        <div>
                                            No note found.
                                        </div>
                                    @else
										<div class="col-lg-9 text-md-left mb-0 mt-3 mt-sm-0 mr-auto">
											<strong>Date:</strong> 
											<span class="font-weight-semibold">{{ $cv_send_in_quality_notes->send_added_date }}</span>
											<ul class="list list-unstyled mb-0">
												<li>
													<strong>Note:</strong> 
													<p class="font-weight-semibold">{!! $cv_send_in_quality_notes->details !!}</p>
												</li>
											</ul>
										</div>

										<div class="col-lg-3 text-md-right mb-0 mt-3 mt-sm-0 ml-auto">
											<strong>Time:</strong>
											<span class="font-weight-semibold">{{ $cv_send_in_quality_notes->send_added_time }}</span>
											<ul class="list list-unstyled mb-0">
												<li class="dropdown">
													<strong>Status:</strong>
													<a href="#" class="badge bg-teal align-top">{{ ucfirst($cv_send_in_quality_notes->status)
														}}</a>
												</li>
											</ul>
										</div>
                                    @endempty
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <span class="text-muted">Quality Note:</span>
                        <div class="card border-top-3 border-top-slate rounded-left-0">
                            <div class="card-body">
                                <div class="d-sm-flex align-item-sm-center flex-sm-nowrap">
                                    @empty($applicant_in_quality)
                                        <div>
                                            No note found.
                                        </div>
                                    @else
										<div class="col-lg-9 text-md-left mb-0 mt-3 mt-sm-0 mr-auto">
											<strong>Date:</strong> 
											<span class="font-weight-semibold">{{ $applicant_in_quality->quality_added_date }}</span>
											<ul class="list list-unstyled mb-0">
												<li>
													<strong>Note:</strong> 
													<p class="font-weight-semibold">{!! $applicant_in_quality->details !!}</p>
												</li>
											</ul>
										</div>

										<div class="col-lg-3 text-sm-right mb-0 mt-3 mt-sm-0 ml-auto">
											<strong>Time:</strong>
											<span class="font-weight-semibold">{{ $applicant_in_quality->quality_added_time }}</span>
											<ul class="list list-unstyled mb-0">
												<li class="dropdown">
													<strong>Status:</strong> 
													<a href="#" class="badge bg-teal align-top">{{ ucfirst($applicant_in_quality->moved_tab_to)
														}}</a>
												</li>
											</ul>
										</div>
                                    @endempty
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card-body">
                            <h5>Applicant's Note In CRM</h5>
                        </div>
                        <table class="table data_table" id="crm_note_history">
                            <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Active In</th>
                                <th>Notes</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
								<?php $i = 0;?>
								@forelse($applicant_in_crm as $applicant)
									<tr>

										<td><?php echo $i = $i+1;?></td>
										<td>{{ date('M d, Y', strtotime($applicant->created_at)) }}</td>
										<td>{{ date('h:i:s A', strtotime($applicant->created_at)) }}</td>
										<td>{{ $applicant->moved_tab_to }}</td>
										<td>{!! $applicant->details !!}</td>
										<td>@if($applicant->status == 'active')
											<h5><span class="badge badge-success">Active</span></h5>
											@else
											<h5><span class="badge badge-danger">Disable</span></h5>
											@endif
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="6" style="text-align: center">Data Not Found</td>
									</tr>
								@endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="card-body" style="text-align: center">
                No Found Any Relevent Notes
            </div>

            @endif
        </div>
        <!-- /invoice template -->

    </div>
    <!-- /content area -->
    <script>
        $(document).ready(function() {
            $('#crm_note_history').DataTable({
                "paging": true, // Enable pagination
                "lengthChange": true, // Enable length menu
                "searching": true, // Enable search
                "ordering": true, // Enable sorting
                "info": true, // Show table information
                "autoWidth": false, // Disable auto-width calculation
                "responsive": true, // Enable responsive behavior
            });
        });
    </script>
    @endsection()
