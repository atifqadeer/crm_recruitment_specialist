@extends('layouts.app')

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="#"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Applicant</span> - Add
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Add</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->

        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->
            @if(session()->has('applicant_add_error'))
                <div class="alert alert-danger">
                    {{ session()->get('applicant_add_error') }}
                </div>
            @endif
        <!-- For Validation Errors  -->
            <!-- ============================================================== -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        <button type="button" class="btn btn-danger"
                                data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#myModal"
                                style="background-color: #007bff;border: none;">Replace With Note</button>
                        <!-- Modal -->
                        <div class="modal fade" tabindex="-1" id="myModal" role="dialog">
                            <div class="modal-dialog">
                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Write A Note For Applicant</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Note Title</label>
                                            <input type="text" id="note_title_id" class="form-control" placeholder="NOTE TITLE">
                                        </div>
                                        <div class="form-group">
                                            <label>Write a Note</label>
                                            <textarea  id="duplicate_note_for_applicants_id" cols="30"
                                                       rows="5" class="form-control"
                                                       placeholder="WRITE A NOTE FOR DUPLICATE APPLICANTS HERE..."></textarea>
                                        </div>
                                        <input type="button" id="duplicate_note_id" class="btn btn-primary btn-block" value="Save"
                                               style="background-color: #007bff;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal End -->
                    </ul>
                </div>
        @endif
        <!-- End Validation Errors  -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Add an Applicant</h5>
                                        <a href="{{ route('applicants.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                   <form action="{{ route('applicants.store') }}" method="POST" enctype="multipart/form-data">
									   @csrf
										<div class="form-group">
											<label for="job_title" class="col-form-label">Job Title<span class="text-danger">*</span></label>
											<select name="applicant_job_title" class="form-control form-control-select2" id="app_job_title_spec" required>
												<option value="">Select Job Title</option>
													<option value="nonnurse specialist" {{ old('applicant_job_title') == 'nonnurse specialist' ? 'selected' : '' }}>Specialist</option>
											</select>
										</div>
										<div class="form-group" id="app_specialist"></div>

										<div class="form-group">
											<label for="name">Name<span class="text-danger">*</span></label>
											<input type="text" name="applicant_name" id="name" value="{{ old('applicant_name') }}" class="form-control" placeholder="ENTER APPLICANT NAME" required>
										</div>

										<div class="row">
											<div class="col-md-6 col-lg-6 col-sm-12 form-group">
												<label for="email_address_id" class="col-form-label">Email Address</label>
												<input type="email" name="applicant_email" id="email_address_id" value="{{ old('applicant_email') }}" class="form-control" placeholder="ENTER APPLICANT EMAIL ADDRESS">
											</div>
											<div class="col-md-6 col-lg-6 col-sm-12 form-group">
												<label for="source">Source<span class="text-danger">*</span></label>
												<select name="applicant_source" id="source" class="form-control" required>
													<option value="">SELECT APPLICANT SOURCE</option>
													@foreach($applicant_source as $key => $value)
														<option value="{{ $key }}" {{ old('applicant_source') == $key ? 'selected' : '' }}>{{ $value }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group">
											<label for="postcode_id">Postcode<span class="text-danger">*</span>&nbsp;(Please avoid using extra symbols)</label>
											<input type="text" name="applicant_postcode" id="postcode_id" class="form-control" placeholder="ENTER APPLICANT POSTCODE" value="{{ old('applicant_postcode') }}" required>
										</div>
									   

										<div class="row">
											<div class="col-md-6 col-lg-6 col-sm-12 form-group">
												<label for="phone_number_id">Mobile Number<span class="text-danger">*</span></label>
												<input type="text" name="applicant_phone" id="phone_number_id" class="form-control" placeholder="ENTER APPLICANT PHONE NUMBER" value="{{ old('applicant_phone') }}" required>
											</div>
											<div class="col-md-6 col-lg-6 col-sm-12 form-group">
												<label for="home_number_id">Landline Number</label>
												<input type="text" name="applicant_homePhone" id="home_number_id" class="form-control" placeholder="ENTER APPLICANT MOBILE NUMBER" value="{{ old('applicant_homePhone') }}">
											</div>
										</div>
									   <div class="row">
										   <div class="col-md-6 col-lg-6 col-sm-12 form-group">
											   <label for="department">Department<span class="text-danger">*</span></label>
											   <select id="department" name="department" class="form-control" required>
												<option value="">Select Department</option>
												<option value="claim-handler" {{ old('department') == 'claim-handler' ? 'selected' : '' }}>Claim Handler</option>
												<option value="commercial-corporate-solicitor" {{ old('department') == 'commercial-corporate-solicitor' ? 'selected' : '' }}>Commercial & Corporate Solicitor</option>
												<option value="conveyancer" {{ old('department') == 'conveyancer' ? 'selected' : '' }}>Conveyancer</option>
												<option value="construction-solicitor" {{ old('department') == 'construction-solicitor' ? 'selected' : '' }}>Construction Solicitor</option>
												<option value="criminal-solicitor" {{ old('department') == 'criminal-solicitor' ? 'selected' : '' }}>Criminal Solicitor</option>
												<option value="dispute-resolution" {{ old('department') == 'dispute-resolution' ? 'selected' : '' }}>Dispute Resolution</option>
												<option value="employment" {{ old('department') == 'employment' ? 'selected' : '' }}>Employment</option>
												<option value="family-solicitor" {{ old('department') == 'family-solicitor' ? 'selected' : '' }}>Family Solicitor</option>
												<option value="immigration" {{ old('department') == 'immigration' ? 'selected' : '' }}>Immigration</option>
												<option value="inquest-solicitor" {{ old('department') == 'inquest-solicitor' ? 'selected' : '' }}>Inquest Solicitor</option>
												<option value="litigation" {{ old('department') == 'litigation' ? 'selected' : '' }}>Litigation</option>
												<option value="medical-negligence" {{ old('department') == 'medical-negligence' ? 'selected' : '' }}>Medical Negligence</option>
												<option value="paralegal" {{ old('department') == 'paralegal' ? 'selected' : '' }}>Paralegal</option>
												<option value="personal-injury-solicitor" {{ old('department') == 'personal-injury-solicitor' ? 'selected' : '' }}>Personal Injury Solicitor</option>
												<option value="private-client" {{ old('department') == 'private-client' ? 'selected' : '' }}>Private Client</option>
												<option value="property-solicitor" {{ old('department') == 'property-solicitor' ? 'selected' : '' }}>Property Solicitor</option>
												<option value="regulatory-solicitor" {{ old('department') == 'regulatory-solicitor' ? 'selected' : '' }}>Regulatory Solicitor</option>
											</select>
										   </div>
										   <div class="col-md-6 col-lg-6 col-sm-12 form-group">
											   <label for="subDepartment">Sub Department</label>
											   <select id="subDepartment" name="subDepartment" disabled class="form-control">
												   <option value="">Select Sub Department</option>
											   </select>
										   </div>
									   </div>

										<div class="form-group">
											<label for="applicant_experience">Add Experience (Optional):</label>
											<textarea name="applicant_experience" class="form-control form-input-styled" rows="3" cols="20">
											{{ old('applicant_experience') }}
											</textarea>
										</div>

										<div class="form-group">
											<label for="applicant_cv">Attach CV:</label>
											<input type="file" name="applicant_cv" class="form-input-styled">
										</div>

										<div class="form-group">
											<label for="applicant_notes">Add Notes:</label>
											<textarea name="applicant_notes" class="form-control form-input-styled" rows="7" cols="20">
											{{ old('applicant_notes') }}
											</textarea>
										</div>

										<div class="text-right">
											<button type="submit" class="btn bg-teal legitRipple">Save <i class="icon-paperplane ml-2"></i></button>
										</div>
									</form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /form centered -->
        </div>
        <!-- /content area -->

@endsection()
@section('script')
	<script>
		$(document).ready(function() {
            // Department to Sub-Department mapping
            const departmentMap = {
                "property-solicitor": ["Head of Department", "Senior", "Junior", "Residential", "Commercial", "residential & commercial"],
                "family-solicitor": ["Head of Department", "Senior", "Junior"],
				"commercial-corporate-solicitor": ["Intellectual Property"],
                "conveyancer": ["fee earner", "residential", "commercial", "residential & commercial"],
                "private-client": ["fee earner", "Head of Department", "Senior", "Junior"],
                "litigation": ["property", "civil", "commercial", "civil & commercial"],
                "medical-negligence": [],
                "claim-handler": [],
                "employment": ["Head of Department", "Senior", "Junior"],
				"dispute-resolution": ["Head of Department", "Senior", "Junior"],
				"inquest-solicitor": ["Head of Department", "Senior", "Junior"],
				"personal-injury-solicitor": ["Head of Department", "Senior", "Junior"],
                "paralegal": ["Legal Secretory"],
                "criminal-solicitor": ["Duty Crime Solicitor"],
                "immigration": ["Head of Department", "Senior", "Junior"],
                "construction-solicitor": ["Head of Department", "Senior", "Junior"],
				"regulatory-solicitor": ["Head of Department", "Senior", "Junior"],
            };

            // Department dropdown change event
            $('#department').change(function() {
                const selectedDept = $(this).val();
                const $subDept = $('#subDepartment');
                
                // Clear and disable sub-department dropdown
                $subDept.empty().prop('disabled', true);
                
                if (selectedDept) {
                    const subDepts = departmentMap[selectedDept];
                    
                    if (subDepts && subDepts.length > 0) {
                        $subDept.append('<option value=""> Select Sub Department </option>');
                        
						$.each(subDepts, function(index, value) {
							const capitalizedValue = value.charAt(0).toUpperCase() + value.slice(1);
							$subDept.append(`<option value="${value}">${capitalizedValue}</option>`);
						});
                        
                        $subDept.prop('disabled', false);
                    } else {
                        $subDept.append('<option value="">No Sub Departments</option>');
                    }
                } else {
                    $subDept.append('<option value=""> Select Department</option>');
                }
            });
        });	
		
	</script>
		
@endsection()