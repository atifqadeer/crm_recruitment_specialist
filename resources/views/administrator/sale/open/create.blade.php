@extends('layouts.app')

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
                        <span class="font-weight-semibold">Open Sale</span> - Add Sale
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Add Sale</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Centered forms -->
            <!-- Centered forms -->
            @if(session()->has('applicant_add_error'))
                <div class="alert alert-danger">
                    {{ session()->get('applicant_add_error') }}
                </div>
            @endif
        <!-- For Validation Errors  -->
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
                                        <h5 class="card-title">Add a Sale</h5>
                                        <a href="{{ route('sales.index') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <form action="{{ route('sales.store')}}" method="POST" enctype="multipart/form-data">
                                        @csrf()
                                        <div class="form-group">
                                            <label for="job_title_text" class="col-form-label">Select Category</label>
                                            <select name="job_category" id="select_job_category_id" class="form-control form-control-select2" required>
                                                <option value="">SELECT JOB For...</option>
                                                <option value="nurse" {{ old('job_category') == 'nurse' ? 'selected="selected"' : '' }}>NURSE</option>
                                                <option value="nonnurse" {{ old('job_category') == 'nonnurse' ? 'selected="selected"' : '' }}>NON NURSE</option>
										 <option value="chef" {{ old('job_category') == 'chef' ? 'selected="selected"' : '' }}>Chef</option>
<option value="nursery" {{ old('job_category') == 'nursery' ? 'selected="selected"' : '' }}>Nursery</option>
                                            </select>
											<span> <small class = "text-danger"> {{ $errors->first('job_category') }} </small> </span>
                                        </div>
                                    <div class="form-group" id="jobs">

                                    </div>
										<div class="form-group" id="specialist">

                                    </div>
									<span> <small class = "text-danger"> {{ $errors->first('job_title') }} </small> </span>
									<div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="postcode">Postcode <em class="text-danger">(Please avoid adding any special characters or comments.)</em></label>
                                                <input id="postcode_id" type="text" placeholder="ENTER POSTCODE" class="form-control" name="postcode" value="{{old('postcode')}}" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('postcode') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-3">
                                            <div class="form-group">
                                                <label for="lat">Latitude <em class="text-info">(Optional)</em></label>
                                                <input id="latitude" type="text" value="{{ old('lat') }}" class="form-control" name="lat" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('lat') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-3">
                                            <div class="form-group">
                                                <label for="long">Longitude <em class="text-info">(Optional)</em></label>
                                                <input id="longitude" type="text" value="{{ old('long') }}" class="form-control" name="long" required>
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
                                                <input id="send_cv_limit" type="number" placeholder="ENTER SEND CV LIMIT" class="form-control" name="send_cv_limit" value="{{old('send_cv_limit')}}" min="1" max="10" required>
                                                <span> <small class = "text-danger"> {{ $errors->first('send_cv_limit') }} </small> </span>
                                            </div>
                                        </div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="job_type">Select Job Type</label>
												<select name="job_type" id="job_type_id" class="form-control form-control-select2" required>
													<option value="">JOB TYPE</option>
													<option value="part time" {{ old('job_type') == 'part time' ? 'selected="selected"' : '' }}>PART TIME</option>
													<option value="full time" {{ old('job_type') == 'full time' ? 'selected="selected"' : '' }}>FULL TIME</option>
												</select>
												<span> <small class = "text-danger"> {{ $errors->first('job_type') }} </small> </span>
											</div>
										</div>
									</div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="timing">Timing</label>
                                                <textarea class="form-control" id="timing" name="timing"  cols="10" rows="4" style="margin-bottom: 10px;" placeholder="ENTER TIME" required>{{old('timing')}}</textarea>
                                                <span> <small class = "text-danger"> {{ $errors->first('timing') }} </small> </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="experience">Experience</label>
                                                <textarea id="experience_id" type="text" name="experience" cols="10" rows="4" style="margin-bottom: 10px;" placeholder="ENTER REQUIRED EXPERIENCE" class="form-control" required>{{old('experience')}}</textarea>
                                                <span> <small class = "text-danger"> {{ $errors->first('experience') }} </small> </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="salary">Salary</label>
                                        <input id="salary_id" type="text" name="salary" value="{{old('salary')}}" placeholder="ENTER OFFER SALARY" class="form-control" required>
                                        <span> <small class = "text-danger"> {{ $errors->first('salary') }} </small> </span>
                                    </div>
                                    
                                    
                                    <div class="form-group">
                                        <label for="headOffice"> Head Office</label>
                                        <select name="head_office" id="head_office_id" class="form-control form-control-select2" required>
                                            <option value="">Select Head Office</option>
                                            @foreach($head_offices as $item)
                                                <option value="{{ $item->id }}" {{ old('head_office') == $item->id ? 'selected' : '' }}>{{ $item->office_name}}</option>
                                            @endforeach()
                                        </select>
										<span> <small class = "text-danger"> {{ $errors->first('head_office') }} </small> </span>
                                    </div>
                                        <div class="form-group" id="offices_units" data-error_msg="{{ $errors->first('head_office_unit') }}" data-unit_id="">

                                        </div>
										<span> <small class = "text-danger"> {{ $errors->first('head_office_unit') }} </small> </span>
                                        <div class="form-group">
                                            <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Benefits</label>
                                                    <textarea class="form-control" id="benefits_id" name="benefits" placeholder="ENTER BENEFITS" cols="10" rows="4" style="margin-bottom: 10px;" required>{{old('benefits')}}</textarea>
                                                    <span> <small class = "text-danger"> {{ $errors->first('benefits') }} </small> </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification</label>
                                                    <textarea id="qualification_id" type="text" name="qualification" cols="10" rows="4" style="margin-bottom: 10px;" placeholder="ENTER QUALIFICATION" class="form-control" required>{{old('qualification')}}</textarea>
                                                    <span> <small class = "text-danger"> {{ $errors->first('qualification') }} </small> </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" id="notes_id" name="sale_note" placeholder="ENTER NOTES" cols="10" rows="6" style="margin-bottom: 10px;" required>{{old('sale_note')}}</textarea>
                                            <span> <small class = "text-danger"> {{ $errors->first('sale_note') }} </small> </span>
                                        </div>
											 <div class="form-group">
                                            <label for="notes">Job Decription</label>
                                            <textarea class="summernote" id="job_description" name="job_description" placeholder="ENTER NOTES" cols="10" rows="6" style="margin-bottom: 10px;">{{old('sale_note')}}</textarea>
                                            <span> <small class = "text-danger"> {{ $errors->first('job_description') }} </small> </span>
                                        </div>
										 <div class="form-group">
                                            <label for="attachments">Attachments</label>
                                            <input type="file" name="attachments[]" class="form-control form-input-styled" id="attachment_id" accept=".pdf, .doc, .docx, .xls, .xlsx, .csv" multiple>
                                            <!-- Display validation errors if any -->
                                            <span><small class="text-danger">{{ $errors->first('attachments.*') }}</small></span>
                                        </div>
										<div class="text-right">
											{{ Form::button('Save <i class="icon-paperplane ml-2"></i>',['type'=>'submit','class'=>'btn bg-teal legitRipple']) }}
											{{--<button type="submit" class="btn bg-teal legitRipple">Save <i class="icon-paperplane ml-2"></i></button>--}}
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

            });
        </script>
@endsection
