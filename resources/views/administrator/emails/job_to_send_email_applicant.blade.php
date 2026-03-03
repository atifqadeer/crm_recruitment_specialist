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

        #spinner-div{
            display: none;
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            bottom: 0;
            text-align: center;
            background: rgba(255,255,255,0.8) url('{{ asset('assets/img/gif/loader.gif')}}') center no-repeat;
            z-index: 10000;

        }
        body.loading{
            overflow: hidden;
        }
        /* Make spinner image visible when body element has the loading class */
        body.loading #spinner-div{
            display: block;
        }

    </style>

@endsection
@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <a href="#"><i class="icon-arrow-left52 mr-2" style="color: white;"></i></a>
                        <span class="font-weight-semibold">Email</span> - Send
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Applicant</a>
                        <span class="breadcrumb-item active">Email</span>
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
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">
                                    <div class="header-elements-inline">
                                        <h5 class="card-title">Applicants Email</h5>
										<div>
										<a href="#" id="export_email" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cloud-upload"></i> Export Emails
                                        </a>
										
                                        <a href="{{ url('direct-nurse-resource') }}" class="btn bg-slate-800 legitRipple">
                                            <i class="icon-cross"></i> Cancel
                                        </a>
										</div>
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
{{--                                    <form  action="{{route('sent-email-applicant')}}" method="post">--}}
                                        @csrf
                                        <div class="form-group border border-light rounded">
                                            @php
                                                $inputValues = [];
                                            @endphp
                                            @foreach($near_by_applicants as $index=>$near_by_applicant)

                                                @php
                                                    $inputValues[] = $near_by_applicant->applicant_email;
                                                @endphp
                                                <input type="hidden" multiple name="applicant_email_old[]"  value="{{$near_by_applicant->applicant_email}}" id="applicant_email_old[]" required class="form-control"
                                                       placeholder="Recipient"/>
                                            @endforeach

                                            <input type="text" multiple name="applicant_email[]"   value="{{ implode(",", $inputValues) }}" id="applicant_email[]" required class="form-control"
                                                   placeholder="Recipient"/>


                                        </div>
                                        <div class="form-group border border-light rounded">
                                            <input type="text" name="email_title" value="Job vacancy details Kingsbury" id="email_title" required class="form-control"
                                                   placeholder="Subject"/>
                                        </div>
                                        <div class="form-group">
                                            <label>Email Body:</label>
                                        </div>

                                        <div class="form-group border border-light rounded">

                                            <div class="editable" contenteditable="true">
                                                <p> Hi <br>
                                                    Hope you are doing well, This is Chris from Kingsbury Personnel. We were impressed with your experience , we are certain that your expertise would allow us to find you a position according to your needs.
<br><br>
                                                    We have following positions available.
                                                    <br>
                                                    <b>1. Staff  {{$job_result->job_category}} required</b><br>
                                                    <b>Unit </b>| {{$unit->unit_name}}<br>
                                                    <b>Salary</b> | {{$job_result->salary}}<br>
                                                    <b>Qualification</b> | {{$job_result->qualification}}<br>
                                                    <b> Type</b> | {{$job_result->job_type}}<br>
                                                    <b>Time</b> | {{$job_result->timing}}<br>
                                                    <b>Experience </b>| {{$job_result->experience}} year experience required<br>
                                                    <b>Location </b>|<br><br>

                                                      If you are interested in this position, please confirm the following details so we can proceed further.
                                                    <br>
                                                    1.     When you are available for interview?<br>
                                                    2.     Please confirm your Pay expectation, Hours expected, Mode of Traveling ?<br>
                                                    3.     Please attach your updated CV or let me know about your updated employers.<br>
                                                    4.     Do you require sponsorship?<br>
                                                    Looking forward to hear from you<br>
                                                    If the above position is not suitable, we would love to have a conversation to know you better as well as introduce ourselves, please do advise a suitable time.
                                                    <br>
                                                    You can either reply to this email or contact us on the information given below.
                                                    <br>
                                                    Best regards,
                                                    <br>
                                                    Recruitment Team.
                                                    <br>
                                                    T: 01494211220
                                                    <br>
                                                    E: info@kingsburypersonnel.com
                                                     <br>
                                                </p>
                                            </div>

                                        </div>

                                        <div class="text-right">
                                            <a href="javascript:;" class="btn bg-teal legitRipple disabled" id="send_app_email" ><i class="icon-paperplane"></i> Send</a>
{{--                                            <button  class="btn bg-teal legitRipple" type="submit" ><i class="icon-paperplane"></i> Send</button>--}}
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
        <div id="spinner-div">
        </div>

        @endsection
        @section('script')
             <script src="http://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.js" defer></script>
            <script>

                $(document).on("click", "#send_app_email", function(e){
                    e.preventDefault();
                    var email_body = $('.editable').html();
                    var app_email = $("input[name='applicant_email[]']")
                        .val();
                    var email_title = $("#email_title").val();
                    if(app_email == '' || email_title == '' || email_body =='')
                    {
                        toastr.error('All fields are required...');
                        $("#spinner-div").hide();

                        return false;
                    }
                    $("#spinner-div").show();
                    $.ajax({
                        url: "{{route('sent-email-applicant')}}",
                        type: "post",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        dataType: "json",
                        data: {'email_body':email_body, 'app_email':app_email, 'email_title':email_title },
                        success: function (success) {
                                setTimeout(function () {
                                        $("#spinner-div").hide();
                                    },
                                    3000);
                                $("#applicant_email").val('');
                                $("#email_title").val('');
                                toastr.success('Email sent successfully!');

                        },
                        error: function (response) {
                            $("#spinner-div").hide();

                            // console.log('error '+response);
							toastr.error(response.responseJSON.message);

                        }
                    });
                });
				
				//Email export js function
		 $(document).on("click", "#export_email", function(e){
                    var app_email = $("input[name='applicant_email[]']")
                        .val();
                    $.ajax({
                        url: "{{route('export_email_job')}}",
                        type: "post",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        xhrFields: {
                            responseType: 'blob'
                        },
                        data: {'app_email':app_email},
                        success: function(response, status, xhr) {
                            var blob = new Blob([response], { type: xhr.getResponseHeader('content-type') });
                            var link = document.createElement('a');
                            link.href = window.URL.createObjectURL(blob);
                            link.download = 'applicants.csv';
                            link.click();
                            toastr.success('Email export successfully!');

                        },
                        error: function (response) {

                            toastr.error('Email is not export!');
                        }
                    });


                });

            </script>
@endsection
