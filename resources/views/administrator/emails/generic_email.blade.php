@extends('layouts.app')
@section('style')
	<style>
		div.editable {
			/* width: 300px; */
			height: 350px;
			overflow: scroll;
			overflow-x:hidden;
			border: 2px solid #ccc;
			padding: 40px 30px 40px 30px;
			background-color: rgb(246, 246, 246);
			border-radius: 5px;
		}

		strong {
		  font-weight: bold;
		}
		 .table-success {
            background-color: #d4edda; /* Light green background */
            color: #155724; /* Dark green text */
        }
        .table-success::after {
            content: "Success!";
            display: block;
            color: #155724;
            font-weight: bold;
        }
    </style>
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
                        <a href="#"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Email Templates</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Administration</a>
						<span class="breadcrumb-item">Current</span>
                        <span class="breadcrumb-item active">Email Templates</span>
                    </div>
                </div>
                
            </div>
            
        </div>

        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <div class="row">
                <div class="col-md-3">

                </div>
                <div class="col-md-8 offset-md-2">
					<!-- Tab navigation -->
                    <ul class="nav nav-tabs mb-0" id="emailTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="generic-email-tab" data-toggle="tab" href="#generic-email" role="tab" aria-controls="generic-email" aria-selected="true">Generic Email</a>
                        </li>
						 @if(auth()->user()->id == '1' || auth()->user()->id == '101')
                        <li class="nav-item">
                            <a class="nav-link" id="random-email-tab" data-toggle="tab" href="#random-email" role="tab" aria-controls="random-email" aria-selected="false">Random Email</a>
                        </li>
						@endif
                    </ul>
                    <!-- Tab content -->
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="generic-email" role="tabpanel" aria-labelledby="generic-email-tab">
                            <div class="card" id="generic_email">
                                <div class="card-header">
                                    <div class="row">
                                        <div class="col-md-10 offset-md-1">
                                            <div class="header-elements-inline">
                                                <h5 class="card-title">Generic Email <span class="badge badge-danger">Unsent: {{ $genericUnsentCount }}</span></h5>
                                               
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-10 offset-md-1">
                                            <form id="form_send_message" action="#">
                                                @csrf
                                                <div class="form-group border border-light rounded">
                                                    <input type="text" name="applicant_email" required id="applicant_email" class="form-control" placeholder="Recipient"/>
                                                </div>
                                                <div class="form-group border border-light rounded">
                                                    <input type="text" name="email_title" required id="email_title" class="form-control" placeholder="Subject"/>
                                                </div>
                                                <div class="form-group">
                                                    <label>Email Body:</label>
                                                </div>
                                                <div class="form-group border border-light rounded">
                                                    <div class="editable" contenteditable="true"><?php echo $data; ?></div>
                                                </div>
                                                <div class="text-right">
													 <a href="{{ route('applicants.index') }}" class="btn bg-slate-800 legitRipple">
                                                    <i class="icon-cross"></i> Cancel
                                                </a> 
                                                    <a href="javascript:;" class="btn bg-teal legitRipple" id="send_app_email"><i class="icon-paperplane"></i> Send</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="random-email" role="tabpanel" aria-labelledby="random-email-tab">
                            <div class="card" id="random_email">
                                <div class="card-header">
                                    <div class="row">
                                        <div class="col-md-10 offset-md-1">
                                            <div class="header-elements-inline">
                                                <h5 class="card-title">Random Email <span class="badge badge-danger mr-1">Unsent: {{ $randomUnsentCount }}</span>
			<a href="#" data-toggle="modal" data-target="#failedEmailsModal">
    				<span class="badge badge-warning">Failed: {{ count($randomUnsentFailed) }}</span>
			</a>
												</h5>
                                               <a href="#" data-controls-modal="#import_unit_csv" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#import_unit_csv" class="btn bg-slate-800 legitRipple mr-1">
                                                    <i class="icon-cloud-download"></i>
                                                    &nbsp;Import Email</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-10 offset-md-1">
                                            <form id="form_send_message" action="#">
                                                @csrf
                                                <div class="form-group border border-light rounded">
                                                    <textarea name="applicant_email" required id="applicant_email_2" class="form-control" placeholder="Recipient"></textarea>
                                                </div>
                                                <div class="form-group border border-light rounded">
                                                    <input type="text" name="email_title" required id="email_title_2" class="form-control" placeholder="Subject"/>
                                                </div>
                                                <div class="form-group">
                                                    <label>Email Body:</label>
                                                </div>
                                                 <div class="form-group border border-light rounded">
        <textarea name="email_body" required id="randomEmailBody" class="form-control"><?php echo $randomData; ?></textarea>
    </div>
                                                <div class="text-right">
													 <a href="{{ route('applicants.index') }}" class="btn bg-slate-800 legitRipple">
														<i class="icon-cross"></i> Cancel
													</a> 
                                                    <a href="javascript:;" class="btn bg-teal legitRipple" id="send_app_email_random"><i class="icon-paperplane"></i> Send</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /form centered -->
        </div>
		<!-- Modal -->
<div class="modal fade" id="failedEmailsModal" tabindex="-1" role="dialog" aria-labelledby="failedEmailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="failedEmailsModalLabel">Failed Emails</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                <table class="table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Email Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($randomUnsentFailed as $email)
                                <tr data-email-id="{{ $email->id }}">
                                    <td>{{ $loop->iteration }}</td> 
                                    <td>
                                        <input type="text" class="email-input w-100 border-0" value="{{ $email->sent_to }}" readonly>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-link btn-sm edit-btn" onclick="toggleEdit('{{ $email->id }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-link btn-sm save-btn d-none" onclick="saveEmail('{{ $email->id }}')">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button type="button" class="btn btn-link text-danger btn-sm" onclick="deleteEmail('{{ $email->id }}')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <i class="fas fa-check d-none success-icon" style="color: green;">&nbsp;Saved</i>
                                    </td>
                                </tr>
                            @endforeach
                            
                            </tbody>
                        </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
		<div id="import_unit_csv" class="modal fade">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Emails CSV</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
						<p>If you want to download the formatted file. <a style="text-decoration:underline;" href="{{ asset('assets/csv/generic_email_format.csv') }}">Click here</a></p>

                        <form id="upload-csv-form" enctype="multipart/form-data">
                            @csrf()
                            <div class="form-group row">
                                <div class="col-lg-12">
                                    <input type="file" name="email_csv" class="file-input-advanced" data-fouc>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endsection
        @section('script')
       <!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

        <script>
           $(document).ready(function() {
				$('#randomEmailBody').summernote({
					height: 200, // Set the height of the editor
					placeholder: 'Write your email body here...',
					tabsize: 2
				});
				$(document).on("click", "#send_app_email", function(e){
					e.preventDefault();
					var email_body = $('.editable').html();
					// console.log(email_body);return false;
					var app_email = $("#applicant_email").val();
					var email_title = $("#email_title").val();
					if(app_email == '' || email_title == '' || email_body =='')
					{
						toastr.error('All fields are required...');
						return false;
					}

					$.ajax({
							url: "{{ route('send_app_email') }}",
							type: "post",
							headers: {
										'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
									},
							dataType: "json",
							data: {'email_body':email_body, 'app_email':app_email, 'email_title':email_title },
							success: function (response) {
								$("#applicant_email").val('');
								$("#email_title").val('');
								toastr.success('Email sent successfuly!');
							},
						error: function (response) {
							console.log('error '+response);
							toastr.error('Something went wrong please try again...');
						}
					});
				});
			   
			   $(document).on("click", "#send_app_email_random", function(e){
					e.preventDefault();
					var email_body = $('#randomEmailBody').summernote('code');
					var app_email = $("#applicant_email_2").val();
					var email_title = $("#email_title_2").val();
					if(app_email == '' || email_title == '' || email_body =='')
					{
						toastr.error('All fields are required...');
						return false;
					}

					$.ajax({
						url: "{{ route('send_app_random_email') }}",
						type: "post",
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
						dataType: "json",
						data: {'email_body':email_body, 'app_email':app_email, 'email_title':email_title },
						success: function (response) {
							$("#applicant_email_2").val('');
							$("#email_title").val('');
							toastr.success('Email sent successfuly!');
						},
						error: function(xhr) {
							if (xhr.status === 422) {
								toastr.error(xhr.responseJSON.message);
							} else if (xhr.status === 500) {
								toastr.error('Server error: ' + xhr.responseJSON.error);
							} else {
								toastr.error('Something went wrong, please try again...');
							}
						}
					});
				});
			});
			
            $(document).ready(function() {
                function formatInput(inputVal) {
                    // Replace spaces and multiple commas with a single comma
                    return inputVal.trim().replace(/[\s,]+/g, ',');
                }

                $('#applicant_email_2').on('input', function() {
                    var inputVal = $(this).val();
                    var newVal = formatInput(inputVal);
                    $(this).val(newVal);
                });

                // Handle the paste event to format pasted text
                $('#applicant_email_2').on('paste', function(e) {
                    e.preventDefault();
                    var paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                    var newVal = formatInput(paste);
                    $(this).val(newVal);
                });
            });
			
			 $(document).ready(function() {
                $('#upload-csv-form').on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);

                    $.ajax({
                        url: '{{ route('emailCsv') }}',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(data) {
                            if(data.success) {
                                $('#applicant_email_2').val(data.emailsString);
                                $('#import_unit_csv').modal('hide'); // Hide the modal
                            } else {
                                alert('Failed to upload CSV file');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                });
            });
			
			var currentEditRow = null; // Track the currently edited row

    function toggleEdit(id) {
        var newRow = $('tr[data-email-id="' + id + '"]');

        // If there is a currently edited row and it's not the new row
        if (currentEditRow && currentEditRow[0] !== newRow[0]) {
            saveEmail(currentEditRow.data('email-id'));
        }

        // Switch the current edit row to view mode if it exists
        if (currentEditRow) {
            var input = currentEditRow.find('.email-input');
            var editBtn = currentEditRow.find('.edit-btn');
            var saveBtn = currentEditRow.find('.save-btn');
            var successIcon = currentEditRow.find('.success-icon');

            input.attr('readonly', 'readonly');
            editBtn.removeClass('d-none');
            saveBtn.addClass('d-none');
            successIcon.addClass('d-none');
        }

        // Switch the new row to edit mode
        var input = newRow.find('.email-input');
        var editBtn = newRow.find('.edit-btn');
        var saveBtn = newRow.find('.save-btn');
        var successIcon = newRow.find('.success-icon');

        if (input.is('[readonly]')) {
            input.removeAttr('readonly').focus(); // Focus the input field
            editBtn.addClass('d-none');
            saveBtn.removeClass('d-none');
            successIcon.addClass('d-none'); // Hide success icon when editing
        } else {
            input.attr('readonly', 'readonly');
            editBtn.removeClass('d-none');
            saveBtn.addClass('d-none');
        }

        // Update the current edit row
        currentEditRow = newRow;
    }

    function saveEmail(id) {
        var row = $('tr[data-email-id="' + id + '"]');
        var input = row.find('.email-input').val();
        var successIcon = row.find('.success-icon');

        $.ajax({
            url: '{{ route("sent_emails.update") }}', // Replace with your edit URL
            method: 'POST',
            data: {
                id: id,
                email: input,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Handle success response
                console.log('Email with ID ' + id + ' edited successfully:', response);

                // Switch back to view mode
                row.find('.email-input').attr('readonly', 'readonly');
                row.find('.edit-btn').removeClass('d-none');
                row.find('.save-btn').addClass('d-none');

                // Show success icon
                successIcon.removeClass('d-none');

                // Optionally, hide the success icon after a short delay
                setTimeout(function() {
                    successIcon.addClass('d-none');

                    // Remove the row from the table
                    row.remove();
                }, 3000);
            },
            error: function(xhr) {
                // Handle error response
                console.error('Error editing email with ID ' + id + ':', xhr);
            }
        });
    }

    function deleteEmail(id) {
        $.ajax({
            url: '{{ route("sent_emails.delete") }}', // Replace with your delete URL
            method: 'DELETE',
            data: {
                id: id,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Handle success response
                console.log('Email with ID ' + id + ' deleted successfully:', response);
                
                // Remove the row from the table
                $('tr[data-email-id="' + id + '"]').remove();

                // Clear currentEditRow if the deleted row was the currently edited one
                if (currentEditRow && currentEditRow.data('email-id') === id) {
                    currentEditRow = null;
                }
            },
            error: function(xhr) {
                // Handle error response
                console.error('Error deleting email with ID ' + id + ':', xhr);
            }
        });
    }

</script>
@endsection
