@extends('layouts.app')

@section('style')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<!-- Include jQuery UI Datepicker -->
<!--<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">-->
<!--<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>-->
<style>
	.buttons-columnVisibility{
		border: 1px solid
	}
	.dt-button-active{
		background-color: #007bff;
		color: #fff;
	}
</style>
	<script>
		$(document).ready(function() {
			// Suppress DataTables errors (optional, use with caution)
			$.fn.dataTable.ext.errMode = 'none';

			// Initialize DataTables for the "Resource Roles" tab
			var roleResource = $('#resource_roles_table').DataTable({
				"dom": 'Blfrtip',
				"buttons": [
					{
						extend: 'colvis',
						text: '<i class="fas fa-eye"></i>&nbsp;',
						columns: ':not(:first)', // Exclude the serial column from visibility toggle
						className: 'btn btn-primary',
					}
				],
				"columnDefs": [
					{ visible: true, targets: [0, 1, 2, 3, 4, 5, 6, 7, 9, 10, 12, 14, 16, 18, 21, 22, 24] }, // Default visible columns
					{ visible: false, targets: '_all' }, // Hide all other columns initially
					{ width: '5%', targets: 0 }, // Set width for serial number column
					{ width: '10%', targets: [1, 2, 3, 4, 5, 6, 7, 9, 10, 12, 14, 16, 18, 21, 22, 24] } // Set width for other columns
				],
				"processing": true,
				"serverSide": true,
				"ajax": {
					"url": "{!! url('resourcesReportAjax') !!}",
					"type": "GET",
					"dataType": "json",
					"error": function(xhr, error, thrown) {
						console.log("Error:", error);
						alert("An error occurred while loading the data. Please try again.");
					}
				},
				"lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
				"pageLength": 10,
				"order": [],
				"scrollX": true,
				"scrollCollapse": true,
				"columns": [
					{
						data: null,
						name: 'serial',
						render: function(data, type, row, meta) {
							return meta.row + meta.settings._iDisplayStart + 1;
						},
						orderable: false,
						searchable: false
					},
					{ 
						data: 'user_name', // Column 1: User Name
						name: 'user_name',
						render: function(data, type, row) {
							// Add an icon based on the logged_in status
							var icon = row.logged_in === 'Yes' 
								? '<i class="fas fa-check-circle text-success"></i>' // Checkmark icon for "Yes"
								: '<i class="fas fa-times-circle text-danger"></i>'; // Cross icon for "No"

							// Return the user name with the icon
							return icon + ' ' + data;
						}
					},
					{ data: 'stats.engagedTotal', name: 'stats.engagedTotal' },
					{ data: 'stats.engagedCreated', name: 'stats.engagedCreated' },
					{ data: 'stats.engagedUpdated', name: 'stats.engagedUpdated' },
					{ data: 'stats.sms', name: 'stats.sms' },
					{ data: 'stats.calls', name: 'stats.calls' },
					{ data: 'stats.cvs_quality_sent', name: 'stats.cvs_quality_sent' },
					{ data: 'stats.cvs_rejected', name: 'stats.cvs_rejected' },
					{ data: 'stats.cvs_cleared', name: 'stats.cvs_cleared' },
					{ data: 'stats.crm_sent_cvs', name: 'stats.crm_sent_cvs' },
					{ data: 'stats.crm_rejected_cv', name: 'stats.crm_rejected_cv' },
					{ data: 'stats.crm_request', name: 'stats.crm_request' },
					{ data: 'stats.crm_rejected_by_request', name: 'stats.crm_rejected_by_request' },
					{ data: 'stats.crm_confirmation', name: 'stats.crm_confirmation' },
					{ data: 'stats.crm_rebook', name: 'stats.crm_rebook' },
					{ data: 'stats.crm_attended', name: 'stats.crm_attended' },
					{ data: 'stats.crm_not_attended', name: 'stats.crm_not_attended' },
					{ data: 'stats.crm_start_date', name: 'stats.crm_start_date' },
					{ data: 'stats.crm_start_date_hold', name: 'stats.crm_start_date_hold' },
					{ data: 'stats.crm_declined', name: 'stats.crm_declined' },
					{ data: 'stats.crm_invoice', name: 'stats.crm_invoice' },
					{ data: 'stats.crm_invoice_sent', name: 'stats.crm_invoice_sent' },
					{ data: 'stats.crm_dispute', name: 'stats.crm_dispute' },
					{ data: 'stats.crm_paid', name: 'stats.crm_paid' }
				],
				"footerCallback": function(row, data, start, end, display) {
					var api = this.api();

					// Remove formatting to get integer data for summation
					var intVal = function(i) {
						return typeof i === 'string' ?
							i.replace(/[\$,]/g, '') * 1 :
							typeof i === 'number' ?
							i : 0;
					};

					// Calculate totals for each column
					api.columns().every(function() {
						var column = this;
						var columnIndex = column.index();

						// Skip the first column (serial number) and non-numeric columns
						if (columnIndex === 0 || columnIndex === 1) {
							return;
						}

						// Calculate the total for the column
						var total = api
							.column(columnIndex, { page: 'current' })
							.data()
							.reduce(function(a, b) {
								return intVal(a) + intVal(b);
							}, 0);

						// Update the footer cell with the total
						$(api.column(columnIndex).footer()).html(total);
					});
				},
			});

			var roleQuality = $('#quality_roles_table').DataTable({
				"dom": 'Blfrtip',
				"buttons": [
					{
						extend: 'colvis',
						text: '<i class="fas fa-eye"></i>&nbsp;',
						columns: ':not(:first)',
						className: 'btn btn-primary',
					}
				],
				"columnDefs": [
					{ visible: true, targets: [0, 1, 2, 3, 4, 5, 6, 7, 8] },
					{ visible: false, targets: '_all' }
				],
				"processing": true,
				"serverSide": true,
				"ajax": {
					"url": "{!! url('qualityReportAjax') !!}",
					"type": "GET",
					"dataType": "json",
					"error": function(xhr, error, thrown) {
						console.log("Error:", error);
						alert("An error occurred while loading the data. Please try again.");
					}
				},
				"lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
				"pageLength": 10,
				"order": [],
				"footerCallback": function(row, data, start, end, display) {
					var api = this.api();

					// Remove formatting to get integer data for summation
					var intVal = function(i) {
						return typeof i === 'string' ?
							i.replace(/[\$,]/g, '') * 1 :
						typeof i === 'number' ?
							i : 0;
					};

					// Calculate totals for each column
					api.columns().every(function() {
						var column = this;
						var columnIndex = column.index();

						// Skip the first column (serial number) and non-numeric columns
						if (columnIndex === 0 || columnIndex === 1) {
							return;
						}

						// Calculate the total for the column
						var total = api
						.column(columnIndex, { page: 'current' })
						.data()
						.reduce(function(a, b) {
							return intVal(a) + intVal(b);
						}, 0);

						// Update the footer cell with the total
						$(api.column(columnIndex).footer()).html(total);
					});
				},
				"columns": [
					{
						data: null,
						name: 'serial',
						render: function(data, type, row, meta) {
							return meta.row + meta.settings._iDisplayStart + 1;
						},
						orderable: false,
						searchable: false
					},
					{ 
						data: 'user_name', // Column 1: User Name
						name: 'user_name',
						render: function(data, type, row) {
							// Add an icon based on the logged_in status
							var icon = row.logged_in === 'Yes' 
								? '<i class="fas fa-check-circle text-success"></i>' // Checkmark icon for "Yes"
								: '<i class="fas fa-times-circle text-danger"></i>'; // Cross icon for "No"

							// Return the user name with the icon
							return icon + ' ' + data;
						}
					},
					{ data: 'stats.engagedTotal', name: 'stats.engagedTotal' },
					{ data: 'stats.cvs_cleared', name: 'stats.cvs_cleared' },
					{ data: 'stats.cvs_opened', name: 'stats.cvs_opened' },
					{ data: 'stats.cvs_rejected', name: 'stats.cvs_rejected' },
					{ data: 'stats.crm_rejected_cv', name: 'stats.crm_rejected_cv' },
					{ data: 'stats.crm_request', name: 'stats.crm_request' },
					{ data: 'stats.crm_rejected_by_request', name: 'stats.crm_rejected_by_request' }
				]
			});

			// Initialize DataTables for the "CRM Roles" tab
			var roleCRM = $('#crm_roles_table').DataTable({
				"processing": true,
				"serverSide": true,
				"ajax": {
					"url": "{!! url('crmReportAjax') !!}",
					"type": "GET",
					"dataType": "json",
					"error": function(xhr, error, thrown) {
						console.log("Error:", error);
						alert("An error occurred while loading the data. Please try again.");
					}
				},
				"lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
				"pageLength": 10,
				"order": [],
				"columns": [
					{
						data: null,
						name: 'serial',
						render: function(data, type, row, meta) {
							return meta.row + meta.settings._iDisplayStart + 1;
						},
						orderable: false,
						searchable: false
					},
					{ 
						data: 'user_name', // Column 1: User Name
						name: 'user_name',
						render: function(data, type, row) {
							// Add an icon based on the logged_in status
							var icon = row.logged_in === 'Yes' 
								? '<i class="fas fa-check-circle text-success"></i>' // Checkmark icon for "Yes"
								: '<i class="fas fa-times-circle text-danger"></i>'; // Cross icon for "No"

							// Return the user name with the icon
							return icon + ' ' + data;
						}
					},
					{ data: 'stats.engagedTotal', name: 'stats.engagedTotal' },
					{ data: 'stats.engagedApplicants', name: 'stats.engagedApplicants' },
					{ data: 'stats.engagedSales', name: 'stats.engagedSales' },
					{ data: 'stats.sms', name: 'stats.sms' },
					{ data: 'stats.calls', name: 'stats.calls' },
					{ data: 'stats.crm_request', name: 'stats.crm_request' },
					{ data: 'stats.crm_rejected_by_request', name: 'stats.crm_rejected_by_request' },
					{ data: 'stats.crm_confirmation', name: 'stats.crm_confirmation' },
					{ data: 'stats.crm_attended', name: 'stats.crm_attended' },
					{ data: 'stats.crm_not_attended', name: 'stats.crm_not_attended' }
				],
				"footerCallback": function(row, data, start, end, display) {
						var api = this.api();

						// Remove formatting to get integer data for summation
						var intVal = function(i) {
							return typeof i === 'string' ?
								i.replace(/[\$,]/g, '') * 1 :
								typeof i === 'number' ?
								i : 0;
						};

						// Calculate totals for each column
						api.columns().every(function() {
							var column = this;
							var columnIndex = column.index();

							// Skip the first column (serial number) and non-numeric columns
							if (columnIndex === 0 || columnIndex === 1) {
								return;
							}

							// Calculate the total for the column
							var total = api
								.column(columnIndex, { page: 'current' })
								.data()
								.reduce(function(a, b) {
									return intVal(a) + intVal(b);
								}, 0);

							// Update the footer cell with the total
							$(api.column(columnIndex).footer()).html(total);
						});
					},
			});

			// Initialize DataTables for the "Sale Roles" tab
			var roleSale = $('#sale_roles_table').DataTable({
				"processing": true,
				"serverSide": true,
				"ajax": {
					"url": "{!! url('saleReportAjax') !!}",
					"type": "GET",
					"dataType": "json",
					"error": function(xhr, error, thrown) {
						console.log("Error:", error);
						alert("An error occurred while loading the data. Please try again.");
					}
				},
				"lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
				"pageLength": 10,
				"order": [],
				"columns": [
					{
						data: null,
						name: 'serial',
						render: function(data, type, row, meta) {
							return meta.row + meta.settings._iDisplayStart + 1;
						},
						orderable: false,
						searchable: false
					},
					{ 
						data: 'user_name', // Column 1: User Name
						name: 'user_name',
						render: function(data, type, row) {
							// Add an icon based on the logged_in status
							var icon = row.logged_in === 'Yes' 
								? '<i class="fas fa-check-circle text-success"></i>' // Checkmark icon for "Yes"
								: '<i class="fas fa-times-circle text-danger"></i>'; // Cross icon for "No"

							// Return the user name with the icon
							return icon + ' ' + data;
						}
					},
					{ data: 'stats.calls', name: 'stats.calls' },
					{ data: 'stats.engagedTotal', name: 'stats.engagedTotal' },
					{ data: 'stats.engagedCreated', name: 'stats.engagedCreated' },
					{ data: 'stats.engagedUpdated', name: 'stats.engagedUpdated', orderable: true },
					{ data: 'stats.engagedReopened', name: 'stats.engagedReopened' },
					{ data: 'stats.engagedOnHold', name: 'stats.engagedOnHold' },
					{ data: 'stats.engagedClosed', name: 'stats.engagedClosed' }
				],
				"footerCallback": function(row, data, start, end, display) {
						var api = this.api();

						// Remove formatting to get integer data for summation
						var intVal = function(i) {
							return typeof i === 'string' ?
								i.replace(/[\$,]/g, '') * 1 :
								typeof i === 'number' ?
								i : 0;
						};

						// Calculate totals for each column
						api.columns().every(function() {
							var column = this;
							var columnIndex = column.index();

							// Skip the first column (serial number) and non-numeric columns
							if (columnIndex === 0 || columnIndex === 1) {
								return;
							}

							// Calculate the total for the column
							var total = api
								.column(columnIndex, { page: 'current' })
								.data()
								.reduce(function(a, b) {
									return intVal(a) + intVal(b);
								}, 0);

							// Update the footer cell with the total
							$(api.column(columnIndex).footer()).html(total);
						});
					},
			});

			// Initialize DataTables for the "Data Entry Roles" tab
			var roleDataEntry = $('#data_entry_roles_table').DataTable({
				processing: true,
				serverSide: true,
				ajax: {
					url: "{!! url('dataEntryReportAjax') !!}",
					type: "GET",
					dataType: "json",
					error: function(xhr, error, thrown) {
						console.log("Error:", error);
						alert("An error occurred while loading the data. Please try again.");
					}
				},
				lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
				pageLength: 10,
				order: [], // No default sorting
				columns: [
					{
						data: null, // Column 0: Serial Number
						name: 'serial',
						render: function(data, type, row, meta) {
							return meta.row + meta.settings._iDisplayStart + 1;
						},
						orderable: false, // Disable sorting for serial column
						searchable: false
					},
					{ 
						data: 'user_name', // Column 1: User Name
						name: 'user_name',
						render: function(data, type, row) {
							// Add an icon based on the logged_in status
							var icon = row.logged_in === 'Yes' 
								? '<i class="fas fa-check-circle text-success"></i>' // Checkmark icon for "Yes"
								: '<i class="fas fa-times-circle text-danger"></i>'; // Cross icon for "No"

							// Return the user name with the icon
							return icon + ' ' + data;
						}
					},
					{ data: 'stats.engagedTotal', name: 'stats.engagedTotal' }, // Column 2: Engaged Total
					{ data: 'stats.engagedCreated', name: 'stats.engagedCreated' }, // Column 3: Engaged Created
					{ data: 'stats.engagedUpdated', name: 'stats.engagedUpdated' } // Column 4: Engaged Updated
				],
				footerCallback: function(row, data, start, end, display) {
					var api = this.api();

					// Remove formatting to get integer data for summation
					var intVal = function(i) {
						return typeof i === 'string' ?
							i.replace(/[\$,]/g, '') * 1 :
							typeof i === 'number' ?
							i : 0;
					};

					// Calculate totals for each column
					api.columns().every(function() {
						var column = this;
						var columnIndex = column.index();

						// Skip the first column (serial number) and non-numeric columns
						if (columnIndex === 0 || columnIndex === 1) {
							return;
						}

						// Calculate the total for the column
						var total = api
							.column(columnIndex, { page: 'current' })
							.data()
							.reduce(function(a, b) {
								return intVal(a) + intVal(b);
							}, 0);

						// Update the footer cell with the total
						$(api.column(columnIndex).footer()).html(total);
					});
				}
			});

			// Add a click event listener to the tab links
			$('.nav-tabs a').on('shown.bs.tab', function(e) {
				var targetTab = $(e.target).attr("href");
				// Reload DataTable for the active tab
				if (targetTab === "#resource_roles") {
					roleResource.ajax.reload();
				} else if (targetTab === "#quality_roles") {
					roleQuality.ajax.reload();
				} else if (targetTab === "#crm_roles") {
					roleCRM.ajax.reload();
				} else if (targetTab === "#sale_roles") {
					roleSale.ajax.reload();
				} else if (targetTab === "#data_entry_roles") {
					roleDataEntry.ajax.reload();
				}
			});

			// Filter functions for each tab
			$('#filter_resource').on('click', function(e) {
				e.preventDefault(); // Prevent default form submission
				var startDate = $('#resource_start_date').val();
				var endDate = $('#resource_end_date').val();
				console.log('Filter Resource:', startDate, endDate); // Debugging
				roleResource.ajax.url("{!! url('resourcesReportAjax') !!}?start_date=" + startDate + "&end_date=" + endDate).load();
			});

			$('#filter_crm').on('click', function(e) {
				e.preventDefault(); // Prevent default form submission
				var startDate = $('#crm_start_date').val();
				var endDate = $('#crm_end_date').val();
				console.log('Filter CRM:', startDate, endDate); // Debugging
				roleCRM.ajax.url("{!! url('crmReportAjax') !!}?start_date=" + startDate + "&end_date=" + endDate).load();
			});

			$('#filter_sale').on('click', function(e) {
				e.preventDefault(); // Prevent default form submission
				var startDate = $('#sale_start_date').val();
				var endDate = $('#sale_end_date').val();
				console.log('Filter Sale:', startDate, endDate); // Debugging
				roleSale.ajax.url("{!! url('saleReportAjax') !!}?start_date=" + startDate + "&end_date=" + endDate).load();
			});

			$('#filter_quality').on('click', function(e) {
				e.preventDefault(); // Prevent default form submission
				var startDate = $('#quality_start_date').val();
				var endDate = $('#quality_end_date').val();
				console.log('Filter Quality:', startDate, endDate); // Debugging
				roleQuality.ajax.url("{!! url('qualityReportAjax') !!}?start_date=" + startDate + "&end_date=" + endDate).load();
			});

			$('#filter_data_entry').on('click', function(e) {
				e.preventDefault(); // Prevent default form submission
				var startDate = $('#data_entry_start_date').val();
				var endDate = $('#data_entry_end_date').val();
				console.log('Filter Data Entry:', startDate, endDate); // Debugging
				roleDataEntry.ajax.url("{!! url('dataEntryReportAjax') !!}?start_date=" + startDate + "&end_date=" + endDate).load();
			});

		});
	</script>
@endsection 

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                   <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Reports </span> - Users Report
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Reports</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Users Report</span>
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
                    <ul class="nav nav-tabs nav-tabs-highlight">
                          <li class="nav-item">
                              <a href="#resource_roles" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="resource_roles_table">Resource Team</a>
                          </li>
                        <li class="nav-item">
                              <a href="#crm_roles" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_roles_table">CRM Team</a>
                          </li>
                        <li class="nav-item">
                              <a href="#sale_roles" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="sale_roles_table">Sale Team</a>
                          </li>
                        <li class="nav-item">
                              <a href="#quality_roles" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="quality_roles_table">Quality Team</a>
                          </li>
						 <li class="nav-item">
                              <a href="#data_entry_roles" class="nav-link legitRipple" data-toggle="tab" data-datatable_name = "data_entry_roles_table">Data Entry</a>
                          </li>
                      </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active px-2" id="resource_roles">	
						<!-- Start and End Date Filter for Resource Roles -->
                        <div class="form-group row justify-content-end">
                            <label for="resource_start_date" class="col-form-label col-md-1 text-right">Start Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="resource_start_date" value="{{ now()->toDateString() }}" name="resource_start_date">
                            </div>
                            <label for="resource_end_date" class="col-form-label col-md-1 text-right">End Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="resource_end_date" value="{{ now()->toDateString() }}" name="resource_end_date">
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-primary" id="filter_resource"><i class="fa fa-filter"></i> Filter</button>
                            </div>
                        </div>
                        <table class="table table-hover table-striped" id="resource_roles_table">
                            <thead>
								<tr style="border:1px solid black;text-align:center;background-color:#d3cfcf">
									<!-- Main Heading: General Information -->
									<th colspan="2" style="border:1px solid black;">General Information</th>

									<!-- Main Heading: Communication -->
									<th colspan="5" style="border:1px solid black;">Communication</th>

									<!-- Main Heading: Quality -->
									<th colspan="3" style="border:1px solid black;">Quality</th>

									<!-- Main Heading: crm -->
									<th colspan="10" style="border:1px solid black;">CRM</th>

									<!-- Main Heading: Financial -->
									<th colspan="5" style="border:1px solid black;">Financial</th>
								</tr>
								<tr>
									<!-- Subheadings for General Information -->
									<th>Sr#</th>
									<th>Name</th>

									<!-- Subheadings for Communication -->
									<th>Engaged<br>(Total)</th>
									<th>Applicants<br>(Created)</th>
									<th>Applicants<br>(Updated)</th>
									<th>SMS</th>
									<th>Calls</th>

									<!-- Subheadings for Quality -->
									<th>Quality<br>(Sent)</th>
									<th>Rejected</th>
									<th>Cleared</th>

									<!-- Subheadings for crm -->
									<th>CV Sent</th>
									<th>Reject</th>
									<th>Request</th>
									<th>Reject<br>(Request)</th>
									<th>Confirm</th>
									<th>Rebook</th>
									<th>Attended</th>
									<th>Not Attended</th>
									<th>Start Date<br>(Accepted)</th>
									<th>Start Date<br>(Hold)</th>

									<!-- Subheadings for Financial -->
									<th>Declined</th>
									<th>Invoice</th>
									<th>Invoice Sent<br>(M)</th>
									<th>Dispute</th>
									<th colspan="2">Paid<br>(M)</th>
								</tr>
							</thead>
                            <tbody>
                            </tbody>
							<tfoot>
								<tr style="background-color:#d3cfcf">
									<th></th>
									<th>Total</th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th colspan="2"></th>
							</tfoot>
                        </table>
                    </div>
                    <div class="tab-pane px-2" id="crm_roles">
						<!-- Start and End Date Filter for CRM Roles -->
                        <div class="form-group row justify-content-end">
                            <label for="crm_start_date" class="col-form-label col-md-1 text-right">Start Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="crm_start_date" value="{{ now()->toDateString() }}" name="crm_start_date">
                            </div>
                            <label for="crm_end_date" class="col-form-label col-md-1 text-right">End Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="crm_end_date" value="{{ now()->toDateString() }}" name="crm_end_date">
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-primary" id="filter_crm"><i class="fa fa-filter"></i> Filter</button>
                            </div>
                        </div>
                        <table class="table table-hover table-striped" id="crm_roles_table">
                            <thead>
                                <tr>
                                    <th>Sr#</th>
                                    <th>Name</th>
									<th>Engaged (Total)</th>
                                    <th>Engaged (Applicants)</th>
									<th>Engaged (Sales)</th>
                                    <th>SMS</th>
                                    <th>Calls</th>
									<th>Request</th>
									<th>Request (Reject)</th>
									<th>Confirmation</th>
									<th>Attended</th>
									<th>Not Attended</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
							<tfoot>
								<tr style="background-color:#d3cfcf">
									<th></th>
									<th>Total</th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</tfoot>
                        </table>
                    </div>
                    <div class="tab-pane px-2" id="sale_roles">
						 <!-- Start and End Date Filter for Sale Roles -->
                        <div class="form-group row justify-content-end">
                            <label for="sale_start_date" class="col-form-label col-md-1 text-right">Start Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="sale_start_date" value="{{ now()->toDateString() }}" name="sale_start_date">
                            </div>
                            <label for="sale_end_date" class="col-form-label col-md-1 text-right">End Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="sale_end_date" value="{{ now()->toDateString() }}" name="sale_end_date">
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-primary" id="filter_sale"><i class="fa fa-filter"></i> Filter</button>
                            </div>
                        </div>
                        <table class="table table-hover table-striped" id="sale_roles_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Sr#</th>
                                    <th>Name</th>
									<th>Calls</th>
									<th>Engaged (Total)</th>
                                    <th>Created</th>
									<th>Updated</th>
									<th>Re-Opened</th>
									<th>On-Hold</th>
									<th>Closed</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
							<tfoot>
								<tr style="background-color:#d3cfcf">
									<th></th>
									<th>Total</th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</tfoot>
                        </table>
                    </div>
                    <div class="tab-pane px-2" id="quality_roles">
						 <!-- Start and End Date Filter for Quality Roles -->
                        <div class="form-group row justify-content-end">
                            <label for="quality_start_date" class="col-form-label col-md-1 text-right">Start Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="quality_start_date" value="{{ now()->toDateString() }}" name="quality_start_date">
                            </div>
                            <label for="quality_end_date" class="col-form-label col-md-1 text-right">End Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="quality_end_date" value="{{ now()->toDateString() }}" name="quality_end_date">
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-primary" id="filter_quality"><i class="fa fa-filter"></i> Filter</button>
                            </div>
                        </div>
						<table class="table table-hover table-striped" id="quality_roles_table">
							<thead>
								<!-- Main Headings -->
								<tr style="border:1px solid black;text-align:center;background-color:#d3cfcf">
									<th colspan="2" style="border:1px solid black;">General Information</th>
									<th colspan="4" style="border:1px solid black;">Quality</th>
									<th colspan="3" style="border:1px solid black;">CRM</th>
								</tr>
								<!-- Subheadings -->
								<tr>
									<th>Sr#</th>
									<th>Name</th>
									<!-- Quality -->
									<th>Engaged (Total)</th>
									<th>Cleared</th>
									<th>Open CV</th>
									<th>Rejected</th>
									<!-- CRM -->
									<th>Rejected CV</th>
									<th>Request</th>
									<th>Request Reject</th>
								</tr>
							</thead>
							<tbody>
								<!-- Data will be populated by DataTables -->
							</tbody>
							<tfoot>
								<tr style="background-color:#d3cfcf">
									<th></th>
									<th>Total</th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</tfoot>
						</table>
					</div>
					<div class="tab-pane px-2" id="data_entry_roles">
						<!-- Start and End Date Filter for Data Entry Roles -->
                        <div class="form-group row justify-content-end">
                            <label for="data_entry_start_date" class="col-form-label col-md-1 text-right">Start Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="data_entry_start_date" value="{{ now()->toDateString() }}" name="data_entry_start_date">
                            </div>
                            <label for="data_entry_end_date" class="col-form-label col-md-1 text-right">End Date:</label>
                            <div class="col-md-2">
                                <input type="date" class="form-control datepicker" id="data_entry_end_date" value="{{ now()->toDateString() }}" name="data_entry_end_date">
                            </div>
                            <div class="col-md-1 text-right">
                                <button class="btn btn-primary" id="filter_data_entry"><i class="fa fa-filter"></i> Filter</button>
                            </div>
                        </div>
                        <table class="table table-hover table-striped" id="data_entry_roles_table">
                            <thead>
                                <tr>
                                    <th>Sr#</th>
                                    <th>Name</th>
                                    <th>Engaged (Total)</th>
									<th>Applicants (Created)</th>
									<th>Applicants (Updated)</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
							<tfoot>
								<tr style="background-color:#d3cfcf">
									<th></th>
									<th>Total</th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection