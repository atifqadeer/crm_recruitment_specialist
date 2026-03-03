/*** global variables */
var crm_table = '';
var table = 'crm_sent_cv_sample';
var route = 'crm-sent-cv';

var columns = [
    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
    { "data":"name", "name": "name", "orderable": false, "searchable": false },
	{
		"data": "applicant_name",
		"name": "applicants.applicant_name",
		"render": function(data, type, row, meta) {
			if (type === 'display' || type === 'filter') {
				return data
					.toLowerCase()  // Convert the entire string to lowercase
					.split(' ')     // Split the string into words
					.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
					.join(' ');    // Join the words back into a single string
			}
			return data;
		}
	},
    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
    { "data":"office_name", "name": "offices.office_name" },
    { "data":"unit_name", "name": "units.unit_name" },
    { "data":"postcode", "name": "sales.postcode" },
    { "data":"crm_note", "name": "crm_note" },
    { "data":"action", "name": "action", "orderable": false, "searchable": false }
];

function crm_tab_cvs(table, route, columns) {
    $.fn.dataTable.ext.errMode = 'throw';
    if ($.fn.DataTable.isDataTable("#"+table)) {
        $('#'+table).DataTable().clear().destroy();
    }
    crm_table = $('#'+table).DataTable({
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": route,
        "columns": columns
    });
}

$(".searchSchedule").keyup(function(){
    var searchVal= $('.searchSchedule').val();
    if(searchVal=='')
    {
        table = 'crm_confirmation_cv_sample';
                route = 'crm-confirmation';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"interview_schedule", "name": "interviews.schedule_date" },
                   // { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details", "searchable": false},
                    { "data":"action", "name": "action", "orderable": false, "searchable": false },
                    {data: "schedule_search", name: "interviews.schedule_date","searchable": true}

                ];
                crm_tab_cvs(table, route, columns);
                crm_table.draw();
    }
    else
    {

        $('#crm_confirmation_cv_sample').dataTable().fnDestroy();
    var table = $('#crm_confirmation_cv_sample').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "crm_confirmation_search",
          data: function (d) {
                d.email = $('.searchSchedule').val()
                // d.search = $('input[type="search"]').val()
            }
        },
        columns: [
            { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"interview_schedule", "name": "interviews.schedule_date" },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details", "searchable": false},
                    { "data":"action", "name": "action", "orderable": false, "searchable": false },
                    {"data": "schedule_search", "name": "interviews.schedule_date","searchable": true}
        ],
        columnDefs: [{
    "defaultContent": "-",
    "targets": "_all"
  }]
    });
        table.draw();


    }
    // $("#crm_confirmation_cv_sample").dataTable().Destroy();
    // $('#crm_confirmation_cv_sample').dataTable().fnClearTable();
    
   
  
  });

 $(document).ready(function () {
    // Handle Reject Button Click
    $(document).on("click", ".reject-btn", function () {
      const applicantId = $(this).data("applicant-id");
      const saleId = $(this).data("sale-id");

      // Populate hidden inputs in the child modal
      $("#reject_email_modal #applicant_id").val(applicantId);
      $("#reject_email_modal #sale_id").val(saleId);
    });

    // Handle Submit Email Button Click
    $(document).on("click", "#submit_email_btn", function (event) {
      event.preventDefault();

	const emailAddress = $("#email_address").val().trim();
      const emailSubject = $("#email_subject").val().trim();
      const emailBody = $("#email_body").val().trim();
      const applicantId = $("#reject_email_modal #applicant_id").val();
      const saleId = $("#reject_email_modal #sale_id").val();

      console.log("Subject:", emailSubject, "Body:", emailBody);
      console.log("Applicant ID:", applicantId, "Sale ID:", saleId);

      if (emailSubject && emailBody && applicantId && saleId) {
        // Construct the parent form's ID
        const formId = `#request_cv_form${applicantId}-${saleId}`;
        const formElement = $(formId);

        // Ensure the form exists
        if (formElement.length === 0) {
          console.error(`Form with ID '${formId}' not found.`);
          alert("Parent form not found. Please try again.");
          return;
        }

        // Remove existing hidden inputs to prevent duplicates
		formElement.find('input[name="email_address"]').remove();
        formElement.find('input[name="email_subject"]').remove();
        formElement.find('input[name="email_body"]').remove();

		  // Append hidden input for email_address
        $("<input>")
          .attr({
            type: "hidden",
            name: "email_address",
            value: emailAddress,
          })
          .appendTo(formElement);
        // Append hidden input for email_subject
        $("<input>")
          .attr({
            type: "hidden",
            name: "email_subject",
            value: emailSubject,
          })
          .appendTo(formElement);
        // Append hidden input for email_subject
        $("<input>")
          .attr({
            type: "hidden",
            name: "request_reject",
            value: "request_reject",
          })
          .appendTo(formElement);

        // Append hidden input for email_body
        $("<input>")
          .attr({
            type: "hidden",
            name: "email_body",
            value: emailBody,
          })
          .appendTo(formElement);

        // Use .one() to bind the event handler only once
        $("#reject_email_modal").one("hidden.bs.modal", function () {
          console.log("Modal hidden. Submitting form.");
          formElement.submit();
        });

        // Hide the child modal
        $("#reject_email_modal").modal("hide");
      } else {
        alert("Please fill out both the subject and body of the email.");
      }
    });
  });

$(document).ready(function() {
    crm_tab_cvs(table, route, columns);

    /*** shows Reject button in Sent CV popup */
    $(document).on('change', '.crm_select_reason', function () {
        $('.reject_btn').css("display","block");
    });

    /*** Year selector */
    $(document).on('focus',".pickadate-year", function(){
        $(this).pickadate({
            selectYears: 4
        });
    });

    /*** Time picker */
    $(document).on('focus',".time_picker", function(){
        $('#'+$(this).attr('id')).AnyTime_picker({
            format: '%H:%i'
        });
    });

    $(document).on('click', '.crm-refresh', function () {
        crm_table.draw();
    });

    $(document).on('shown.bs.tab', '.nav-tabs a', function (event) {
        var datatable_name = $(this).data('datatable_name');
		console.log(datatable_name);
        var tab_href = $(this).attr('href').substr(1);

        switch (tab_href) {
            case 'CV_sent':
                table = 'crm_sent_cv_sample';
                route = 'crm-sent-cv';
                columns = [
                    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
                    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                   // { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_note" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
				 case "CV_open":
					table = "crm_open_cv_sample";
					route = "crm-open-cv";
					columns = [
					  {
                            data: "updated_at", // Use the exact key from the data
                            name: "revert_stages.updated_at",
                        },
                        {
                            data: "updated_time", // Use the same key for updated_time
                            name: "revert_stages.updated_at",
                            orderable: false,
                        },
					  {
						data: "name",
						name: "name",
						orderable: false,
						searchable: false,
					  },
					  {
						data: "applicant_name",
						name: "applicants.applicant_name",
						render: function(data, type, row, meta) {
						  if (type === "display" || type === "filter") {
							return data
							  .toLowerCase() // Convert the entire string to lowercase
							  .split(" ") // Split the string into words
							  .map((word) => word.charAt(0).toUpperCase() + word.slice(1)) // Capitalize the first letter of each word
							  .join(" "); // Join the words back into a single string
						  }
						  return data;
						},
					  },
					  {
						data: "applicant_job_title",
						name: "applicants.applicant_job_title",
					  },
					  {
						data: "applicant_postcode",
						name: "applicants.applicant_postcode",
					  },
					  {
						data: "job_details",
						name: "job_details",
						orderable: false,
						searchable: false,
					  },
					  {
						data: "office_name",
						name: "offices.office_name",
					  },
					  {
						data: "unit_name",
						name: "units.unit_name",
					  },
					  {
						data: "postcode",
						name: "sales.postcode",
					  },
					  {
                            data: "revert_notes", // Use the exact key from the data
                            name: "revert_stages.notes",
                            orderable: false,
                            render: function (data, type, row) {
                                if (type === "display" || type === "filter") {
                                    var div = document.createElement("div");
                                    div.innerHTML = data;
                                    return div.textContent || div.innerText || "";
                                }
                                return data;
                            },
                        },
					  {
						data: "action",
						name: "action",
						orderable: false,
						searchable: false,
					  },
					];
					crm_tab_cvs(table, route, columns);
					break;
				case 'CV_sent_nurse':
                table = 'crm_sent_cv_nurse_sample';
                route = 'crm-sent-cv-nurse';
                columns = [
                    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
                    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_note" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
                case 'CV_sent_nonnurse':
                table = 'crm_sent_cv_nonnurse_sample';
                route = 'crm-sent-cv-nonnurse';
                columns = [
                    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
                    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                 //   { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_note" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'reject_CV':
                table = datatable_name;
                route = 'crm-reject-cv';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                //    { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'request':
                table = datatable_name;
                route = 'crm-request';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
				case 'request_nurse':
                table = datatable_name;
                route = 'crm-request-nurse';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                   // { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
                case 'request_nonnurse':
                table = datatable_name;
                route = 'crm-request-nonnurse';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                   // { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'rejectByRequest':
                table = datatable_name;
                route = 'crm-reject-by-request';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'confirmation':
                table = 'crm_confirmation_cv_sample';
                route = 'crm-confirmation';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"interview_schedule", "name": "interviews.schedule_date" },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false },
                    {"data": "schedule_search", "name": "interviews.schedule_date","searchable": true}
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'rebook':
                table = 'crm_rebook_cv_sample';
                route = 'crm-rebook';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'pre-start':
                table = 'crm_pre_start_cv_sample';
                route = 'crm-pre-start-date';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'declined':
                table = 'crm_declined_cv_sample';
                route = 'crm-declined';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'not_attended':
                table = 'crm_not_attended_cv_sample';
                route = 'crm-not-attended';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'start':
                table = 'crm_start_date_cv_sample';
                route = 'crm-start-date';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'start_date_hold':
                table = 'crm_start_date_hold_cv_sample';
                route = 'crm-start-date-hold';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
					{ "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'invoice_sent':
                table = 'crm_invoice_cv_sample';
                route = 'crm-invoice';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
					{ "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
				
				case 'invoice_final_sent':
                table = 'crm_invoice_final_sent_cv_sample';
                route = 'crm-invoice-final-sent';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                   // { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
					{ "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'dispute':
                table = 'crm_dispute_cv_sample';
                route = 'crm-dispute';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'invoice_pending':
                table = 'crm_paid_cv_sample';
                route = 'crm-paid';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                 //   { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
		  case 'request_no_job':
                table = 'crm_request_no_job_cv_sample';
                route = 'crm-request-chef';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                   // { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
           case 'CV_sent_no_job':
                table = 'crm_sent_cv_no_job_sample';
                route = 'crm-sent-cv-chef';
                columns = [
                    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
                    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                  //  { "data":"applicant_name", "name": "applicants.applicant_name" },
					{
						"data": "applicant_name",
						"name": "applicants.applicant_name",
						"render": function(data, type, row, meta) {
							if (type === 'display' || type === 'filter') {
								return data
									.toLowerCase()  // Convert the entire string to lowercase
									.split(' ')     // Split the string into words
									.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
									.join(' ');    // Join the words back into a single string
							}
							return data;
						}
					},
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
					 { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_note" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            default:
        }
    });

    /*** sent cv tab actions */
    $(document).on('click', '.sent_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $sent_cv_form = $('#sent_cv_form'+app_sale);
        var $sent_cv_alert = $('#sent_cv_alert' + app_sale);
        var details = $.trim($("#sent_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                // url: "{{ route('sentCvAction') }}",
                url: "sent-cv-action",
                type: "POST",
                data: $sent_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $sent_cv_alert.html(response);
                    setTimeout(function () {
                        $('#clear_cv' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $sent_cv_alert.html(raw_html);
                }
            });
        } else {
            $sent_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $sent_cv_form.trigger('reset');
        setTimeout(function () {
            $sent_cv_alert.html('');
        }, 2000);
        return false;
    });
	
	    $(document).on('click', '.sent_no_job_cv_submit', function (event) {
      // alert('testing no job');

        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        // var model_name = $(this).find('(input[name="cv_modal_name"])');
    //    var model_name= $(".model_name").val();
       var model_name=$(this).closest('.small_msg_modal').find('input[name="cv_modal_name"]').val();
    // var model_name=$(this).find('.model_name').val();
        var sent_cv_form = '';
        var sent_cv_alert = '';
        var details = '';
        if(model_name == 'sent_cv')
        {
             sent_cv_form = $('#sent_cv_form'+app_sale);
             sent_cv_alert = $('#sent_cv_alert' + app_sale);
             details = $.trim($("#sent_cv_details" + app_sale).val());
        }
        else if(model_name == 'sent_cv_nurse')
        {

            var job_nurse_id=$(this).closest('.small_msg_modal').find("#nurse_job_hidden_id").val();
            var applicant_nurse_id=$(this).closest('.small_msg_modal').find("#nurse_applicant_hidden_id").val();
            console.log(job_nurse_id +'and '+applicant_nurse_id);
             sent_cv_form = $('#sent_cv_form_nurse'+applicant_nurse_id+'-'+job_nurse_id);
             sent_cv_alert = $('#sent_cv_alert_nurse' +applicant_nurse_id+'-'+job_nurse_id);
             details = $.trim($("#sent_cv_details_nurse"+applicant_nurse_id+'-'+job_nurse_id).val());
             console.log('sent cv Nurse '+sent_cv_form+' and , '+sent_cv_alert+' and ,'+details);


        }
        else
        {
            var job_nurse_id=$(this).closest('.small_msg_modal').find("#non_nurse_job_hidden_id").val();
            var applicant_nurse_id=$(this).closest('.small_msg_modal').find("#non_nurse_applicant_hidden_id").val();
            sent_cv_form = $('#sent_cv_form_non_nurse'+applicant_nurse_id+'-'+job_nurse_id);
             sent_cv_alert = $('#sent_cv_alert_non_nurse' +applicant_nurse_id+'-'+job_nurse_id);
             details = $.trim($("#sent_cv_details_non_nurse" +applicant_nurse_id+'-'+job_nurse_id).val());
             console.log('sent cv Nurse '+sent_cv_form+' and , '+sent_cv_alert+' and ,'+details);

        }
        if (details) {
            $.ajax({
                // url: "{{ route('sentCvAction') }}",
                // url: "sent-cv-action",
                url: "sent-cv-no-job-action",
                type: "POST",
                data: sent_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    console.log(response);
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    sent_cv_alert.html(response);
                    setTimeout(function () {
                        $('#clear_cv' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    sent_cv_alert.html(raw_html);
                }
            });
        } else {
            sent_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        sent_cv_form.trigger('reset');
        setTimeout(function () {
            sent_cv_alert.html('');
        }, 2000);
        return false;
    });

	$(document).on("click", "[id^='submit_requested_applicant_email_btn_']", function(event) {
		event.preventDefault();

		// Extract the applicantId and saleId from the button's ID
		const idParts = $(this).attr('id').split('_');
		const applicantId = idParts[idParts.length - 2]; // Extracts applicant ID from the button ID
		const saleId = idParts[idParts.length - 1]; // Extracts sale ID from the button ID

		const emailAddress = $("#email_address_requested_" + applicantId + "_" + saleId).val().trim();
		const emailSubject = $("#email_subject_requested_" + applicantId + "_" + saleId).val().trim();
		const emailBody = $("#email_body_requested_" + applicantId + "_" + saleId).val().trim();

		// Validate the inputs
		if (!emailAddress || !emailSubject || !emailBody) {
			alert("Please fill out all the fields before sending the email.");
			return;
		}

	  // AJAX call to send email
	  $.ajax({
		url: "/send-applicant-email", // Your server endpoint
		method: "POST",
		contentType: "application/json",
		headers: {
		  // Add the CSRF token to the headers
		  "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
		},
		data: JSON.stringify({
		  emailAddress: emailAddress,
		  emailSubject: emailSubject,
		  emailBody: emailBody,
		  applicantId: applicantId,
		  saleId: saleId,
		  title: "Request Configuration Email", // Adjust as needed
		}),
		success: function(response) {
		  console.log(response);
		  if (response.success) {
			alert("Email sent successfully!");
			// Optionally, close the modal or reset fields
			$("#send_applicant_email_request_modal" + applicantId + "-" + saleId).modal("hide");
		  } else {
			alert("Failed to send email: " + response.message);
		  }
		},
		error: function(xhr, status, error) {
		  alert("An error occurred while sending the email. Please try again.");
		  console.error("Error details:", error);
		  console.error("Status:", status);
		  console.error("XHR object:", xhr);
		},
	  });
	});
	
	$(document).on("click", "[id^='submit_applicant_email_attended_btn_']", function(event) {
		event.preventDefault();

		// Extract the applicantId and saleId from the button's ID
		const idParts = $(this).attr('id').split('_');
		const applicantId = idParts[idParts.length - 2]; // Extracts applicant ID from the button ID
		const saleId = idParts[idParts.length - 1]; // Extracts sale ID from the button ID

		const emailAddress = $("#email_address_attended_" + applicantId + "_" + saleId).val().trim();
		const emailSubject = $("#email_subject_attended_" + applicantId + "_" + saleId).val().trim();
		const emailBody = $("#email_body_attended_" + applicantId + "_" + saleId).val().trim();

		// Validate the inputs
		if (!emailAddress || !emailSubject || !emailBody) {
			alert("Please fill out all the fields before sending the email.");
			return;
		}

        // AJAX call to send email
        $.ajax({
            url: "/send-applicant-email", // Your server endpoint
            method: "POST",
            contentType: "application/json",
            headers: {
            // Add the CSRF token to the headers
            "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
            },
            data: JSON.stringify({
            emailAddress: emailAddress,
            emailSubject: emailSubject,
            emailBody: emailBody,
            applicantId: applicantId,
            saleId: saleId,
            title: "Attended Applicant Email", // Adjust as needed
            }),
            success: function(response) {
            console.log(response);
             if (response.success) {
                alert("Email sent successfully!");

                // Hide the modal
                $("#send_applicant_email_attended_modal" + applicantId + "-" + saleId).modal("hide");

                // Reset the form fields inside the modal
                $("#email_address_attended_" + applicantId + "_" + saleId).val('');
                $("#email_subject_attended_" + applicantId + "_" + saleId).val('');
                $("#email_body_attended_" + applicantId + "_" + saleId).val('');

                // Reset Summernote editor (clear the content)
                $('#email_body_attended_' + applicantId + '_' + saleId).summernote('reset'); // or use .val('') if not using Summernote

            } else {
                alert("Failed to send email: " + response.message);
            }
            },
            error: function(xhr, status, error) {
            alert("An error occurred while sending the email. Please try again.");
            console.error("Error details:", error);
            console.error("Status:", status);
            console.error("XHR object:", xhr);
            },
        });
	});


    /*** rejected cv tab */
    $(document).on('click', '.rejected_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_sent_cv_form = $('#revert_sent_cv_form'+app_sale);
        var $revert_sent_cv_alert = $('#revert_sent_cv_alert' + app_sale);
        var details = $.trim($("#revert_sent_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "revert-sent-cv-action",
                type: "POST",
                data: $revert_sent_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_sent_cv_alert.html(response);
                    setTimeout(function () {
                        $('#revert_sent_cvs' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_sent_cv_alert.html(raw_html);
                }
            }).then(function (data) {
                setTimeout(function () {
                    $revert_sent_cv_form.trigger('reset');
                    $revert_sent_cv_alert.html('');
                }, 2000);
            });
        } else {
            $revert_sent_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** Request tab actions */
    $(document).on('click', '.schedule_interview_submit', function (event) {
        event.preventDefault();
        var app_sale = $(this).data('app_sale');
        var $schedule_interview_form = $('#schedule_interview_form'+app_sale);
        var $schedule_interview_alert = $('#schedule_interview_alert' + app_sale);
        var schedule_date = $.trim($("#schedule_date" + app_sale).val());
        var schedule_time = $.trim($("#schedule_time" + app_sale).val());
        if (schedule_date && schedule_time) {
            $.ajax({
                url: "schedule-interview",
                type: "POST",
                data: $schedule_interview_form.serialize(),
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $schedule_interview_alert.html(response);
                    setTimeout(function () {
                        $('#schedule_interview' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $schedule_interview_alert.html(raw_html);
                }
            });
        } else {
            $schedule_interview_alert.html('<p class="text-danger">Kindly Provide Date and Time</p>');
        }
        $schedule_interview_form.trigger('reset');
        setTimeout(function () {
            $schedule_interview_alert.html('');
        }, 2000);
        return false;
    });

    $(document).on('click', '.request_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $request_cv_form = $('#request_cv_form'+app_sale);
        var $request_cv_alert = $('#request_cv_alert' + app_sale);
        var details = $.trim($("#request_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "request-action",
                type: "POST",
                data: $request_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $request_cv_alert.html(response);
                    setTimeout(function () {
                        $('#confirm_cv' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $request_cv_alert.html(raw_html);
                }
            });
        } else {
            $request_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $request_cv_form.trigger('reset');
        setTimeout(function () {
            $request_cv_alert.html('');
        }, 2000);
        return false;
    });

    /*** rejected by request tab actions */
    $(document).on('click', '.revert_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_cv_form = $('#revert_cv_form'+app_sale);
        var $revert_cv_alert = $('#revert_cv_alert' + app_sale);
        var details = $.trim($("#revert_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "reject-by-request-action",
                type: "POST",
                data: $revert_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_cv_alert.html(response);
                    setTimeout(function () {
                        $('#revert' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_cv_alert.html(raw_html);
                }
            }).then(function (response) {
                $revert_cv_form.trigger('reset');
                setTimeout(function () {
                    $revert_cv_alert.html('');
                }, 2000);
            });
        } else {
            $revert_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** confirmation tab actions */
    $(document).on('click', '.after_interview_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $after_interview_form = $('#after_interview_form'+app_sale);
        var $after_interview_alert = $('#after_interview_alert' + app_sale);
        var details = $.trim($("#after_interview_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "after-interview-action",
                type: "POST",
                data: $after_interview_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $after_interview_alert.html(response);
                    setTimeout(function () {
                        $('#after_interview' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $after_interview_alert.html(raw_html);
                }
            });
        } else {
            $after_interview_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $after_interview_form.trigger('reset');
        setTimeout(function () {
            $after_interview_alert.html('');
        }, 2000);
        return false;
    });

    /*** rebook tab actions */
    $(document).on('click', '.rebook_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $rebook_form = $('#rebook_form'+app_sale);
        var $rebook_alert = $('#rebook_alert' + app_sale);
        var details = $.trim($("#rebook_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "rebook-action",
                type: "POST",
                data: $rebook_form.serialize() + '&form_action' + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $rebook_alert.html(response);
                    setTimeout(function () {
                        $('#rebook' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $rebook_alert.html(raw_html);
                }
            });
        } else {
            $rebook_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $rebook_form.trigger('reset');
        setTimeout(function () {
            $rebook_alert.html('');
        }, 2000);
        return false;
    });

    /*** attended to pre-start date tab actions */
    $(document).on('click', '.accept_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $accept_form = $('#accept_form'+app_sale);
        var $accept_alert = $('#accept_alert' + app_sale);
        var details = $.trim($("#accept_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "attended-to-pre-start-action",
                type: "POST",
                data: $accept_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $accept_alert.html(response);
                    setTimeout(function () {
                        $('#accept' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $accept_alert.html(raw_html);
                }
            });
        } else {
            $accept_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $accept_form.trigger('reset');
        setTimeout(function () {
            $accept_alert.html('');
        }, 2000);
        return false;
    });

    /*** declined tab actions */
    $(document).on('click', '.declined_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $declined_revert_form = $('#declined_revert_form'+app_sale);
        var $declined_revert_alert = $('#declined_revert_alert' + app_sale);
        var details = $.trim($("#declined_revert_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "declined-action",
                type: "POST",
                data: $declined_revert_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $declined_revert_alert.html(response);
                    setTimeout(function () {
                        $('#declined_revert' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $declined_revert_alert.html(raw_html);
                }
            });
        } else {
            $declined_revert_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $declined_revert_form.trigger('reset');
        setTimeout(function () {
            $declined_revert_alert.html('');
        }, 2000);
        return false;
    });

    /*** not attended tab actions */
    $(document).on('click', '.revert_attended_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_attended_form = $('#revert_attended_form'+app_sale);
        var $revert_attended_alert = $('#revert_attended_alert' + app_sale);
        var details = $.trim($("#revert_attended_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "not-attended-action",
                type: "POST",
                data: $revert_attended_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_attended_alert.html(response);
                    setTimeout(function () {
                        $('#revert_attended' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_attended_alert.html(raw_html);
                }
            }).then(function (response) {
                $revert_attended_form.trigger('reset');
                setTimeout(function () {
                    $revert_attended_alert.html('');
                }, 2000);
            });
        } else {
            $revert_attended_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** start date tab actions */
    $(document).on('click', '.start_date_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $start_date_form = $('#start_date_form'+app_sale);
        var $start_date_alert = $('#start_date_alert' + app_sale);
        var details = $.trim($("#start_date_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "start-date-action",
                type: "POST",
                data: $start_date_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $start_date_alert.html(response);
                    setTimeout(function () {
                        $('#start_date' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $start_date_alert.html(raw_html);
                }
            });
        } else {
            $start_date_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $start_date_form.trigger('reset');
        setTimeout(function () {
            $start_date_alert.html('');
        }, 2000);
        return false;
    });

    /*** start date hold tab actions */
    $(document).on('click', '.start_date_hold_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $start_date_hold_form = $('#start_date_hold_form'+app_sale);
        var $start_date_hold_alert = $('#start_date_hold_alert' + app_sale);
        var details = $.trim($("#start_date_hold_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "start-date-hold-action",
                type: "POST",
                data: $start_date_hold_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $start_date_hold_alert.html(response);
                    setTimeout(function () {
                        $('#start_date_hold' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $start_date_hold_alert.html(raw_html);
                }
            }).then(function (response) {
                $start_date_hold_form.trigger('reset');
                setTimeout(function () {
                    $start_date_hold_alert.html('');
                }, 2000);
            });
        } else {
            $start_date_hold_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** invoice tab actions */
    $(document).on('click', '.invoice_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $invoice_form = $('#invoice_form'+app_sale);
        var $invoice_alert = $('#invoice_alert' + app_sale);
        var details = $.trim($("#invoice_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "invoice-action",
                type: "POST",
                data: $invoice_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $invoice_alert.html(response);
                    setTimeout(function () {
                        $('#invoice' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $invoice_alert.html(raw_html);
                }
            });
        } else {
            $invoice_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $invoice_form.trigger('reset');
        setTimeout(function () {
            $invoice_alert.html('');
        }, 2000);
        return false;
    });
	
	$(document).on('click', '.invoice_submit_sent', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale_sent');
        var $invoice_form = $('#invoice_form_sent'+app_sale);
        var $invoice_alert = $('#invoice_alert_sent' + app_sale);
        var details = $.trim($("#invoice_details_sent" + app_sale).val());
        if (details) {
            $.ajax({
                url: "invoice-action-sent",
                type: "POST",
                data: $invoice_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $invoice_alert.html(response);
                    setTimeout(function () {
                        $('#invoice_sent' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $invoice_alert.html(raw_html);
                }
            });
        } else {
            $invoice_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $invoice_form.trigger('reset');
        setTimeout(function () {
            $invoice_alert.html('');
        }, 2000);
        return false;
    });

    /*** dispute tab actions */
    $(document).on('click', '.revert_invoice_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_invoice_form = $('#revert_invoice_form'+app_sale);
        var $revert_invoice_alert = $('#revert_invoice_alert' + app_sale);
        var details = $.trim($("#revert_invoice_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "dispute-action",
                type: "POST",
                data: $revert_invoice_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_invoice_alert.html(response);
                    setTimeout(function () {
                        $('#revert_invoice' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_invoice_alert.html(raw_html);
                }
            }).then(function (response) {
                $revert_invoice_form.trigger('reset');
                setTimeout(function () {
                    $revert_invoice_alert.html('');
                }, 2000);
            });
        } else {
            $revert_invoice_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** paid tab actions */
    $(document).on('click', '.paid_status_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $paid_status_form = $('#paid_status_form'+app_sale);
        var $paid_status_alert = $('#paid_status_alert' + app_sale);
        console.log($paid_status_form.serialize() + '&paid_status=' + form_action);

        if ((form_action === 'Open') || (form_action === 'Close')) {
            $.ajax({
                url: "paid-action",
                type: "POST",
                data: $paid_status_form.serialize() + '&paid_status=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'none';
                    crm_table.draw();
                    $paid_status_alert.html(response);
                    setTimeout(function () {
                        $('#paid_status' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $paid_status_alert.html(raw_html);
                }
            });
        } else {
            $paid_status_alert.html('<p class="text-danger">Form action do not match</p>');
        }
        $paid_status_form.trigger('reset');
        setTimeout(function () {
            $paid_status_alert.html('');
        }, 2000);
        return false;
    });
});


$(document).on('click', '#rebook_confirm', function(event){ 
    event.preventDefault();
    var applicant_id = $(this).closest('form').find("input[name='applicant_hidden_id']").val();
    var sale_id = $(this).closest('form').find("input[name='job_hidden_id']").val();
    $('#rebook_applicant_id').val(applicant_id);
    $('#rebook_sale_id').val(sale_id);
    $(".reebok_confirm").removeClass("fade").modal("hide");
    $("#schedule_interviewww").addClass("fade").modal("show");
 var applicant_name =$("#test_val").val();
    $('#schdule_applicant_name').text(applicant_name);
    // console.log($('#rebook_form').serialize());
    var details = $(this).closest('form').find('textarea[name="details"]').val();
    $("#detail_value").val(details);
    $(this).closest('form').find('textarea[name="details"]').val('');
    // $("#rebook"+applicant_id+'-'+sale_id).on("hidden.bs.modal",function(){
    // $("#schedule_interviewww").on('shown.bs.modal', function() {
    //     $('body').addClass('modal-open');
    // });
    // });
    // return false;

});

$(document).on('click', '#schedule_rebook', function(event){ 
    event.preventDefault();

    var sale_id = $('#rebook_sale_id').val();
    var applicant_id = $('#rebook_applicant_id').val();
	
    var detail_value =  $(this).closest('form').find("#detail_value").val();
	//console.log('applicant_id'+applicant_id+', sale_id: '+sale_id+', detail_value: '+detail_value);
	//return false;
    var form_action = 'rebook_confirm';
    $("#schedule_interviewww").addClass("fade").modal("hide");
    $(".reebok_confirm").addClass("fade");
    $(this).closest('form').find('input[name="schedule_time"]').val('');
    $(this).closest('form').find('input[name="schedule_date"]').val('');
    var schedule_date = $(this).closest('form').find('input[name="schedule_date_reebok"]').val();
    // alert(schedule_date);
    var schedule_time = $(this).closest('form').find('input[name="schedule_time_reebok"]').val();


        table = 'crm_rebook_cv_sample';
                route = 'crm-rebook';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
        if (detail_value) {
            $.ajax({
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url: "rebook_confirm_revert",
                type: "POST",
                data: {sale_id:sale_id,applicant_id:applicant_id,detail_value:detail_value,form_action:form_action,schedule_date:schedule_date,schedule_time:schedule_time},
                success: function (response) {
                    console.log(response);
					//return false;
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $('#notify_alert').html(response);

                    // $rebook_alert.html(response);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $('#notify_alert').html(raw_html);

                }
            });
        } else {
            // $rebook_alert.html('<p class="text-danger">Kindly Provide Details</p>');
            $('#notify_alert').html('<p class="text-danger">Kindly Provide Details</p>');
        }
        // $rebook_form.trigger('reset');
        setTimeout(function () {
            $('#notify_alert').html('');
        }, 2000);
        return false;
});
$(document).on('click', 'a.testing_href', function(event){ 
    var value =$(this).attr('data-name');
    console.log(value);
    $("#test_val").val(value);
});

$(document).on('click', '#openToPaid', function(event) {
    // Show a confirmation dialog
    if (confirm("This action will reopen applications that have been closed for 5 months. Are you sure you want to proceed?")) {
        // If the user confirms, proceed with the AJAX request
        $.ajax({
            url: "open-to-paid-applicants",
            type: "get",
            success: function(response) {
                // Parse the JSON response
                var responseData = JSON.parse(response);

                // Check if the request was successful
                if (responseData.success) {
                    // Show toaster notification
                    toastr.success(responseData.message);
                } else {
                    // Handle error case if needed
                    toastr.error(responseData.message);
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX error
                toastr.error(error);
            }
        });
    }
});