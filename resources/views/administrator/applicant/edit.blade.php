@extends('layouts.app')

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="{{ route('applicants.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Applicants</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
						<a href="#" class="breadcrumb-item">Applicants</a>
						<span class="breadcrumb-item">Current</span>
                        <span class="breadcrumb-item active">Update</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Edit An Applicant</h5>
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
                                    {{ Form::open(array('route'=>['applicants.update.applicant',$applicant->id],'method'=>'PATCH','files'=>true,'id'=>'update_applicant_with_notes')) }}
                                    <div class="form-group">
                                        {{ Form::label('job_title','Job Title',array('class'=>'col-form-label')) }}
                                        <select name="applicant_job_title" id="applicant_job_title_id" class="form-control form-control-select2">
                                         <!--   <optgroup label="NURSES">
                                                <option value="rgn" @if($applicant->applicant_job_title === 'rgn') selected='selected' @endif>RGN</option>
                                                <option value="rmn" @if($applicant->applicant_job_title === 'rmn') selected='selected' @endif>RMN</option>
                                                <option value="rnld" @if($applicant->applicant_job_title === 'rnld') selected='selected' @endif>RNLD</option>
                                                <option value="nurse deputy manager" @if($applicant->applicant_job_title === 'nurse deputy manager') selected='selected' @endif>NURSE DEPUTY MANAGER</option>
                                                <option value="nurse manager" @if($applicant->applicant_job_title === 'nurse manager') selected='selected' @endif>NURSE MANAGER</option>
                                                <option value="senior nurse" @if($applicant->applicant_job_title === 'senior nurse') selected='selected' @endif>SENIOR NURSE</option>
                                                <option value="rgn/rmn" @if($applicant->applicant_job_title === 'rgn/rmn') selected='selected' @endif>RGN/RMN</option>
                                                <option value="rmn/rnld" @if($applicant->applicant_job_title === 'rmn/rnld') selected='selected' @endif>RMN/RNLD</option>
                                                <option value="rgn/rmn/rnld" @if($applicant->applicant_job_title === 'rgn/rmn/rnld') selected='selected' @endif>RGN/RMN/RNLD</option>
                                                <option value="clinical lead" @if($applicant->applicant_job_title === 'clinical lead') selected='selected' @endif>CLINICAL LEAD</option>
                                                <option value="rcn" @if($applicant->applicant_job_title === 'rcn') selected='selected' @endif>RCN</option>
                                                <option value="peripatetic nurse" @if($applicant->applicant_job_title === 'peripatetic nurse') selected='selected' @endif>PERIPATETIC NURSE</option>
                                                <option value="unit manager" @if($applicant->applicant_job_title === 'unit manager') selected='selected' @endif>UNIT MANAGER</option>
                                                <option value="nurse specialist" @if($applicant->applicant_job_title === 'nurse specialist') selected='selected' @endif>NURSE SPECIALIST</option>
                                            </optgroup>
                                            <optgroup label="NON NURSES">
                                                <option value="care assistant" @if($applicant->applicant_job_title === 'care assistant') selected='selected' @endif>CARE ASSISTANT</option>
                                                <option value="senior care assistant" @if($applicant->applicant_job_title === 'senior care assistant') selected='selected' @endif>SENIOR CARE ASSISTANT</option>
                                                <option value="team lead" @if($applicant->applicant_job_title === 'team lead') selected='selected' @endif>TEAM LEAD</option>
                                                <option value="deputy manager" @if($applicant->applicant_job_title === 'deputy manager') selected='selected' @endif>DEPUTY MANAGER</option>
                                                <option value="registered manager" @if($applicant->applicant_job_title === 'registered manager') selected='selected' @endif>REGISTERED MANAGER</option>
                                                <option value="support worker" @if($applicant->applicant_job_title === 'support worker') selected='selected' @endif>SUPPORT WORKER</option>
                                                <option value="senior support worker" @if($applicant->applicant_job_title === 'senior support worker') selected='selected' @endif>SENIOR SUPPORT WORKER</option>
												 <option value='support worker / care assistant'  @if($applicant->applicant_job_title === 'support worker / care assistant') selected='selected' @endif>SUPPORT WORKER / CARE ASSISTANT</option>
                                                <option value='senior support worker / senior care assistant'  @if($applicant->applicant_job_title === 'senior support worker / senior care assistant') selected='selected' @endif>SENIOR SUPPORT WORKER / SENIOR CARE ASSISTANT</option>
                                                <option value="activity coordinator" @if($applicant->applicant_job_title === 'activity coordinator') selected='selected' @endif>ACTIVITY COORDINATOR</option> -->
                                                <option value="nonnurse specialist" @if($applicant->applicant_job_title === 'nonnurse specialist') selected='selected' @endif>SPECIALIST</option>
                                          <!--  </optgroup>
											   <optgroup label="Chef">
                                                <option value="chef" @if($applicant->applicant_job_title === 'chef') selected='selected' @endif>Chef</option>
                                                <option value="head chef" @if($applicant->applicant_job_title === 'head chef') selected='selected' @endif>Head Chef</option>
                                                <option value="chef de partie" @if($applicant->applicant_job_title === 'chef de partie') selected='selected' @endif>Chef De Partie</option>
                                                <option value="sous chef" @if($applicant->applicant_job_title === 'sous chef') selected='selected' @endif>Sous Chef</option>
                                                <option value="commis chef" @if($applicant->applicant_job_title === 'commis chef') selected='selected' @endif>commis chef</option>
                                            </optgroup>
											 <optgroup label="Nursery">
                                                <option value='nursery manager' @if($applicant->applicant_job_title === 'nursery manager') selected='selected' @endif>Nursery Manager</option>
                                                <option value='nursery deputy manager' @if($applicant->applicant_job_title === 'nursery deputy manager') selected='selected' @endif>Nursery Deputy Manager</option>
                                                <option value='nursery practitioner' @if($applicant->applicant_job_title === 'nursery practitioner') selected='selected' @endif>Nursery Practitioner</option>
                                                <option value='room leader' @if($applicant->applicant_job_title === 'room leader') selected='selected' @endif>Room Leader</option>
												  <option value='baby room manager' @if($applicant->applicant_job_title === 'baby room manager') selected='selected' @endif>Baby Room Manager</option>
                                                <option value='teacher' @if($applicant->applicant_job_title === 'teacher') selected='selected' @endif>Teacher</option>
                                                <option value='room attendant / nursery assistant' @if($applicant->applicant_job_title === 'room attendant / nursery assistant') selected='selected' @endif>Room Attendant / Nursery Assistant</option> 
                                            </optgroup> -->
                                        </select>
                                    </div>
                                    <?php if($applicant->applicant_job_title =='nonnurse specialist' || $applicant->applicant_job_title =='nurse specialist'){?>
                                    <div class="form-group" id="app_specialist_edit">
                                    <label>Select Job Profession</label>
                                    <select name='job_title_prof' class='form-control form-control-select2' id='job_title_prof_id' required>
                                        <option value=''>Select Profession</option>
                                        
                                         @foreach($spec_all_jobs_data as $item) 
                                        <option value="{{$item['id']}}" @if($sec_job_data && $sec_job_data->id == $item['id']) selected='selected' @endif()> {{ $item['specialist_prof'] }}</option>
                                        @endforeach()
                                   </select>
                                        
                                    </div>
                                     <!-- <div class="form-group" id="specialist_edit_new">

                                    </div> -->
                                    <?php }?>
                                    <div class="form-group" id="app_specialist_edit_special_only">
                                   </div>
                                    <input type="hidden" name="applicant_id" id="applicant_id" value="{{$applicant->id}}">


                                    <div class="form-group">
                                   @if(\Illuminate\Support\Facades\Auth::user()->is_admin == 1 ||
										\Illuminate\Support\Facades\Auth::id() == 101 ||
										\Illuminate\Support\Facades\Auth::id() == 150 ||
										\Illuminate\Support\Facades\Auth::id() == 66 || 
										\Illuminate\Support\Facades\Auth::id() == 83 || 
										\Illuminate\Support\Facades\Auth::id() == 164 || 
										\Illuminate\Support\Facades\Auth::id() == 114)
                                        {{ Form::label('name','Name') }}
                                        {{ Form::text('applicant_name',$applicant->applicant_name,array('class'=>'form-control','id'=>'name')) }}
										 @else
										    {{ Form::label('name','Name') }}
                                        {{ Form::text('applicant_name',$applicant->applicant_name,array('class'=>'form-control disabled','disabled','id'=>'name')) }}
										
										    @endif
                                    </div>
									<div class="row">
										<div class="col-md-6 col-lg-6 col-sm-12 form-group">
											{{ Form::label('email_address','Email Address',array('class'=>'col-form-label')) }}
											{{ Form::email('applicant_email',$applicant->applicant_email,array('id'=>'email_address_id','class'=>'form-control')) }}
										</div>
										<div class="col-md-6 col-lg-6 col-sm-12 form-group">
										{{ Form::label('source', 'Source') }}
										{!! Form::select('applicant_source', $applicant_source ,$selectedID, array('id'=>'source','class'=>'form-control','required',
											'placeholder' => 'SELECT APPLICANT SOURCE')) !!}
										</div>
									</div>
                                    <div class="form-group">
										{{ Form::hidden('notes_details', 'notes_details', array('id' => 'notes_details')) }}
										{{ Form::hidden('notes_type', 'asdfasfasdfasdf', array('id' => 'notes_type')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('postcode','Postcode (Please avoid using extra symbols)') }}
                                        {{ Form::text('applicant_postcode',$applicant->applicant_postcode,array('id'=>'postcode_id','class'=>'form-control')) }}
                                    </div>
									
                                   <div class="row">
                                    @if(\Illuminate\Support\Facades\Auth::user()->is_admin == 1 || 
									\Illuminate\Support\Facades\Auth::id() == 66 || 
									   \Illuminate\Support\Facades\Auth::id() == 150 ||
									\Illuminate\Support\Facades\Auth::id() == 164 ||
									\Illuminate\Support\Facades\Auth::id() == 101)
                                    <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                                        {{ Form::label('phnumber','Phone Number') }}
                                        {{ Form::text('applicant_phone',$applicant->applicant_phone,array('id'=>'phone_number_id','class'=>'form-control')) }}
                                    </div>
									 <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                                            {{ Form::label('mobile','Landline Number') }}
                                            {{ Form::text('applicant_homePhone',$applicant->applicant_homePhone,array('id'=>'mobile_number_id','class'=>'form-control')) }}
                                        </div>
                                       
                                    @else

                                    <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                                        {{ Form::label('phnumber','Phone Number') }}
                                        {{ Form::text('applicant_phone',$applicant->applicant_phone,array('id'=>'phone_number_id','class'=>'form-control disabled','disabled')) }}
                                    </div>
									@if(\Illuminate\Support\Facades\Auth::user()->is_admin == 1 || 
										\Illuminate\Support\Facades\Auth::id() == 66 || 
									   \Illuminate\Support\Facades\Auth::id() == 150 ||
									\Illuminate\Support\Facades\Auth::id() == 164 ||
										\Illuminate\Support\Facades\Auth::id() == 101)
                                            <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                                                {{ Form::label('mobile','Landline Number') }}
                                                {{ Form::text('applicant_homePhone',$applicant->applicant_homePhone,array('id'=>'mobile_number_id','class'=>'form-control' )) }}
                                            </div>

                                        @else
                                            <div class="col-md-6 col-lg-6 col-sm-12 form-group">
                                                {{ Form::label('mobile','Landline Number') }}
                                                {{ Form::text('applicant_homePhone',$applicant->applicant_homePhone,array('id'=>'mobile_number_id','class'=>'form-control disabled','disabled' )) }}
                                            </div>
                                        @endif
                                      
                                    @endif
									</div>
									<div class="row">
										<div class="col-md-6 col-lg-6 col-sm-12 form-group">
											<label for="department">Department</label>
											<select id="department" name="department" class="form-control" required>
												<option value="">Select Department</option>
												<option value="claim-handler" {{ $applicant->department == 'claim-handler' ? 'selected' : '' }}>Claim Handler</option>
												<option value="commercial-corporate-solicitor" {{ $applicant->department == 'commercial-corporate-solicitor' ? 'selected' : '' }}>Commercial & Corporate Solicitor</option>
												<option value="conveyancer" {{ $applicant->department == 'conveyancer' ? 'selected' : '' }}>Conveyancer</option>
												<option value="construction-solicitor" {{ $applicant->department == 'construction-solicitor' ? 'selected' : '' }}>Construction Solicitor</option>
												<option value="criminal-solicitor" {{ $applicant->department == 'criminal-solicitor' ? 'selected' : '' }}>Criminal Solicitor</option>
												<option value="dispute-resolution" {{ $applicant->department == 'dispute-resolution' ? 'selected' : '' }}>Dispute Resolution</option>
												<option value="employment" {{ $applicant->department == 'employment' ? 'selected' : '' }}>Employment</option>
												<option value="family-solicitor" {{ $applicant->department == 'family-solicitor' ? 'selected' : '' }}>Family Solicitor</option>
												<option value="immigration" {{ $applicant->department == 'immigration' ? 'selected' : '' }}>Immigration</option>
												<option value="inquest-solicitor" {{ $applicant->department == 'inquest-solicitor' ? 'selected' : '' }}>Inquest Solicitor</option>
												<option value="litigation" {{ $applicant->department == 'litigation' ? 'selected' : '' }}>Litigation</option>
												<option value="medical-negligence" {{ $applicant->department == 'medical-negligence' ? 'selected' : '' }}>Medical Negligence</option>
												<option value="paralegal" {{ $applicant->department == 'paralegal' ? 'selected' : '' }}>Paralegal</option>
												<option value="personal-injury-solicitor" {{ $applicant->department == 'personal-injury-solicitor' ? 'selected' : '' }}>Personal Injury Solicitor</option>
												<option value="private-client" {{ $applicant->department == 'private-client' ? 'selected' : '' }}>Private Client</option>
												<option value="property-solicitor" {{ $applicant->department == 'property-solicitor' ? 'selected' : '' }}>Property Solicitor</option>
												<option value="regulatory-solicitor" {{ $applicant->department == 'regulatory-solicitor' ? 'selected' : '' }}>Regulatory Solicitor</option>
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
                                        {{ Form::label('applicant_experience', 'Add Experience (Optional):') }}
                                        {{ Form::textarea('applicant_experience',$applicant->applicant_experience,
                                        array('class'=>'form-control form-input-styled','rows' => '3','cols' => '20')) }}
                                    </div>
                                    <div class="form-group">
                                        {{ Form::hidden('old_image',$applicant->applicant_cv) }}
                                        {{ Form::label('attachment', 'Attach CV:') }}
                                        {{ $applicant->applicant_cv }}
                                        {{ Form::file('applicant_cv',array('class'=>'form-input-styled')) }}
                                    </div>
									
{{--                                    <div class="form-group">--}}
{{--                                        {{ Form::label('applicant_notes', 'Add Notes:') }}--}}
{{--                                        {{ Form::textarea('applicant_notes',$applicant->applicant_notes,--}}
{{--                                        array('class'=>'form-control form-input-styled','rows' => '7','cols' => '20')) }}--}}
{{--                                    </div>--}}

                                    <div class="text-right">
                                        <!-- {{ Form::button('Save <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }} -->
                                        <a href="#" class="reject_history icon-paperplane ml-2 btn bg-teal legitRipple" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#check_notes"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#check_notes">Save</a>
                                        <!-- <button type="submit" class="btn bg-teal legitRipple">Save <i class="icon-paperplane ml-2"></i></button> -->
                                    </div>
                                    {{ Form::close() }}
                                    
                                    <div id="check_notes" class="modal fade" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                <h5 class="modal-title">Notes</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                
                                                    <div class="modal-body">
                                                        <div id="sent_cv_alert' . $applicant->id . '"></div>
                                                        <div class="form-group row">
                                                            <label class="col-form-label col-sm-3">Details</label>
                                                            <div class="col-sm-9">
                                                                <input type="hidden" name="applicant_hidden_id" value="">
                                                                <textarea name="details" id="sent_cv_details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-form-label col-sm-3">Choose type:</label>
                                                            <div class="col-sm-9">
                                                                <select name="reject_reason" class="form-control crm_select_reason" id="reason">
                                                                    <option value="0" >Select Reason</option>
                                                                    <option value="1">Casual Notes</option>
                                                                    <option value="2">Block Applicant Notes</option>
                                                                    <option value="3">Temporary Not Interested Applicants Notes</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <div class="modal-footer">
                                    
                                                    <button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>
                                        
                                                    <button type="submit" value="cv_sent_save" class="btn bg-teal legitRipple update_applicant">Update</button>

                                                </div>
                                            </div>
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
        <!-- /content area -->

@endsection()
@section('script')

<script>
    $(document).ready(function(){
		// create new note
		$(document).on('click', '.update_applicant', function (event) {
			event.preventDefault();
			$('#notes_details').val($('#sent_cv_details').val());
			var notes_details=$('#notes_details').val();
		   $('#notes_type').val($('#reason option:selected').val());
			var notes_type=$('#notes_type').val();
			$("#update_applicant_with_notes").submit()

		});
	});
	
	$(document).ready(function(){
	   $('.reject_history').on('click',function(){
		   var source =$("select#source option").filter(":selected").text();
		   if(source=='SELECT APPLICANT SOURCE')
		   {
			alert('Please Select Applicant Source');
			return false;
		   }

		   return true;

	   }); 
	});
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
			"regulatory-solicitor": ["Head of Department", "Senior", "Junior"],
			"criminal-solicitor": ["Duty Crime Solicitor"],
			"immigration": ["Head of Department", "Senior", "Junior"],
			"construction-solicitor": ["Head of Department", "Senior", "Junior"]
		};

		// Function to handle department change
		function updateSubDepartment(selectedDept) {
			const $subDept = $('#subDepartment');
			const dbSubDept = '{{ $applicant->sub_department ?? "" }}'; // Get stored value

			// Clear and disable sub-department dropdown
			$subDept.empty().prop('disabled', true);

			if (selectedDept) {
				const subDepts = departmentMap[selectedDept];

				if (subDepts && subDepts.length > 0) {
					$subDept.append('<option value="">Select Sub Department</option>');

					$.each(subDepts, function(index, value) {
						const capitalizedValue = value.charAt(0).toUpperCase() + value.slice(1);
						// Check if this value matches the stored sub-department
						const isSelected = (value === dbSubDept) ? 'selected="selected"' : '';
						$subDept.append(`<option value="${value}" ${isSelected}>${capitalizedValue}</option>`);
					});

					$subDept.prop('disabled', false);
				} else {
					$subDept.append('<option value="">No Sub Departments</option>');
				}
			} else {
				$subDept.append('<option value="">Select Department First</option>');
			}
		}

		// Department dropdown change event
		$('#department').change(function() {
			updateSubDepartment($(this).val());
		});

		// Trigger on page load if department is already selected
		const initialDept = $('#department').val();
		if (initialDept) {
			updateSubDepartment(initialDept);
		}
	});
</script>
@endsection