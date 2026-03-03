<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('img/cv.png') }}" type="image/png">
    <style>

        .mark{
            background-color: yellow;
        }
        .reject{
            color: white;
            background-color: black;
        }
        .reject_job{
            color: white;
            background-color: red;
        }
        .not_reject{
            background-color: green;
            color:white;
        }
        .accept{
            background-color: #00796a;
        }
        .clear{
            background-color: transparent;
        }
        .select2-selection__rendered{
            margin-left:30px;
        }
    </style>

    <!-- Fonts -->
    <link rel="shortcut icon" href="{{{ asset('img/download.png') }}}">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">
    <!-- Global stylesheets -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	
    <link href="{{ asset('global_assets/css/icons/icomoon/styles.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}?v={{ time() }}" rel="stylesheet" type="text/css"> 
    <link href="{{ asset('assets/css/bootstrap-toaster.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <link href="{{ asset('assets/css/bootstrap_limitless.min.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/layout.min.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/components.min.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/colors.min.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('global_assets/css/icons/fontawesome-5-12/css/all.min.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/custom.css') }}?v={{ time() }}" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->
	
    <!-- Core JS files -->
    <script src="{{ asset('global_assets/js/main/jquery.min.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('global_assets/js/main/bootstrap.bundle.min.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('global_assets/js/plugins/loaders/blockui.min.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('global_assets/js/plugins/ui/ripple.min.js') }}?v={{ time() }}"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <!-- /core JS files -->

    <!-- Theme JS files -->
	<!--  <script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.js"></script>

	<script src="https://unpkg.com/@popperjs/core@2"></script> -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>

    <script src="{{ asset('global_assets/js/plugins/tables/datatables/datatables.min.js') }}"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
	
    <script src="{{ asset('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/demo_pages/form_select2.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/visualization/d3/d3.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/visualization/d3/d3_tooltip.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/ui/moment/moment.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/pickers/daterangepicker.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/pickers/anytime.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/pickers/pickadate/picker.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/pickers/pickadate/picker.date.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/pickers/pickadate/picker.time.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/pickers/pickadate/legacy.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/notifications/jgrowl.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/demo_pages/picker_date.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('global_assets/js/demo_pages/dashboard.js') }}"></script>
    <script src="{{ asset('global_assets/js/demo_pages/datatables_sorting.js') }}"></script>
    <script src="{{ asset('global_assets/js/demo_pages/form_layouts.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/extensions/jquery_ui/interactions.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/uploaders/fileinput/plugins/purify.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/uploaders/fileinput/plugins/sortable.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/uploaders/fileinput/fileinput.min.js') }}"></script>
    <script src="{{ asset('global_assets/js/demo_pages/uploader_bootstrap.js') }}"></script>
    <!-- /theme JS files -->
    <script src="{{ asset('js/dist/jquery.inputmask.bundle.js') }}"></script>
    <script src="{{ asset('global_assets/js/plugins/charts/loader.js') }}"></script>
	<script src="{{ asset('global_assets/js/main/bootstrap-toaster.min.js') }}" defer></script>
	<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.3.0/socket.io.js"
            integrity="sha256-bQmrZe4yPnQrLTY+1gYylfNMBuGfnT/HKsCGX+9Xuqo=" crossorigin="anonymous"></script>
    @yield('style')
</head>
<body>
	@include('inc/navbar')
	<div class="page-content">
		@include('inc/sidebar')
		@yield('content')
		@include('inc/footer')
		@include('layouts/small_chat_box')
	</div>
<!-- /main content -->
<script>
    $(document).ready(function() {
        $('.table-responsive').on('show.bs.dropdown', function () {
			 $('.table-responsive').css( "overflow", "inherit" );
		});

		$('.table-responsive').on('hide.bs.dropdown', function () {
			 $('.table-responsive').css( "overflow", "auto" );
		})

		var page_temp = location.pathname.split("/");
        var page_edit = "/"+page_temp[1]+"/"+page_temp[3];

        if (location.pathname == '/sales/create' || page_edit == '/sales/edit') {

            if ($("#head_office_id").val() != "") { //if head office has already been selected once before
                var head_office_id = $("#head_office_id").val();
                //var unit_id = "";
                var unit_id = $("#offices_units").data("unit_id");

                $.ajax({//get back unit select box from the beginning of page reload
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: "{{ url('getUnits') }}",
                    method: 'POST',
                    data: {
                        office_id : head_office_id
                    },
                    success: function(result){
                        if (result) {
                            var options = "";
                            options += "<label>Choose Unit</label><select name='head_office_unit' class='form-control form-control-select2' id='head_office_unit' required><option value=''>Select Unit</option>";
                            for (var i = 0; i < result.length; i++) {
                                options += "<option value='"+result[i]['id']+"'>" + result[i]['unit_name'] + "</option>";
                            }
                            options += "</select>";
                            /*options += "<option value='" + result[i]['id'] + "' <?//php echo old('head_office_unit')==" + result[i]['id'] + " ? 'selected' : '' ?>>" + result[i]['unit_name'] + "</option>";*/
                            $("#offices_units").html(options);

                            if (unit_id=="") {
                                $.ajax({//set the value of unit to selected in the select box
                                    url: "{{ url('getUnitID') }}",
                                    method: 'GET',
                                    success: function(result){
                                        if (result) {
                                            unit_id = result;
                                            $("#head_office_unit option[value='"+ unit_id +"']").attr('selected', 'selected');
                                            $("#head_office_unit").trigger("change");
                                        } else {
                                            if(result==""){
                                                unit_id = "";
                                            } else {
                                                alert("Could not retrieve unit id from session.");
                                            }
                                        }
                                    }
                                });
                            } else {
                                $("#head_office_unit option[value='"+ unit_id +"']").attr('selected', 'selected');
                                $("#head_office_unit").trigger("change");
                            }
                        } else {
                            alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                        }
                    }
                });
            }

            if ($("#select_job_category_id").val() != "") { //if job category has already been selected once before
                var job_category = $("#select_job_category_id").val();
                var title_id = "";

                if(job_category === 'nurse'){
                    var options = "";
                    options += "<label>Choose Job Title</label>" +
                        "<select name='job_title' class='form-control form-control-select2' id='job_title' required>" +
                        "<option value=''>Select Job Title</option>" +
                        "<option value='rgn'>RGN</option>" +
                        "<option value='rmn'>RMN</option>" +
                        "<option value='rnld'>RNLD</option>" +
                        "<option value='nurse deputy manager'>NURSE DEPUTY MANAGER</option>" +
                        "<option value='nurse manager'>MANAGER</option>" +
                        "<option value='senior nurse'>SENIOR NURSE</option>" +
                        "<option value='rgn/rmn'>RGN/RMN</option>" +
                        "<option value='rmn/rnld'>RMN/RNLD</option>" +
                        "<option value='rgn/rmn/rnld'>RGN/RMN/RNLD</option>" +
                        "<option value='clinical lead'>CLINICAL LEAD</option>" +
                        "<option value='rcn'>RCN</option>" +
                        "<option value='peripatetic nurse'>PERIPATETIC NURSE</option>" +
                        "<option value='unit manager'>UNIT MANAGER</option>" +
						"<option value='nurse specialist'>NURSE SPECIALIST</option>" +
                        "</select>";
                    $("#jobs").html(options);
                }
                else if(job_category === 'nonnurse'){
                    var options2 = "";
                    options2 += "<label>Choose Job Title</label>" +
                        "<select name='job_title' class='form-control form-control-select2' id='job_title' required>" +
                        "<option value=''>Select Job Title</option>" +
                        "<option value='care assistant'>CARE ASSISTANT</option>" +
                        "<option value='senior care assistant'>SENIOR CARE ASSISTANT</option>" +
                        "<option value='team lead'>TEAM LEAD</option>" +
                        "<option value='deputy manager'>DEPUTY MANAGER</option>" +
                        "<option value='registered manager'>REGISTERED MANAGER</option>" +
                        "<option value='support worker'>SUPPORT WORKER</option>" +
                        "<option value='senior support worker'>SENIOR SUPPORT WORKER</option>" +
						"<option value='support worker / care assistant'>SUPPORT WORKER / CARE ASSISTANT</option>" +
                        "<option value='senior support worker / senior care assistant'>SENIOR SUPPORT WORKER / SENIOR CARE ASSISTANT</option>" +
                        "<option value='activity coordinator'>ACTIVITY COORDINATOR</option>" +
						"<option value='nonnurse specialist'>NON-NURSE SPECIALIST</option>" +
                        "</select>";
                    $("#jobs").html(options2);
                } else if (job_category === 'nursery') {
					var options3 = "";
					options3 += "<label>Choose Job Title</label>" +
						"<select name='job_title' class='form-control form-control-select2' id='job_title' required>" +
						"<option value=''>Select Job Title</option>" +
						"<option value='nursery manager'>NURSERY MANAGER</option>" +
						"<option value='nursery deputy manager'>NURSERY DEPUTY MANAGER</option>" +
						"<option value='nursery practitioner'>NURSERY PRACTITIONER</option>" +
						"<option value='room leader'>ROOM LEADER</option>" +
						"<option value='baby room manager'>BABY ROOM MANAGER</option>" +
						"<option value='teacher'>TEACHER</option>" +
						"<option value='room attendant / nursery Assistant'>ROOM ATTENDANT / NURSERY ASSISTANT</option>" +
						"</select>";
					$("#jobs").html(options3);
				}

                $.ajax({//set the value of job title to selected in the select box
                    url: "{{ url('getTitleID') }}",
                    method: 'GET',
                    success: function(result){
                        if (result) {
                            title_id = result;
                            $("#job_title option[value='"+ title_id +"']").attr('selected', 'selected');
                            $("job_title").trigger("change");
                        } else {
                            if(result==""){
                                title_id = "";
                            } else {
                                alert("Could not retrieve unit id from session.");
                            }
                        }
                    }
                });
            }

        }

        $('#duplicate_note_id').on('click',function(){
            var applicant_note_title = $('#note_title_id').val();
            var applicant_note = $('#duplicate_note_for_applicants_id').val();
            var applicant_email = $('#email_address_id').val();
            var applicant_postcode = $('#postcode_id').val();
            var applicant_phone = $('#phone_number_id').val();

            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url:   "{{ url('notes') }}",
                method: 'POST',
                data: {
                    note_title : applicant_note_title,
                    note_descr : applicant_note,
                    appl_email : applicant_email,
                    postcode: applicant_postcode,
                    phone : applicant_phone
                },
                success: function(result){
                    console.log(result);
                    if(result){
                        location.reload();
                        //alert(applicant_note_title.concat(applicant_note, applicant_email,applicant_postcode,applicant_phone,"Applicant Note has been Noted"));
                        alert("Applicant Note has been Noted");
                    }
                    else{
                        alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                        location.reload();
                    }
                }
            });
        });
        $('#head_office_id').change(function(){
            var head_office_id = $(this).val();

            // AJAX CALL FOR GETTING UNITS
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url: "{{ url('getUnits') }}",
                method: 'POST',
                data: {
                    office_id : head_office_id
                },
                success: function(result){

                    if(result){
                        console.log(result);
                        var options = "";
                        options += "<label>Choose Unit</label><select name='head_office_unit' class='form-control form-control-select2' id='head_office_unit' required><option value=''>Select Unit</option>";
                        for (var i = 0; i < result.length; i++) {
                            options += "<option value='"+result[i]['id']+"'>" + result[i]['unit_name'] + "</option>";
                        }
                        options += "</select>";
                        $("#offices_units").html(options);
                    }
                    else{
                        alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                    }
                }
            });
        });

		$('#offices_units').on('change', function () {//if the unit select box is clicked
            var unit_id =  $('#head_office_unit').val();     // get id the value from the select
            $("#head_office_unit option[value='"+ unit_id +"']").attr('selected', 'selected');
            $("head_office_unit").trigger("change");

            $.ajax({//save value of selected id in session
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ url('setUnitID') }}",
                method: 'POST',
                data: {
                    unit_list_id : unit_id
                },
                success: function (result) {
                    if (result) {
                        console.log("Session for unit id was created.");
                    } else {
                        alert("Session for unit id failed.");
                    }
                }
            });
        });

        $('#jobs').on('change', function() {
            var title_id = $('#job_title').val();
            $("#job_title option[value='" + title_id + "']").attr('selected', 'selected');
            $("job_title").trigger("change");

            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ url('setTitleID') }}",
                method: 'POST',
                data: {
                    job_title_id: title_id
                },
                success: function (result) {
                    if (result) {
                        console.log("Session for title id was created.");
                    } else {
                        alert("Session for title id failed.");
                    }
                }
            });
        });

        // $('.crm_select_reason').on('click',function(){
        //     $('.reject_btn').css("display","block");
        // });
        // $('.crm_select_reason').on('change',function(){
        //     $('.reject_btn').css("display","block");
        // });

        $('#select_job_category_id').change(function(){
            var job_category = $(this).val();
            if(job_category === 'nurse'){
                var options = "";
                options += "<label>Choose Job Title</label>" +
                    "<select name='job_title' class='form-control form-control-select2' id='job_title_spec' required>" +
                    "<option value=''>Select Job Title</option>" +
                    "<option value='rgn'>RGN</option>" +
                    "<option value='rmn'>RMN</option>" +
                    "<option value='rnld'>RNLD</option>" +
                    "<option value='senior nurse'>SENIOR NURSE</option>" +
                    "<option value='nurse deputy manager'>NURSE DEPUTY MANAGER</option>" +
                    "<option value='nurse manager'>MANAGER</option>" +
                    "<option value='rgn/rmn'>RGN/RMN</option>" +
                    "<option value='rmn/rnld'>RMN/RNLD</option>" +
                    "<option value='rgn/rmn/rnld'>RGN/RMN/RNLD</option>" +
                    "<option value='clinical lead'>CLINICAL LEAD</option>" +
                    "<option value='rcn'>RCN</option>" +
                    "<option value='peripatetic nurse'>PERIPATETIC NURSE</option>" +
                    "<option value='unit manager'>UNIT MANAGER</option>" +
					"<option value='nurse specialist'>Nurse Specialist</option>" +
                    "</select>";
                $("#jobs").html(options);
            }
            else if(job_category === 'nonnurse'){
                var options2 = "";
                options2 += "<label>Choose Job Title</label>" +
                    "<select name='job_title' class='form-control form-control-select2' id='job_title_spec' required>" +
                    "<option value=''>Select Job Title</option>" +
                    "<option value='care assistant'>CARE ASSISTANT</option>" +
                    "<option value='senior care assistant'>SENIOR CARE ASSISTANT</option>" +
                    "<option value='team lead'>TEAM LEAD</option>" +
                    "<option value='deputy manager'>DEPUTY MANAGER</option>" +
                    "<option value='registered manager'>REGISTERED MANAGER</option>" +
                    "<option value='support worker'>SUPPORT WORKER</option>" +
                    "<option value='senior support worker'>SENIOR SUPPORT WORKER</option>" +
					"<option value='support worker / care assistant'>SUPPORT WORKER / CARE ASSISTANT</option>" +
                    "<option value='senior support worker / senior care assistant'>SENIOR SUPPORT WORKER / SENIOR CARE ASSISTANT</option>" +
                    "<option value='activity coordinator'>ACTIVITY COORDINATOR</option>" +
					"<option value='nonnurse specialist'>Non-Nurse Specialist</option>" +
                    "</select>";
                $("#jobs").html(options2);
            }
			else if(job_category === 'chef'){
                var options3 = "";
                options3 += "<label>Choose Job Title</label>" +
                    "<select name='job_title' class='form-control form-control-select2' id='job_title_spec' required>" +
                    "<option value=''>Select Job Title</option>" +
                    "<option value='chef'>Chef</option>" +
                    "<option value='chef de partie'>Chef De Partie</option>" +
                    "<option value='sous chef'>Sous Chef</option>" +
                    "<option value='commis chef'>Commis Chef</option>" +
                    "<option value='head chef'>Head Chef</option>" +
                    "</select>";
                $("#jobs").html(options3);
             } else if (job_category === 'nursery') {
				 var options4 = "";
				 options4 += "<label>Choose Job Title</label>" +
					 "<select name='job_title' class='form-control form-control-select2' id='job_title' required>" +
					 "<option value=''>Select Job Title</option>" +
					 "<option value='nursery manager'>NURSERY MANAGER</option>" +
					 "<option value='nursery deputy manager'>NURSERY DEPUTY MANAGER</option>" +
					 "<option value='nursery practitioner'>NURSERY PRACTITIONER</option>" +
					 "<option value='room leader'>ROOM LEADER</option>" +
					 "<option value='baby room manager'>BABY ROOM MANAGER</option>" +
					 "<option value='teacher'>TEACHER</option>" +
					 "<option value='room attendant / nursery Assistant'>ROOM ATTENDANT / NURSERY ASSISTANT</option>" +
					 "</select>";
				 $("#jobs").html(options4);
			 }
        });
		$("#jobs").change(function(){
            var result = $("#job_title_spec :selected").val();
            
            if(result === 'nurse specialist' || result === 'nonnurse specialist'){
            $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: "{{ url('get_specialist_titles') }}",
                    method: 'POST',
                    data: {
                        specialist : result
                    },
                    success: function(response){
                       
                        if (response) {
                            var options = "";
                            options += "<label>Select Job Profession</label><select name='job_title_prof' class='form-control form-control-select2' id='select_job_title_id' required><option value=''>Select Profession</option>";
                            $.each(response, function(index, item) {
                                options += "<option value='"+item.id+"'>" + item.specialist_prof + "</option>";
                            });
                            options += "</select>";
                            $("#specialist").html(options);
                        } else {
                            alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                        }
                    }
                });
            }
            else{
                $("#specialist").html('');
            }
                             
        });
		$("#select_job_title_id").change(function(){
            var job_title_spec= $("#select_job_title_id :selected").val();
            var sale_id= $("#sale_id").val();
			// var result = $("#select_job_title_id :selected").val();
            // alert(job_title_spec);
            if(job_title_spec === 'nurse specialist-nurse' || job_title_spec === 'nonnurse specialist-nonnurse'){

                $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: "{{ url('get_all_specialist_titles') }}",
                    method: 'POST',
                    data: { 
                            "job_title_spec": job_title_spec,
                            "sale_id": sale_id
                        },
                    success: function(response){
                       console.log(response.all_prof_data);
                        if (response) {
                            var options = "";
                            
                            options += "<label>Select Job Profession</label><select name='job_title_prof' class='form-control form-control-select2' id='job_title_prof_id' required><option value=''>Select Profession</option>";
                            $.each(response.all_prof_data, function(index, item) {
                                var last_select_prof='';
                                if(response.selected_prof_data !=undefined && response.selected_prof_data.id == item['id'])
                                {
                                   
                                        last_select_prof='selected';
                                    
                                    
                                }
                                options += "<option value='"+item['id']+"' "+ last_select_prof +">" + item['specialist_prof'] + "</option>";
                            });
                            options += "</select>";
                            console.log(options);
                            $("#specialist_edit").hide();
                            $("#specialist_edit_special_only").html(options);
                        } else {
                            alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                        }
                    }
                });








                // var textbox = "<div class='form-group'>"+
                //                         "<label for='experience'>Job Title Profession</label>"+
                //                         "<input id='job_title_prof_id' type='text' name='job_title_prof' value='" + job_title_prof+"' placeholder='ENTER JOB TITLE PROFESSION' class='form-control' required>"+
                //                         "<span> <small class = 'text-danger'> {{ $errors->first('job_title_prof') }} </small> </span>"+
                //                    "</div> ";
                // $("#specialist_edit").html(textbox);
            }
            else{
                $("#specialist_edit").html('');
				$("#specialist_edit_special_only").html('');

            }
            
                             
        });
		
		// applicant create ajax call
        $("#app_job_title_spec").change(function(){
            var result = $("#app_job_title_spec :selected").val();
            if(result === 'nurse specialist' || result === 'nonnurse specialist'){
            $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: "{{ url('get_specialist_titles') }}",
                    method: 'POST',
                    data: {
                        specialist : result
                    },
                    success: function(response){
                       
                        if (response) {
                            var options = "";
                            options += "<label>Select Job Profession</label><select name='job_title_prof' class='form-control form-control-select2' id='select_job_title_id' required><option value=''>Select Profession</option>";
                            $.each(response, function(index, item) {
                                options += "<option value='"+item.id+"'>" + item.specialist_prof + "</option>";
                            });
                            options += "</select>";
                            $("#app_specialist").html(options);
                        } else {
                            alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                        }
                    }
                });
            }
            else{
                $("#specialiapp_specialistst").html('');
            }
        });

        // applicant edit ajax call 
        $("#applicant_job_title_id").change(function(){
            
            var job_title_spec= $("#applicant_job_title_id :selected").val();

            var applicant_id= $("#applicant_id").val();

            // alert(applicant_id);
			// var result = $("#select_job_title_id :selected").val();
            // alert(job_title_spec);
            if(job_title_spec === 'nurse specialist' || job_title_spec === 'nonnurse specialist'){
                $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url: "{{ url('app_get_all_specialist_titles') }}",
                    method: 'POST',
                    data: { 
                            "job_title_spec": job_title_spec,
                            "applicant_id": applicant_id
                        },
                    success: function(response){
                        // console.log(response);
                       console.log(response[1].id);
                        if (response) {
                            var options = "";
                            
                            options += "<label>Select Job Profession</label><select name='job_title_prof' class='form-control form-control-select2' id='job_title_prof_id' required><option value=''>Select Profession</option>";
                            $.each(response[0], function(index, item) {
                                var last_select_prof='';
                                if(response[1].id == item.id)
                                {
                                    last_select_prof='selected';
                                }
                                options += "<option value='"+item.id+"' "+ last_select_prof +">" + item.specialist_prof + "</option>";
                            });
                            options += "</select>";
                            $("#app_specialist_edit").hide();
                            $("#app_specialist_edit_special_only").html(options);
                        } else {
                            alert("WHOOPS! Something Went Wrong.Contact Your Development Support");
                        }
                    }
                });
            }
            else{
                $("#app_specialist_edit").html('');
                $("#app_specialist_edit_special_only").html('');
            }
			
			
            
                             
        });



        $("#phone_number_id").inputmask({mask: "0*{1,20}"});
        $("#home_number_id").inputmask({mask: "0*{1,20}"});
    });
	
	
	
	
	//  Sms Module js 
$(document).on("click", ".import_cv", function () {
     var app_id = $(this).data('id');
    //  alert(app_id);
     $(".modal-body #applicant_id").val(app_id);
});

$(document).on("click", "#show_chat", function(event){    
    event.preventDefault();
    var applicant_name=$("#applicant_name_chat").val();
	if(applicant_name=='')
	{
		applicant_name=$('#notify_applicant_name').attr('value');
	}
    $("#avatar_name").html(applicant_name);
    let applicant_id = $("#applicant_id_chat").val();
	console.log('id is'+ applicant_id);
    let applicant_phone=$("#applicant_phone_chat").val();
	//alert('applicant_name: '+applicant_name+' & applicant_id:'+applicant_id+' & applicant_phone:'+applicant_phone);
    $("#applicant_chatbox_id").val(applicant_id);
    $("#applicant_chatbox_name").val(applicant_name);
    $("#applicant_chatbox_phone").val(applicant_phone);
	var records_per_page = 1;
    var data_call_status = 'first';
	    console.log('applicant id '+applicant_id+' records per page '+records_per_page+' data call status '+data_call_status);

    getUserMessages(applicant_id,records_per_page,data_call_status);
    show_sm_chat();
});

function getUserMessages(applicant_id, records_per_page, data_call_status) {
            $.ajax({
                url: "{{ route('get-user-messages') }}",
                type: "GET",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    applicant_id: applicant_id,
                    records_per_page: records_per_page
                },
                success: function (response) {
					console.log(response);
                    if (response.data != '' && response.data.length > 0) {
                        var notify_phone = response.data[0]['phone_number'];
                        var notify_app_id = response.data[0]['applicant_id'];
                        var notify_app_name = response.applicant_name;

                        $('#notify_phone').val(notify_phone);
                        $('#notify_applicant_id').val(notify_app_id);
                        $('#notify_applicant_name').val(notify_app_name);
                        $("#applicant_name_chat").val(notify_app_name);
                        $("#avatar_name").text(notify_app_name);
                        var message = '';
                        if (response.data != '') {
                            $.each(response.data.reverse(), function (index, itemData) {
                                var timeDate = '';

                                var tdate = new Date();
                                var dd = tdate.getDate(); //yields day
                                var MM = tdate.getMonth(); //yields month
                                var yyyy = tdate.getFullYear(); //yields year
                                var currentDate = yyyy + "-" + (MM + 1) + "-" + dd;

                                if (itemData.date == currentDate) {
                                    timeDate = itemData.time + ', Today';
                                } else {
                                    timeDate = itemData.time + ', ' + itemData.date;

                                }
          
                                let is_read_status = (itemData.is_read == '0') ? 'Unseen' : 'Seen';
                                if (itemData.status == 'outgoing') {
                                    if (itemData.user_name == 'super_admin') {
                                        message += ' <li class="clearfix user_msg_space">' +
                                            '<div class="message-data text-right">' +
                                            '<span class="message-data-time">' + timeDate +
                                            '</span>' +
                                            '<span class="message-data-time">' + itemData.user
                                            .name + '</span>' +
                                            '<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">' +
                                            ' </div>' +
                                            '<div class="message other-message float-right">' +
                                            itemData.message + '</div>' +
                                            '</li>';
                                    } else {
                                        message += ' <li class="clearfix user_msg_space">' +
                                            '<div class="message-data text-right">' +
                                            '<span class="message-data-time">' + timeDate +
                                            '</span>' +
                                            '<span class="message-data-time">' + itemData.user
                                            .name + '</span>' +
                                            '<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">' +
                                            ' </div>' +
                                            '<div class="message other-message float-right">' +
                                            itemData.message + '</div>' +
                                            '</li>';
                                    }

                                } else {
                                    message += '<li class="clearfix user_msg_space">' +
                                        '<div class="message-data">' +
                                        '<span class="message-data-time"><small class="is_read_style">' +
                                        is_read_status + '</small>' + timeDate + '</span>' +
                                        ' </div>' +
                                        '<div class="message my-message">' + itemData.message +
                                        '</div>' +
                                        '</li>';
                                }

                            });
                            if (data_call_status == 'first') {

                                $(".chat-history").html("");
                                $('.chat-history').append(message);
                                $("#scroll").animate({
                                    scrollTop: $("#scroll")[0].scrollHeight
                                }, 1000);
                                console.log('first call');
                            } else {
                                $('.chat-history').prepend(message);
                                console.log('not first call ');
                            }
                        }
                    } else {
                        //$(".chat-history").html("");
                        console.log('no data found for applicant');
                    }
                },
                error: function (response) {
                    console.log('error');
                }
            });
        }


$(document).on("click", ".sms_action_option", function(event){    
    event.preventDefault();
   var applicant_id= $(this).attr("data-applicantIdJs"); 
   var applicant_phone= $(this).attr("data-applicantPhoneJs"); 
   var applicant_name= $(this).attr("data-applicantNameJs");
   $("#applicant_id_chat").val(applicant_id);
   $("#applicant_phone_chat").val(applicant_phone);
   $("#applicant_name_chat").val(applicant_name);
});
$(document).ready(function(){
    $("#btnClose").click(function() {
        $("#exampleModal").hide();
		$(".small_msg_modal :input").val('');
		$(".chat-history").html('');
    //$(".small_msg_modal :input").prop("disabled", false); 
    // $(".small_msg_modal :input").prop("disabled", false);
    });
   
});
$(document).on("click", "#sendMsg", function(event){
    event.preventDefault();
    let applicant_chatbox_id=$("#applicant_chatbox_id").val();
	if(applicant_chatbox_id=='')
	{
	   
	   applicant_chatbox_id=$('#notify_applicant_id').attr('value');
	   }
    let applicant_chatbox_name=$("#applicant_chatbox_name").val();
	let applicant_chatbox_phone=$("#applicant_chatbox_phone").val();

	if(applicant_chatbox_phone=='')
	{
	   applicant_chatbox_phone=$('#notify_phone').attr('value');
	 }
    var msg_send_id = randstr();
    var applicant_msg = $('#msgText').val();
	//alert(applicant_chatbox_phone);
	var test_phone='07515479514';
  var query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='+applicant_chatbox_phone+'&message='+applicant_msg+'&port=2&report=JSON&timeout=0';
	console.log('query_string: '+query_string);
	
	 var msgType = '';
        $.ajax({
            url: "{{ route('store-user-message_open_vox') }}?t=" + new Date().getTime(),
            type: "post",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {applicant_chatbox_id:applicant_chatbox_id, msg_phone:applicant_chatbox_phone,applicant_msg:applicant_msg,msg_send_id:msg_send_id,msgType:msgType},
            success: function (response) {
                var message_data=response.data;
                if(response.success == true)
                {
                    let message ='<li class="clearfix user_msg_space">'+
                        '<div class="message-data text-right">'+
                        '<span class="message-data-time">'+ message_data.time +' '+ message_data.date +' '+ response.user_name+'</span>'+
                        '<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">'+
                        ' </div>'+
                        '<div class="message other-message float-right">'+message_data.message+'</div>'+
                        '</li>';
                    $('.chat-history').append(message);
                    $('#msgText').val('');
                    $("#toaster_success").toast({ delay: 5000 });
                    $('#toaster_success').toast('show');
                }
                else
                {
                    toastr.error('Error! Applicant message can not be sent try again...');
                }
                // alert(response.msg);
            },
            error: function (response) {
                console.log(response);
                toastr.error('Error! Applicant message can not be stored in database...');
            }
        });
       



    function ajaxCall(msg_phone,msg_time,applicant_msg,msg_send_id)
    {
		var msgType = '';
        $.ajax({
                url: "{{ route('store-user-message') }}",
                type: "post",
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                data: {applicant_chatbox_id:applicant_chatbox_id, msg_phone:msg_phone, msg_time:msg_time,applicant_msg:applicant_msg,msg_send_id:msg_send_id,msgType:msgType},
                success: function (response) {
                    console.log(response.data);
                        let message='<li class="clearfix user_msg_space">'+
                                                '<div class="message-data text-right">'+
                                                    '<span class="message-data-time">'+msg_time+'</span>'+
                                                        '<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">'+
                                                    ' </div>'+
                                                    '<div class="message other-message float-right">'+applicant_msg+'</div>'+
                                                        '</li>';
                    $('.chat-history').append(message);
                    $('#msgText').val('');
					$("#toaster_success").toast({ delay: 5000 });
                    $('#toaster_success').toast('show');
            },
        error: function (response) {
            console.log(response);
            alert('Sms sent... unable to save message in database...');
        }
    });



    }


    function randstr()
    {
        var randLetter = String.fromCharCode(65 + Math.floor(Math.random() * 26));
        return randLetter + Date.now();
    }
});
	
	
	// Send message module 
$(document).on("click", "#send_message_app", function(e){
        e.preventDefault();
        var phone_numbers = $('textarea#applicant_numbers').val();
       var applicant_numbers = phone_numbers.replace(/ /g,'');
       var numbers_arr_validate = applicant_numbers.split(','); 
       if(/^[0-9,]+$/.test(applicant_numbers))
       {
        validate_numbers(numbers_arr_validate);
       }
       else
       {
        $("#applicant_numbers").parent().after("<div class='validation numbers_error' style='color:red;margin-bottom: 20px;'>Please enter valid comma seperated numbers</div>");
        return false;
        
    }
       var applicant_message = $('textarea#applicant_message').val();
       if(applicant_message == '')
       {
        $("#applicant_message").parent().after("<div class='validation numbers_error' style='color:red;margin-bottom: 20px;'>Please write something to send in message. </div>");
        return false;
    }
	 var applicant_message_encoded = encodeURIComponent(applicant_message);
  var query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='+applicant_numbers+'&message='+applicant_message_encoded+'&port=2&report=JSON&timeout=0';
        $.ajax({
                url: "{{ route('send_messages_applicants') }}",
                type: "post",
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                dataType: "json",
                data: {query_string:query_string},
                success: function (response) {
                    console.log(response);
                    // console.log(response.data);
                    $('textarea#applicant_numbers').val('');
                    $('textarea#applicant_message').val('');
                    OnSuccess(response.data);
                    toastr.success('Message sent to applicants successfuly!');
                },
            error: function (response) {
                toastr.error('Something went wrong please try again...'+ response.error);
            }
        });
    });

    function OnSuccess(data) {
        // console.log('data'+data);
            $.ajax({
                type: "POST",
                url: "{{ route('save_send_messages_applicants') }}",
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                data: { data : data},
                success: function (response) {
                    console.log('success : '+response.success_data+' , error numbers: '+response.error_data);
                    if(response.error_data.length > 0)
                    {
                        dataItems = response.error_data.map((item) => {
                                return item+' ';
                            })
                        $('#applicant_numbers').after('<span class="error text-info numbers_error">Message sent, thses numbers does not exist in database.('+dataItems+') </span>');
                        response.error_data='';
                    }
                    else
                    {
                        $('.numbers_error').hide();
                    }
                },
                failure: function (response) {
                   console.log('error: '+response.data);
                }
            });
        }
	
	function validate_numbers(phone_numbers)
    {

           

            $.each(phone_numbers, function(index, value){
                if(value.toString().length != 11)
            {
                $("#applicant_numbers").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please enter 11 digits numbers.</div>");
                return false;
            }
            
        });
    }

	function show_sm_chat() {
		$('.small_msg_modal').css('z-index', '1041');
		$('#exampleModal').css('z-index', '9999');
		$("#exampleModal").show();
	}


// $(document).ready(function(){
//         $('.notification_link').on('click',function(e){
//     e.preventDefault();
//     alert('here');
// });
// });




// $(function() {
//       $("a.notification_link").click(function(){
//         var applicant_value = $(this).attr('id');
//         let applicant_id = applicant_value.split(':');
//         let applicant_name = applicant_id[1].split('(');
//         let applicant_phone = applicant_name[1].split('-');
//         $("#applicant_chatbox_id").val(applicant_id[0]);
//     $("#applicant_chatbox_name").val(applicant_name[0]);
//     $("#applicant_chatbox_phone").val(applicant_phone[1]);
// getUserMessages(applicant_id[0]);
// show_sm_chat();

//             return false;
//       });
// });
	
	$(document).ready(function() {
            fetchNotifications(); // Initial fetch

            setInterval(fetchNotifications, 120000); // Fetch every 2 minutes

            function fetchNotifications() {
                $.ajax({
                    url: '{{ route('notifications.get') }}',
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        console.log('Received data:', data); // Debug: Log received data

                        var $notificationList = $('#notification_list');
                        $notificationList.empty(); // Clear existing notifications
                        var count = 0;

                        // Check if data is an array and has elements
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(function(info) {
                                var applicant_notify_name = info.applicant_name + ' (' + info.applicant_postcode + ')';
                                var listItem = `
                                    <li class="media">
                                            <div class="mr-3">
                                                <img src="https://bootdey.com/img/Content/avatar/avatar1.png" width="36" height="36" class="rounded-circle" alt="">
                                            </div>
                                            <div class="media-body">
                                                <div class="media-title">
                                                    <a href="#"  id="${applicant_notify_name}" data-id="${info.applicant_id}" class="notify_click">
                                                        <span class="font-weight-semibold" id="applicant_name">${applicant_notify_name}</span>
                                                        <span class="text-muted float-right font-size-sm  applicant_notifications badge badge-warning">${info.total}</span>
                                                    </a>
                                                </div>

                                                <span class="text-muted">${info.message.substring(0, 75)}${info.message.length > 75 ? '...' : ''}</span>
                                                <small class="text-warning">${info.created_at}</small>
                                            </div>
                                        </li>
                            `;
                                $notificationList.append(listItem);
                                count += parseInt(info.total); // Ensure info.total is parsed as integer
                            });
                        } else {
                            // If no data or data is not an array
                            $notificationList.append('<li class="notification-box text-center">No notifications found.</li>');
                        }

                        $('.total_notifications_new').text(count);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching notifications:', error);
                    }
                });
            }

            // Event delegation for click on .notify_click
            $(document).on('click', 'a.notify_click', function(e) {
                e.preventDefault();

                var applicant_id = $(this).data("id");
                var total_notification = parseInt($("#total_notify_count").text(), 10);
                var application_notification = parseInt($(this).find('.applicant_notifications').text(), 10);
                var new_notifications = total_notification - application_notification;

                $(".total_notifications_new").text(new_notifications);

                $(this).closest('li').remove(); // Remove the clicked notification

                // Additional logic to handle user messages
                let records_per_page = 1;
                let data_call_status = 'first';
                getUserMessages(applicant_id, records_per_page, data_call_status);
                show_sm_chat();
            });

            $(document).on('click','#markAll',function(e){
                $.ajax({
                    url: '{{ route('mark_msg_as_read') }}',
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        fetchNotifications();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching notifications:', error);
                    }
                });
            });
        });
	
	$(document).ready(function(){
		let page_num = 1;
		$('#scroll').scroll(function() {

		var pos=$(this).scrollTop();

			if(pos==0 || pos < 0)
			{
				page_num+=1;
				let data_call_status = 'rest';
				let applicant_id = $("#applicant_chatbox_id").val();
				console.log(applicant_id);
				getUserMessages(applicant_id,page_num,data_call_status);
			}

		});
	});
	
	$(document).ready(function(){
	   $('a.notify_click').on('click',function(){
			var applicant_id = $(this).data("id");
			let total_notification = $("#total_notify_count").text();
			let application_notification = $(this).find('.applicant_notifications').text();
			var new_notifications = total_notification -application_notification;
			$(".total_notifications_new").html(new_notifications);
			$("#notification_list").find(this).remove();
			  let records_per_page = 1;
			let data_call_status = 'first';
			getUserMessages(applicant_id,records_per_page,data_call_status);
			show_sm_chat();
	   }); 
	});
	
							 
$(document).on('click', '.sms_action_sent_cv', function(e){
    // $(this).prop('disabled', true);
    // clear_cv_non_nurse\
    // event.stopPropagation()
    e.preventDefault();
    var href = $(this).data('target');
  //  $(href).find(".cv_sent_request").attr("disabled", true);
	$(href).find(".cv_sent_request").attr("disabled", false);
    var applicantPhone = $(this).data('applicantphonejs');
    var applicantName = $(this).data('applicantnamejs');
    var applicantId = $(this).data('applicantidjs');
    var applicantUnit = $(this).data('applicantunitjs');
    $('#smsName').text(applicantName);
    $('#applicant_number_sms').val(applicantPhone);
    $('#non_nurse_modal_id').val(href);
    
    var applicant_message = 'Hi '+applicantName+' Congratulations! '+applicantUnit+' would like to invite you to their office for an in-person interview. Are you available next Tues 1-3pm or Fri 10am-12pm? Please do advise a suitable time. You can either reply to this message or contact us on the information given below Thank you for choosing Kingbury to represent you. Best regards, CRM TEAM T: 01494211220 E: crm@kingsburypersonnel.com';
    $("#sent_cv_details_non_nurse_for_sms").val(applicant_message);

    $('#sent_cv_non_nurse_sms').modal('show');
});							 

	function OnSuccessSaveReqSms(data) {
		$.ajax({
			type: "POST",
			url: "{{ route('save_req_send_sms') }}",
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			data: { data : data},
			success: function (response) {
				if(response.error_data.length > 0)
				{

					return 'error';
				}
				else
				{
					return 'success';
				}
			},
			failure: function (response) {
				console.log('error: '+response.data);
			}
		});
	}

	// Send request sms module 
	$(document).on("click", "#non_nurse_req_sms", function(event){
		event.stopPropagation();
			var applicant_message = $('textarea#sent_cv_details_non_nurse_for_sms').val();
			applicant_message = $.trim(applicant_message);
		   var applicant_number = $('#applicant_number_sms').val();
		   var nonNurseModalId = $('#non_nurse_modal_id').val();
		   if(applicant_message == '')
		   {
			alert('Please enter message...');
		}
	  var query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='+applicant_number+'&message='+applicant_message+'&port=2&report=JSON&timeout=0';
			$.ajax({
					url: "{{ route('send_non_nurse_req_sms') }}",
					type: "post",
					headers: {
								'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
							},
					dataType: "json",
					data: {query_string:query_string},
					success: function (response) {
	$(nonNurseModalId).find(".cv_sent_request").attr("disabled", false);
						OnSuccessSaveReqSms(response.data);
						toastr.success('Request sms sent successfuly!');

		$('#sent_cv_non_nurse_sms').modal('hide');

					},
				error: function (response) {
					toastr.error('Something went wrong please try again...');
				}
			});
		});

		$(document).ready(function(){
		$("#crmBtnClose").click(function() {
			$("#crmExampleModal").hide();
		$(".small_msg_modal :input").prop("disabled", false); 
		// $(".small_msg_modal :input").prop("disabled", false);
		});

	});
	
	$(document).on('click', '.crm_chat', function(e){
		e.preventDefault();
		var app_id = $(this).data('id');
		 $(".modal-body #applicant_id").val(app_id);
	});

	function crmGetUserMessages(applicant_id,records_per_page,data_call_status)
	{
		$.ajax({

			url: "{{ route('get-crm-app-messages') }}",
			type: "GET",
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			data: {applicant_id:applicant_id,records_per_page:records_per_page},
			success: function (response) {
				var message='';
				if(response.data!='')
				{
					$('#crm_msg_avatar_name').text(response.data[0].applicant_name);
					$.each(response.data.reverse(), function (index, itemData) {
						var timeDate = '';

						var tdate = new Date();
						var dd = tdate.getDate(); //yields day
						var MM = tdate.getMonth(); //yields month
						var yyyy = tdate.getFullYear(); //yields year
						var currentDate= yyyy + "-" +( MM+1) + "-" + dd;
						//    alert(currentDate+' and db date is : '+itemData.date);
						var hour = parseInt(itemData.time.split(":")[0]) % 12;
						var formatedTime = (hour == 0 ? "12": hour ) + ":" + itemData.time.split(":")[1] + " " + (parseInt(parseInt(itemData.time.split(":")[0]) / 12) < 1 ? "am" : "pm");
						var formatedDate = moment(itemData.date).format('DD-MMM-YYYY');

						if(itemData.date==currentDate)
						{
							timeDate= itemData.time+', Today';
						}
						else
						{
							timeDate= formatedTime+', '+itemData.date;

						}
						// console.log('status: '+itemData.is_read);
						let is_read_status = (itemData.is_read=='0')?'Unseen':'Seen';
						if(itemData.status=='outgoing')
						{

							if(itemData.user_name=='super_admin')
							{
								message+=' <li class="clearfix user_msg_space" style="margin-top:20px;">'+
									'<div class="message-data text-right">'+
									'<span class="message-data-time">'+formatedDate+' '+formatedTime+'</span>'+
									'<span class="message-data-time" style="font-weight: bold">'+itemData.name+'</span>'+
									'<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">'+
									' </div>'+
									'<div class="message other-message float-right">'+itemData.message+'</div>'+
									'</li>';
							}
							else
							{
								message+=' <li class="clearfix user_msg_space" style="margin-top:20px;">'+
									'<div class="message-data text-right">'+
									'<span class="message-data-time">'+formatedDate+' '+formatedTime+'</span>'+
									'<span class="message-data-time" style="font-weight: bold">'+itemData.name+'</span>'+
									'<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">'+
									' </div>'+
									'<div class="message other-message float-right">'+itemData.message+'</div>'+
									'</li>';
							}

						}
						else
						{
							message+='<li class="clearfix user_msg_space" style="margin-top:20px;">'+
								'<div class="message-data">'+
								'<span class="message-data-time"><img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="avatar">'+
								'<span class="message-data-time"  style="margin-right: 10px;font-weight: bold">'+itemData.applicant_name+'</span>'+formatedDate+' '+formatedTime+'<small class="is_read_style badge badge-danger" style="margin-left: 15px;">'+is_read_status+'</small></span>'+
								' </div>'+
								'<div class="message my-message">'+itemData.message+'</div>'+
								'</li>';
						}

					});
					if(data_call_status=='first')
					{
						$(".chat-history").html("");
						$('.chat-history').append(message);
						$("#crm_chat_scroll").animate({ scrollTop: $("#crm_chat_scroll")[0].scrollHeight }, 1000);
					}
					else
					{
						$('.chat-history').prepend(message);
					}
				}
				else{

				}


			},
			error: function (response) {
				console.log('error');
			}
		});
	}
	function show_crm_chat(){
		$('.small_msg_modal').css('z-index','1041');
		$(".small_msg_modal :input").prop("disabled", true);
		$('#crmExampleModal').css('z-index','1055');
		$(".modal-backdrop").remove();
		$("#crmExampleModal").show();
	}

	function crmAjaxCall(applicant_chatbox_id,msg_phone,msg_time,applicant_msg,msg_send_id)
    {
        var msgType = 'crm';
        $.ajax({
                url: "{{ route('store-user-message') }}",
                type: "post",
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                data: { applicant_chatbox_id:applicant_chatbox_id, msg_phone:msg_phone, msg_time:msg_time, applicant_msg:applicant_msg, msg_send_id:msg_send_id, msgType:msgType },
                success: function (response) {
                    console.log('ajax call response success: '+response.msg);

                        let message='<li class="clearfix user_msg_space">'+
                                                '<div class="message-data text-right">'+
                                                    '<span class="message-data-time">'+formatedDate+' '+formatedTime+'</span>'+
                                         	'<span class="message-data-time" style="font-weight: bold">'+response.user_name+'</span>'+
                                                        '<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="avatar">'+
                                                                    ' </div>'+
                                                    '<div class="message other-message float-right">'+applicant_msg+'</div>'+
                                                        '</li>';
                        $('.chat-history').append(message);
					const element = $('#crm_chat_scroll');

                        element.animate({
                            scrollTop: element.prop("scrollHeight")
                        }, 500);
                        $('#crmMsgText').val('');
                        // $("#crm_toaster_success").toast({ delay: 5000 });
                        // $('#crm_toaster_success').toast('show');
                        // return 'success';

                            toastr.success('Success! Message sent and updated successfuly.');
                       

                        
            },
        error: function (response) {
            toastr.error('Error! Applicant message can not be stored in database...');

        }
    });



    }
$(document).ready(function(){
    function randstrcrm()
    {
        var randLetter = String.fromCharCode(65 + Math.floor(Math.random() * 26));
        return randLetter + Date.now();
    }
    
$(document).on("click", "#crmSendMsg", function(event){
    event.preventDefault();
    var applicant_chatbox_id=$("#crm_applicant_chatbox_id").val();
	
	 if(applicant_chatbox_id=='')
	 {
	   
	    applicant_chatbox_id=$('#notify_applicant_id').attr('value');
	    }
    var applicant_chatbox_name=$("#crm_applicant_chatbox_name").val();
	var applicant_chatbox_phone=$("#crm_applicant_chatbox_phone").val();
    //    alert(applicant_chatbox_id+' and '+applicant_chatbox_name+' and '+applicant_chatbox_phone);
	// if(applicant_chatbox_phone=='')
	// {
	//    applicant_chatbox_phone=$('#notify_phone').attr('value');
	//  }
    var msg_send_id = randstrcrm();
    var applicant_msg = $('#crmMsgText').val();
    var test_phone='07597019065';
  var query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='+test_phone+'&message='+applicant_msg+'&port=2&report=JSON&timeout=0';
	console.log('query_string: '+query_string);
        $.ajax({
                url: "{{ route('send-sms-ajax') }}",
                type: "post",
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
				dataType: "json",
                data: {query_string:query_string},
                success: function (response) {
					console.log(response);
					console.log('time: '+response.time+' and phone: '+response.phonenumber);
					if(response.report=='not ready')
					{
						alert('Sms gateway not responding, plz try again...');
						console.log(response);
					}
					else
					{
						console.log('ajaxCall :'+response.phonenumber+' and '+response.time+' and '+applicant_msg+' and '+msg_send_id);
						 crmAjaxCall(applicant_chatbox_id,response.phonenumber,response.time,applicant_msg,msg_send_id);
                       
					}   
            },
        error: function (response) {
            toastr.error('Error! Applicant message can not be sent...');
        }
    });
    });

$(document).on("click", ".crm_chat", function(e){    
    e.preventDefault();
    var applicant_phone = $(this).attr("data-applicantPhoneJs"); 
    var applicant_id = $(this).attr("data-applicantIdJs"); 
    var applicant_name = $(this).attr("data-applicantNameJs"); 
    var records_per_page = 1;
    var data_call_status = 'first';
    $('#crm_applicant_chatbox_id').val(applicant_id);
    $('#crm_applicant_chatbox_name').val(applicant_name);
    $('#crm_applicant_chatbox_phone').val(applicant_phone);
    // console.log('applicant id '+applicant_id+' applicant_phone '+applicant_phone+' applicant_name '+applicant_name);
    crmGetUserMessages(applicant_id,records_per_page,data_call_status);
    show_crm_chat();
});
});
	

</script>

@yield('js_file')
@yield('script')

</body>
</html>
