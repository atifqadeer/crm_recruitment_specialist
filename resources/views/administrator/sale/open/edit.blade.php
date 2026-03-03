@extends('layouts.app')
@section('style')
<style>
    .attachment-preview {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    /* Style for image thumbnails */
    .attachment-thumbnail {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        object-fit: cover;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .attachment-thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Style for non-image files */
    .file-link {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: #333;
        font-weight: 500;
    }

    .file-link:hover .file-icon {
        color: #007bff;
    }

    .file-icon {
        font-size: 24px;
        color: #555;
    }

    .file-description {
        font-size: 14px;
        color: #555;
    }

    /* Styling for the Remove Attachment button */
    .remove-attachment-form {
        display: flex;
        align-items: center;
    }

    .btn-remove {
        border: none;
        background: transparent;
        color: #dc3545;
        font-size: 18px;
        padding: 0;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .btn-remove:hover {
        background-color: transparent;
        color: #dc3545;
    }

    /* Adding a professional border to the preview */
    .form-group {
        margin-bottom: 20px;
    }

    .attachment-preview {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
                        <a href="{{ route('applicants.index') }}"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Sale</span> - Update
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Update</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
			
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

            <!-- Centered forms -->
			<?php
				if (is_null(old('previous_url'))) {
                    $back_url = explode('/', url()->previous());
                    $back_url = $back_url[count($back_url) - 1];
                    $back_url = ($back_url == 'sales') ? 'sales.index' : $back_url;
                    $back_url = route($back_url);
                } else {
                    $full_url = explode('/', old('previous_url'));
                    $back_url = $full_url[count($full_url) - 1];
                    $back_url = url($back_url);
                }
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Edit a Sale</h5>
                                        <a href="{{ $back_url }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    {{ Form::open(['route' => ['sales.update', $sale->id], 'method' => 'PATCH', 'enctype' => 'multipart/form-data']) }}
                                    <div class="form-group">
                                        <label for="job_title_text" class="col-form-label">Job Title</label>
                                        <select name="job_title" id="select_job_title_id" class="form-control form-control-select2" required>
                                            <optgroup label="NURSES">
                                                <option value="rgn-nurse" @if($sale->job_title === 'rgn') selected='selected' @endif>RGN</option>
                                                <option value="rmn-nurse" @if($sale->job_title === 'rmn') selected='selected' @endif>RMN</option>
                                                <option value="rnld-nurse" @if($sale->job_title === 'rnld') selected='selected' @endif>RNLD</option>
												<option value="senior nurse-nurse" @if($sale->job_title === 'senior nurse') selected='selected' @endif>SENIOR NURSE</option>
                                                <option value="nurse deputy manager-nurse" @if($sale->job_title === 'nurse deputy manager') selected='selected' @endif>NURSE DEPUTY MANAGER</option>
                                                <option value="nurse manager-nurse" @if($sale->job_title === 'nurse manager') selected='selected' @endif>MANAGER</option>
                                                <option value="rgn/rmn-nurse" @if($sale->job_title === 'rgn/rmn') selected='selected' @endif>RGN/RMN</option>
                                                <option value="rmn/rnld-nurse" @if($sale->job_title === 'rmn/rnld') selected='selected' @endif>RMN/RNLD</option>
                                                <option value="rgn/rmn/rnld-nurse" @if($sale->job_title === 'rgn/rmn/rnld') selected='selected' @endif>RGN/RMN/RNLD</option>
												<option value="clinical lead-nurse" @if($sale->job_title === 'clinical lead') selected='selected' @endif>CLINICAL LEAD</option>
												<option value="rcn-nurse" @if($sale->job_title === 'rcn') selected='selected' @endif>RCN</option>
												<option value="peripatetic nurse-nurse" @if($sale->job_title === 'peripatetic nurse') selected='selected' @endif>PERIPATETIC NURSE</option>
                                                <option value="unit manager-nurse" @if($sale->job_title === 'unit manager') selected='selected' @endif>UNIT MANAGER</option>
												<option value="nurse specialist-nurse" @if($sale->job_title === 'nurse specialist') selected='selected' @endif>NURSE SPECIALIST</option>

                                            </optgroup>
                                            <optgroup label="NON NURSES">
                                                <option value="care assistant-nonnurse" @if($sale->job_title === 'care assistant') selected='selected' @endif>CARE ASSISTANT</option>
                                                <option value="senior care assistant-nonnurse" @if($sale->job_title === 'senior care assistant') selected='selected' @endif>SENIOR CARE ASSISTANT</option>
                                                <option value="team lead-nonnurse" @if($sale->job_title === 'team lead') selected='selected' @endif>TEAM LEAD</option>
                                                <option value="deputy manager-nonnurse" @if($sale->job_title === 'deputy manager') selected='selected' @endif>DEPUTY MANAGER</option>
                                                <option value="registered manager-nonnurse" @if($sale->job_title === 'registered manager') selected='selected' @endif>REGISTERED MANAGER</option>
													<option value="support worker-nonnurse" @if($sale->job_title === 'support worker') selected='selected' @endif>SUPPORT WORKER</option>
												<option value="senior support worker-nonnurse" @if($sale->job_title === 'senior support worker') selected='selected' @endif>SENIOR SUPPORT WORKER</option>
												<option value='support worker / care assistant-nonnurse'  @if($sale->job_title === 'support worker / care assistant') selected='selected' @endif>SUPPORT WORKER / CARE ASSISTANT</option>
                                                <option value='senior support worker / senior care assistant-nonnurse'  @if($sale->job_title === 'senior support worker / senior care assistant') selected='selected' @endif>SENIOR SUPPORT WORKER / SENIOR CARE ASSISTANT</option>
                                                <option value="activity coordinator-nonnurse" @if($sale->job_title === 'activity coordinator') selected='selected' @endif>ACTIVITY COORDINATOR</option>
												<option value="nonnurse specialist-nonnurse" @if($sale->job_title === 'nonnurse specialist') selected='selected' @endif>NON-NURSE SPECIALIST</option>
												
                                            </optgroup>
											
											<optgroup label="Chef">
                                                <option value="chef-chef" @if($sale->job_title === 'chef') selected='selected' @endif>Chef</option>
                                                <option value="head chef-chef" @if($sale->job_title === 'head chef') selected='selected' @endif>Head Chef</option>
                                                <option value="chef de partie-chef" @if($sale->job_title === 'chef de partie') selected='selected' @endif>Chef De Partie</option>
                                                <option value="sous chef-chef" @if($sale->job_title === 'sous chef') selected='selected' @endif>Sous Chef</option>
                                                <option value="commis chef-chef" @if($sale->job_title === 'commis chef') selected='selected' @endif>Commis Chef</option>
                                            </optgroup>
											 <optgroup label="Nursery">
                                                   <option value='nursery manager-nursery' @if($sale->job_title === 'nursery manager') selected='selected' @endif>Nursery Manager</option>
                                                <option value='nursery deputy manager-nursery' @if($sale->job_title === 'nursery deputy manager') selected='selected' @endif>Nursery Deputy Manager</option>
                                                <option value='nursery practitioner-nursery' @if($sale->job_title === 'nursery practitioner') selected='selected' @endif>Nursery Practitioner</option>
                                                <option value='room leader-nursery' @if($sale->job_title === 'room leader') selected='selected' @endif>Room Leader</option>
												  <option value='baby room manager-nursery' @if($sale->job_title === 'baby room manager') selected='selected' @endif>Baby Room Manager</option>
                                                <option value='teacher-nursery' @if($sale->job_title === 'teacher') selected='selected' @endif>Teacher</option>
                                                <option value='room attendant / nursery assistant-nursery' @if($sale->job_title === 'room attendant / nursery assistant') selected='selected' @endif>Room Attendant / Nursery Assistant</option>                    
                                            </optgroup>
                                        </select>
										<span> <small class = "text-danger"> {{ $errors->first('job_title') }} </small> </span>
                                    </div>
									<?php if($sale->job_title =='nonnurse specialist' || $sale->job_title =='nurse specialist'){?>
                                    <div class="form-group" id="specialist_edit">
                                    <label>Select Job Profession</label>
                                    <select name='job_title_prof' class='form-control form-control-select2' id='job_title_prof_id' required>
                                        <option value=''>Select Profession</option>
                                         @foreach($spec_all_jobs_data as $item) 
                                        <option value="{{$item['id']}}" @if($sec_job_data && $sec_job_data->id == $item['id']) selected='selected' @endif()> {{ $item['specialist_prof'] }}</option>
                                        @endforeach()
                                   </select>

                                    </div>
                                    <?php if($sec_job_data) { ?>
                                    <input type="hidden" name="job_title_prof_val" id="job_title_prof_val" value="{{$sec_job_data['id']}}">
                                    <?php }?>
                                     <!-- <div class="form-group" id="specialist_edit_new">

                                    </div> -->
                                    <?php }?>
                                    <div class="form-group" id="specialist_edit_special_only">
                                   </div>
                                    <input type="hidden" name="sale_id" id="sale_id" value="{{$sale->id}}">

                                    <div class="form-group" id="specialist_edit_new">

                                    </div>
									<div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="postcode">Postcode <em class="text-danger">(Please avoid adding any special characters or comments.)</em></label>
                                                <input id="postcode_id" type="text" value="{{ old('postcode', $sale->postcode) }}" class="form-control" name="postcode" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('postcode') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-3">
                                            <div class="form-group">
                                                <label for="lat">Latitude <em class="text-info">(Optional)</em></label>
                                                <input id="latitude" type="text" value="{{ old('lat', $sale->lat) }}" class="form-control" name="lat" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('lat') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-3">
                                            <div class="form-group">
                                                <label for="long">Longitude <em class="text-info">(Optional)</em></label>
                                                <input id="longitude" type="text" value="{{ old('long', $sale->lng) }}" class="form-control" name="long" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('long') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-2">
											<div class="form-group">
												<a href="https://www.freemaptools.com/convert-uk-postcode-to-lat-lng.htm" class="btn btn-info"  target="_blank">
													<i class="bi-search"></i> Find Manual
												</a>
											</div>
										</div>
                                    </div>
                                    <div class="row">
										 <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="send_cv_limit">Send CV Limit</label>
                                                <input id="send_cv_limit" type="number" placeholder="ENTER SEND CV LIMIT" class="form-control" name="send_cv_limit" value="{{old('send_cv_limit', $sale->send_cv_limit)}}" min="{{ $sent_cv_count }}" max="10" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('send_cv_limit') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="job_type">Select Job Type</label>
												<select name="job_type" id="job_type_id" class="form-control form-control-select2" required>
													<option value="">Select JOB TYPE</option>
													<option value="part time" @if($sale->job_type == 'part time') selected='selected' @endif()>PART TIME</option>
													<option value="full time" @if($sale->job_type == 'full time') selected='selected' @endif()>FULL TIME</option>
												</select>
												<span> <small class = "text-danger"> {{ $errors->first('job_type') }} </small> </span>
											</div>
										</div>
									</div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="timing">Timing</label>
                                                <textarea class="form-control" id="timing" name="timing"  cols="10" rows="4" style="margin-bottom: 10px;" placeholder="ENTER TIME" required>{{ old('timing', $sale->timing) }}</textarea>
                                                <span> <small class = "text-danger"> {{ $errors->first('timing') }} </small> </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="experience">Experience</label>
                                                <textarea id="experience_id" type="text" name="experience" cols="10" rows="4" style="margin-bottom: 10px;" placeholder="ENTER REQUIRED EXPERIENCE" class="form-control" required>{{ old('experience', $sale->experience) }}</textarea>
                                                <span> <small class = "text-danger"> {{ $errors->first('experience') }} </small> </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="salary">Salary</label>
                                        <input id="salary_id" type="text" name="salary" value="{{ old('salary', $sale->salary) }}" class="form-control" required>
                                        <span> <small class = "text-danger"> {{ $errors->first('salary') }} </small> </span>
                                    </div>
                                    
                                    
                                        <div class="form-group">
                                            <label for="headOffice">Select Head Office</label>
                                            <select name="head_office" id="head_office_id" class="form-control form-control-select2" required>
                                                <option value=''>Select Head Office</option>
                                                @foreach($office_types as $item)
                                                    <option value="{{ $item->id }}" @if($sale->head_office == $item->id) selected='selected' @endif()>{{ $item->office_name}}</option>
                                                @endforeach()
                                            </select>
											<span> <small class = "text-danger"> {{ $errors->first('head_office') }} </small> </span>
                                        </div>
                                        <div class="form-group" id="offices_units" data-unit_id="{{ $sale->head_office_unit }}">

                                        </div>
										<span> <small class = "text-danger"> {{ $errors->first('head_office_unit') }} </small> </span>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Benefits</label>
                                                    <textarea class="form-control" id="benefits_id" name="benefits" placeholder="ENTER BENEFITS" cols="10" rows="4" style="margin-bottom: 10px;" required>{{ old('benefits', $sale->benefits) }}</textarea>
                                                    <span> <small class = "text-danger"> {{ $errors->first('benefits') }} </small> </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification</label>
                                                    <textarea id="qualification_id" type="text" name="qualification" cols="10" rows="4" style="margin-bottom: 10px;" placeholder="ENTER QUALIFICATION" class="form-control" required>{{ old('qualification', $sale->qualification) }}</textarea>
                                                    <span> <small class = "text-danger"> {{ $errors->first('qualification') }} </small> </span>
                                                </div>
                                            </div>
                                        </div>
{{--                                        <div class="form-group">--}}
{{--                                            <label>Notes</label>--}}
{{--                                            <textarea class="form-control" id="notes_id" name="sale_note" cols="10" rows="6" style="margin-bottom: 10px;" required>{{ old('sale_note', $sale_note['sale_note']) }}</textarea>--}}
{{--                                            <textarea class="form-control" id="notes_id" name="sale_note" cols="10" rows="6" style="margin-bottom: 10px;" required>{{ old('sale_note') }}</textarea>--}}
{{--                                            <span> <small class = "text-danger"> {{ $errors->first('sale_note') }} </small> </span>--}}
{{--                                            <input type="hidden" name="sale_note_key" value="{{ $sale_note['key'] }}">--}}
{{--                                        </div>--}}
									<div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" id="notes_id" name="sale_note" placeholder="ENTER NOTES" cols="10" rows="6" style="margin-bottom: 10px;" required>{{old('sale_note')}}</textarea>
                                            <span> <small class = "text-danger"> {{ $errors->first('sale_note') }} </small> </span>
                                        </div>
 <div class="form-group">
                                        <label for="notes">Job Description</label>
                                        <textarea class="summernote" id="job_description" name="job_description" placeholder="ENTER NOTES" cols="10" rows="6" style="margin-bottom: 10px;">{!! $sale->job_description !!}</textarea>
                                        <span> <small class = "text-danger"> {{ $errors->first('job_description') }} </small> </span>
                                    </div>
									 <div class="form-group">
                                        <label for="attachments">Attachments</label>
                                        <input type="file" name="attachments[]" class="form-control form-input-styled" id="attachment_id" accept=".pdf, .doc, .docx, .xls, .xlsx, .csv" multiple>
                                        <!-- Display validation errors if any -->
                                        <span><small class="text-danger">{{ $errors->first('attachments.*') }}</small></span>
                                    </div>
                                    <div class="form-group">
                                        @if($sale->sale_documents)
                                            <div class="form-group">
                                                <label>Current Attachments</label>
                                                @forelse($sale->sale_documents as $document)
                                                <div class="attachment-preview" id="document-{{ $document->id }}">
                                                    @php
                                                        // Get the file extension (convert it to lowercase for consistency)
                                                        $extension = strtolower($document->document_extension);
                                                        $doc_name = $document->document_name;
                                                        $file_path = $document->document_path;
                                            
                                                        // Define default icon class and title
                                                        $iconClass = 'fas fa-file-alt'; // Default icon for unknown file types
                                                        $iconTitle = 'File'; // Default icon title
                                                    @endphp
                                            
                                                   @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']))
                                                        @php
                                                            $iconClass = 'fas fa-image'; // Image icon
                                                            $iconTitle = 'Image';
                                                        @endphp
                                                    @elseif(in_array($extension, ['pdf']))
                                                        @php
                                                            $iconClass = 'fas fa-file-pdf text-danger'; // PDF icon
                                                            $iconTitle = 'PDF Document';
                                                        @endphp
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        @php
                                                            $iconClass = 'fas fa-file-word text-primary'; // Word document icon
                                                            $iconTitle = 'Word Document';
                                                        @endphp
                                                    @elseif(in_array($extension, ['xls', 'xlsx']))
                                                        @php
                                                            $iconClass = 'fas fa-file-excel text-success'; // Excel spreadsheet icon
                                                            $iconTitle = 'Excel Spreadsheet';
                                                        @endphp
                                                    @elseif(in_array($extension, ['csv']))
                                                        @php
                                                            $iconClass = 'fas fa-file-csv text-success'; // CSV file icon
                                                            $iconTitle = 'CSV File';
                                                        @endphp
                                                    @elseif(in_array($extension, ['zip', 'rar']))
                                                        @php
                                                            $iconClass = 'fas fa-file-archive text-primary'; // Archive file icon
                                                            $iconTitle = 'Archive File';
                                                        @endphp
                                                    @elseif(in_array($extension, ['txt']))
                                                        @php
                                                            $iconClass = 'fas fa-file-alt'; // Text file icon
                                                            $iconTitle = 'Text File';
                                                        @endphp
                                                    @elseif(in_array($extension, ['ppt', 'pptx']))
                                                        @php
                                                            $iconClass = 'fas fa-file-powerpoint text-warning'; // PowerPoint file icon
                                                            $iconTitle = 'PowerPoint Presentation';
                                                        @endphp
                                                    @endif
                                            
                                                    <!-- For non-image files, show an icon and the filename -->
                                                    <a href="{{ asset($file_path) }}" target="_blank" class="file-link">
                                                        <i class="{{ $iconClass }} file-icon" title="{{ $iconTitle }}"></i>
                                                        <span class="file-description">{{ strtoupper($doc_name) }}</span>
                                                    </a>
                                            
                                                    <!-- Button to remove the document -->
                                                    <button type="button" class="btn btn-danger btn-remove" data-sale-document-id="{{ $document->id }}">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            @empty
                                                <div class="alert alert-transparent">No attachments found.</div>
                                            @endforelse
                                            
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn bg-teal legitRipple">Save <i class="icon-paperplane ml-2"></i></button>
                                    </div>
									<input type="hidden" name="previous_url" value="{{ old('previous_url', url()->previous()) }}">
                                    {{ Form::close() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /form centered -->
        </div>
        <!-- /content area -->

@endsection
		
		       @section('script')
            <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                $('.summernote').summernote({
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        // ['insert', ['link', 'picture', 'video']],
                        // ['misc', ['codeview']]
                    ]
                });
 				$(document).on('click', '.btn-remove', function() {  // Bind event to .btn-remove using event delegation
					var documentId = $(this).data('sale-document-id');  // Get the sale document ID from the button's data attribute

					// Confirm the action
					if (confirm('Are you sure you want to remove this attachment?')) {
						// Send the DELETE request using AJAX
						$.ajax({
							url: '/sales/removeAttachment/' + documentId,  // Make sure the URL is correct
							type: 'DELETE',
							data: {
								_token: '{{ csrf_token() }}'  // Send CSRF token for security
							},
							success: function(response) {
								if (response.status) {
									toastr.success(response.message);

									// Fade out and remove the attachment preview container
									$('#document-' + documentId).fadeOut(500, function() {
										$(this).remove();  // Remove the attachment container from the DOM
									});
								} else {
									toastr.error(response.message);
								}
							},
							error: function(xhr, status, error) {
								toastr.error('An error occurred: ' + error);
							}
						});
					}
				});
            });
        </script>
@endsection
