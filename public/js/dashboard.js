$(function() {
    $('#daily_date').datetimepicker({
        pickTime: false,
    });
    $('#daily_date').on('changeDate', function(ev){
        $(this).datetimepicker('hide');
        var daily_date_value = $('#daily_date_value').val();
		 $('#app_daily_date').val(daily_date_value);
		$('#close_app_daily_date').val(daily_date_value);
		//update sale date append
		 $('#open_daily_date_update').val(daily_date_value);
		 $('#close_daily_date_update').val(daily_date_value);
        $.ajax({
            url: "daily-stats/"+daily_date_value,
            type: "get",
            success: function(response){

                var callback_html = response.daily_data.no_of_callbacks;
                if (callback_html != 0) {
                    callback_html = '<form action="callback-applicants" method="get">' +
                        '<input type="hidden" name="app_daily_date" value="' + daily_date_value + '" id="app_daily_date">' +
                        '<input type="submit" class="submitLink" value="' + response.daily_data.no_of_callbacks + '">' +
                        '</form>';
                }
                $('#app_form_date').val(daily_date_value);
                $('#daily_date_string').html(response.daily_data.daily_date_string);
                $('#no_of_callbacks').html(callback_html);
                $('#no_of_not_interested').html(response.daily_data.no_of_not_interested + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;Not -</span>');
                $('#no_of_nurses').html(response.daily_data.no_of_nurses);
                $('#no_of_non_nurses').html(response.daily_data.no_of_non_nurses);
				
				
				 // old applicant update
                $('#no_of_nurses_daily_update').html(response.daily_data.no_of_nurses_update);
                $('#no_of_non_nurses_daily_update').html(response.daily_data.no_of_non_nurses_update);
                $('#no_of_not_interested_daily_update').html(response.daily_data.no_of_not_interested_update + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;Not -</span>');
                $('#no_of_callbacks_daily_update').html(callback_html);
				
                /*** sales */
                $('#daily_open_sales').html(response.daily_data.open_sales);
                $('#daily_close_sales').html(response.daily_data.close_sales);
                $('#daily_psl_offices').html(response.daily_data.psl_offices);
                $('#daily_non_psl_offices').html(response.daily_data.non_psl_offices + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;NON -</span>');
				
				      /*** sales update */
                $('#daily_open_sales_update').html(response.daily_data.open_sales_update);
                $('#daily_close_sales_update').html(response.daily_data.close_sales_update);
                $('#daily_psl_offices_update').html(response.daily_data.psl_offices_update);
                $('#daily_non_psl_offices_update').html(response.daily_data.non_psl_offices_update + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;NON -</span>');

				
                /*** quality */
                $('#daily_cvs').html(response.daily_data.quality_cvs);
                $('#daily_cvs_rejected').html(response.daily_data.quality_cvs_rejected);
                $('#daily_cvs_cleared').html(response.daily_data.quality_cvs_cleared);
                /*** crm */
                $('#crm_total').html(response.daily_data.crm_total);
                $('#no_of_crm_sent').html(response.daily_data.crm_sent);
				$('#no_of_crm_open_cvs').html(response.daily_data.crm_open_cvs);
                $('#no_of_crm_rejected').html(response.daily_data.crm_rejected);
                $('#no_of_crm_requested').html(response.daily_data.crm_requested);
                $('#no_of_crm_request_rejected').html(response.daily_data.crm_request_rejected);
                $('#no_of_crm_confirmed').html(response.daily_data.crm_confirmed);
                $('#no_of_crm_prestart_attended').html(response.daily_data.crm_prestart_attended);
                $('#no_of_crm_rebook').html(response.daily_data.crm_rebook);
                $('#no_of_crm_not_attended').html(response.daily_data.crm_not_attended);
                $('#no_of_crm_declined').html(response.daily_data.crm_declined);
                $('#no_of_crm_date_started').html(response.daily_data.crm_date_started);
                $('#no_of_crm_start_date_held').html(response.daily_data.crm_start_date_held);
                $('#no_of_crm_invoiced').html(response.daily_data.crm_invoiced);
                $('#no_of_crm_disputed').html(response.daily_data.crm_disputed);
                $('#no_of_crm_paid').html(response.daily_data.crm_paid);
				$('#no_of_quality_revert').html(response.daily_data.quality_revert);
                $('#no_of_crm_revert').html(response.daily_data.crm_revert);

                var chart_data = [
                    ['Crm Stage', 'Number of Applicants'],
                    ['crm_sent', response.daily_data.crm_sent],
					['crm_open_cvs', response.daily_data.crm_open_cvs],
                    ['crm_rejected', response.daily_data.crm_rejected],
                    ['crm_requested', response.daily_data.crm_requested],
                    ['crm_request_rejected', response.daily_data.crm_request_rejected],
                    ['crm_confirmed', response.daily_data.crm_confirmed],
                    ['crm_prestart_attended', response.daily_data.crm_prestart_attended],
                    ['crm_rebook', response.daily_data.crm_rebook],
                    ['crm_not_attended', response.daily_data.crm_not_attended],
                    ['crm_date_started', response.daily_data.crm_date_started],
                    ['crm_declined', response.daily_data.crm_declined],
                    ['crm_start_date_held', response.daily_data.crm_start_date_held],
                    ['crm_invoiced', response.daily_data.crm_invoiced],
                    ['crm_disputed', response.daily_data.crm_disputed],
                    ['crm_paid', response.daily_data.crm_paid]
                ];
                var color_data = [
                    '#0b5baf','#0e78e6','#0d6fd4','#2a8cf2',
                    '#1782f1','#2a8cf2','#469cf3','#5ca9f6',
                    '#81bcf7','#458cd3','#86bef8','#73b4f6',
                    '#97c9f8','#98c8f9','#0b5baf','#0e78e6'
                            ];

                loadChart('google-donut', chart_data, color_data);

            },
            error: function(response){
                alert('<p>WHOOPS! Something Went Wrong!!</p>');
            }
        });
    });

    $('#weekly_date').datetimepicker({
        pickTime: false,
    });
    $('#weekly_date').on('changeDate', function(ev){

        moment.updateLocale('en', {
            week: { dow: 1 } // Monday is the first day of the week
        });

        var value = $("#weekly_date_value").val();
        var firstDate = moment(value, "DD-MM-YYYY").day(1).format("DD-MM-YYYY");
        var lastDate =  moment(value, "DD-MM-YYYY").day(7).format("DD-MM-YYYY");
        $(this).datetimepicker('hide');
		 $('#open_start_date').val(firstDate);
        $('#open_end_date').val(lastDate);

        $('#close_start_date').val(firstDate);
        $('#close_end_date').val(lastDate);
        $("#weekly_date_value").val(firstDate + " - " + lastDate);

        var firstLongDate = moment(firstDate, "DD-MM-YYYY").format("MMM D, YYYY");
        var lastLongDate = moment(lastDate, "DD-MM-YYYY").format("MMM D, YYYY");
        $("#weekly_date_format").html(firstLongDate + " - "+ lastLongDate);

        $.ajax({
            url: "weekly-stats/"+ firstDate + "/" + lastDate,
            type: "get",
            success: function(response) {
				console.log(response.weekly_data.no_of_nurses_weekly_update);
				console.log('weekly');
				
                /*** Applicants */
                $('#weekly_no_of_nurses').html(response.weekly_data.no_of_nurses.toLocaleString());
                $('#weekly_no_of_non_nurses').html(response.weekly_data.no_of_non_nurses);
                $('#weekly_no_of_callbacks').html(response.weekly_data.no_of_callbacks);
                $('#weekly_no_of_not_interested').html(response.weekly_data.no_of_not_interested + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;Not -</span>');
				
				//old applicant update
				 $('#weekly_no_of_nurses_update').html(response.weekly_data.no_of_nurses_weekly_update.toLocaleString());
                $('#weekly_no_of_non_nurses_update').html(response.weekly_data.no_of_non_nurses_weekly_update);
                $('#weekly_no_of_callbacks_update').html(response.weekly_data.no_of_callbacks_weekly_update);
                $('#weekly_no_of_not_interested_update').html(response.weekly_data.no_of_not_interested_weekly_update + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;Not -</span>');

				
                /*** Sales */
                $('#weekly_no_of_open_sales').html(response.weekly_data.open_sales);
                $('#weekly_no_of_close_sales').html(response.weekly_data.close_sales);
                $('#weekly_no_of_psl').html(response.weekly_data.psl_offices);
                $('#weekly_no_of_nonpsl').html(response.weekly_data.non_psl_offices + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;NON -</span>');
                /*** Quality */
                $('#weekly_quality_cvs').html(response.weekly_data.cvs.toLocaleString());
                $('#weekly_quality_cvs_cleared').html(response.weekly_data.cvs_cleared);
                $('#weekly_quality_cvs_rejected').html(response.weekly_data.cvs_rejected);
                /*** CRM */
                $('#weekly_crm_sent').html(response.weekly_data.crm_sent);
                $('#weekly_crm_rejected').html(response.weekly_data.crm_rejected);
                $('#weekly_crm_requested').html(response.weekly_data.crm_requested);
                $('#weekly_crm_request_rejected').html(response.weekly_data.crm_request_rejected);
                $('#weekly_crm_confirmed').html(response.weekly_data.crm_confirmed);
                $('#weekly_crm_prestart_attended').html(response.weekly_data.crm_prestart_attended);
                $('#weekly_crm_rebook').html(response.weekly_data.crm_rebook);
                $('#weekly_crm_not_attended').html(response.weekly_data.crm_not_attended);
                $('#weekly_crm_declined').html(response.weekly_data.crm_declined);
                $('#weekly_crm_date_started').html(response.weekly_data.crm_date_started);
                $('#weekly_crm_start_date_held').html(response.weekly_data.crm_start_date_held);
                $('#weekly_crm_invoiced').html(response.weekly_data.crm_invoiced);
                $('#weekly_crm_disputed').html(response.weekly_data.crm_disputed);
                $('#weekly_crm_paid').html(response.weekly_data.crm_paid);
                $('#weekly_crm_total').html(response.weekly_data.crm_total);
				$('#weekly_crm_quality_revert').html(response.weekly_data.quality_revert);
                $('#weekly_crm_revert').html(response.weekly_data.crm_revert);

                var chart_data = [
                    ['Crm Stage', 'Number of Applicants'],
                    ['crm_sent', response.weekly_data.crm_sent],
                    ['crm_rejected', response.weekly_data.crm_rejected],
                    ['crm_requested', response.weekly_data.crm_requested],
                    ['crm_request_rejected', response.weekly_data.crm_request_rejected],
                    ['crm_confirmed', response.weekly_data.crm_confirmed],
                    ['crm_prestart_attended', response.weekly_data.crm_prestart_attended],
                    ['crm_rebook', response.weekly_data.crm_rebook],
                    ['crm_not_attended', response.weekly_data.crm_not_attended],
                    ['crm_declined', response.weekly_data.crm_declined],
                    ['crm_date_started', response.weekly_data.crm_date_started],
                    ['crm_start_date_held', response.weekly_data.crm_start_date_held],
                    ['crm_invoiced', response.weekly_data.crm_invoiced],
                    ['crm_disputed', response.weekly_data.crm_disputed],
                    ['crm_paid', response.weekly_data.crm_paid]
                ];

                var color_data = [
                    '#036d62','#059c8d','#168d81','#15b4a4',
                    '#28ada2','#38bdaf','#22c7b6','#3ad1c2',
                    '#65e6d9','#48c3b7','#4fd8ca','#5bd1c5',
                    '#8cf8ed','#76ddd3'
                ];

                loadChart('weekly_google-donut', chart_data, color_data);

            },
            error: function(response) {
                
                alert('<p>WHOOPS! Something went wrong!!</p>');
            }
        });
    });

    $('#monthly_date').datetimepicker({
        minViewMode: 'months',
        viewMode: 'months',
        format: 'MM/yyyy',
        pickTime: false
    });
    $('#monthly_date').on('changeDate', function(ev){
        var dateParts = ev.date.toString().split(" ");
        var months_full_names = { "Jan":"January", "Feb":"February", "Mar":"March", "Apr":"April", "May":"May", "Jun":"June", "Jul":"July", "Aug":"August", "Sep":"September", "Oct":"October", "Nov":"November", "Dec":"December" };
        var month_name = dateParts[1];
        var year = dateParts[3];
        var month = moment(month_name,"MMM").format("MM");
	 $('#monthly_date_sale').val(month);
        $('#yearly_date_sale').val(year);
        // close sale monthly date month,year
        $('#monthly_close_sale').val(month);
        $('#yearly_close_sale').val(year);
		
		month=ev.date.getUTCMonth() + 1;
		
        //var value = $("#monthly_date_input").val();
        //console.log(value);
        //var month = moment(value, "MM-YYYY").format("MM");
        //var month_name = moment(month, "MM").format("MMMM");
        //var year =  moment(value, "MM-YYYY").format("YYYY");
        $(this).datetimepicker('hide');

        $("#monthly_date_value").html(months_full_names[month_name] + " " + year);

        $.ajax({
            url: "monthly-stats/"+ month + "/" + year,
            type: "get",
            success: function(response) {
                /*** Applicants */
                $('#monthly_no_of_nurses').html(response.monthly_data.no_of_nurses.toLocaleString());
                $('#monthly_no_of_non_nurses').html(response.monthly_data.no_of_non_nurses);
                $('#monthly_no_of_callbacks').html(response.monthly_data.no_of_callbacks);
                $('#monthly_no_of_not_interested').html(response.monthly_data.no_of_not_interested + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;Not -</span>');
				
				  /*** Old applicant update */
                $('#monthly_no_of_nurses_update').html(response.monthly_data.no_of_nurses_monthly_update);
                $('#monthly_no_of_non_nurses_update').html(response.monthly_data.no_of_non_nurses_monthly_update);
                $('#monthly_no_of_callbacks_update').html(response.monthly_data.no_of_callbacks_monthly_update);
                $('#monthly_no_of_not_interested_update').html(response.monthly_data.no_of_not_interested_monthly_update + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;Not -</span>');


				
                /*** Sales */
                $('#monthly_no_of_open_sales').html(response.monthly_data.open_sales);
                $('#monthly_no_of_close_sales').html(response.monthly_data.close_sales);
                $('#monthly_no_of_psl').html(response.monthly_data.psl_offices);
                $('#monthly_no_of_nonpsl').html(response.monthly_data.non_psl_offices + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">&nbsp;NON -</span>');
                /*** Quality */
                $('#monthly_quality_cvs').html(response.monthly_data.quality_cvs.toLocaleString());
                $('#monthly_quality_rejected').html(response.monthly_data.quality_cvs_rejected);
                $('#monthly_quality_cleared').html(response.monthly_data.quality_cvs_cleared.toLocaleString());
                /*** CRM */
                $('#monthly_crm_sent').html(response.monthly_data.crm_sent);
                $('#monthly_crm_rejected').html(response.monthly_data.crm_rejected);
                $('#monthly_crm_requested').html(response.monthly_data.crm_requested);
                $('#monthly_crm_request_rejected').html(response.monthly_data.crm_request_rejected);
                $('#monthly_crm_confirmed').html(response.monthly_data.crm_confirmed);
                $('#monthly_crm_prestart_attended').html(response.monthly_data.crm_prestart_attended);
                $('#monthly_crm_rebook').html(response.monthly_data.crm_rebook);
                $('#monthly_crm_not_attended').html(response.monthly_data.crm_not_attended);
                $('#monthly_crm_declined').html(response.monthly_data.crm_declined);
                $('#monthly_crm_date_started').html(response.monthly_data.crm_date_started);
                $('#monthly_crm_start_date_held').html(response.monthly_data.crm_start_date_held);
                $('#monthly_crm_invoiced').html(response.monthly_data.crm_invoiced);
                $('#monthly_crm_disputed').html(response.monthly_data.crm_disputed);
                $('#monthly_crm_paid').html(response.monthly_data.crm_paid);
                $('#monthly_crm_total').html(response.monthly_data.crm_total);
				 $('#monthly_quality_revert').html(response.monthly_data.quality_revert);
                $('#monthly_crm_revert').html(response.monthly_data.crm_revert);
 // crm_sent
            // crm_rejected
            // crm_requested
            // crm_request_rejected
            // crm_confirmed
            // crm_prestart_attended
            // crm_rebook
            // crm_not_attended
            // crm_declined
            // crm_date_started
            // crm_start_date_held
            // crm_invoiced
            // crm_disputed
            // crm_paid
                var chart_data = [
                    ['Crm Stage', 'Number of Applicants'],
                    ['crm_sent', response.monthly_data.crm_sent],
                    ['crm_rejected', response.monthly_data.crm_rejected],
                    ['crm_requested', response.monthly_data.crm_requested],
                    ['crm_request_rejected', response.monthly_data.crm_request_rejected],
                    ['crm_confirmed', response.monthly_data.crm_confirmed],
                    ['crm_prestart_attended', response.monthly_data.crm_prestart_attended],
                    ['crm_rebook', response.monthly_data.crm_rebook],
                    ['crm_not_attended', response.monthly_data.crm_not_attended],
                    ['crm_declined', response.monthly_data.crm_declined],
                    ['crm_date_started', response.monthly_data.crm_date_started],
                    ['crm_start_date_held', response.monthly_data.crm_start_date_held],
                    ['crm_invoiced', response.monthly_data.crm_invoiced],
                    ['crm_disputed', response.monthly_data.crm_disputed],
                    ['crm_paid', response.monthly_data.crm_paid]
                ];

                var color_data = [
                    '#9e0e4f','#ce0e64','#b31b5d','#df317c',
                    '#d3347b','#dd5190','#ec4590','#f0609e',
                    '#f690bd','#e06d9f','#f376ae','#e795ba',
                    '#f6b8d4','#e7afc8'
                ];

                loadChart('monthly_google-donut', chart_data, color_data);
            },
            error: function(response) {
                alert('<p>WHOOPS! Something went wrong!!</p>');
            }
        });
    });

    $('#custom_start_date').datetimepicker({
        pickTime: false,
        endDate: new Date(moment(moment($("#custom_end_date_value").val(), "DD-MM-YYYY").add(-1, 'days')).format("YYYY-MM-DD"))
    });

    $('#custom_start_date').on('changeDate', function (ev) {
        $(this).datetimepicker('hide');
        $('#custom_end_date').datetimepicker('destroy');
        $('#custom_end_date').datetimepicker({
            pickTime: false,
            startDate: new Date(moment(moment($("#custom_start_date_value").val(), "DD-MM-YYYY").add(1, 'days')).format("YYYY-MM-DD"))
        });
    });

    $('#custom_end_date').datetimepicker({
        pickTime: false,
        startDate: new Date(moment(moment($("#custom_start_date_value").val(), "DD-MM-YYYY").add(1, 'days')).format("YYYY-MM-DD"))
    });

    $('#custom_end_date').on('changeDate', function (ev) {
        $(this).datetimepicker('hide');
        $('#custom_start_date').datetimepicker('destroy');
        $('#custom_start_date').datetimepicker({
            pickTime: false,
            endDate: new Date(moment(moment($("#custom_end_date_value").val(), "DD-MM-YYYY").add(-1, 'days')).format("YYYY-MM-DD"))
        });
    });

    $('#custom_submit').on('click', function(event){
        var sdate = $("#custom_start_date_value").val();
        var edate = $("#custom_end_date_value").val();

        $.ajax({
            url: "custom-stats/"+ sdate + "/" + edate,
            type: "get",
            success: function(response) {
                /*** Applicants */
				console.log('sanat');
                // $("#custom_date_string").html(response.custom_data.sdate + " - " + response.custom_data.edate);
                $('#custom_no_of_nurses').html(response.custom_data.no_of_nurses.toLocaleString());
                $('#custom_no_of_non_nurses').html(response.custom_data.no_of_non_nurses.toLocaleString());
                $('#custom_no_of_callbacks').html(response.custom_data.no_of_callbacks.toLocaleString());
                $('#custom_no_of_not_interested').html(response.custom_data.no_of_not_interested.toLocaleString() + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">Not -</span>');
                /*** Sales */
                $('#custom_open_sales').html(response.custom_data.open_sales.toLocaleString());
                $('#custom_close_sales').html(response.custom_data.close_sales.toLocaleString());
                $('#custom_psl').html(response.custom_data.psl_offices.toLocaleString());
                $('#custom_nonpsl').html(response.custom_data.non_psl_offices.toLocaleString() + ' <span class="text-muted" style="font-size: 13px; font-weight: 400;">NON -</span>');
                /*** Quality */
                $('#custom_quality_cvs').html(response.custom_data.cvs.toLocaleString());
                $('#custom_quality_rejected').html(response.custom_data.cvs_rejected.toLocaleString());
                $('#custom_quality_cleared').html(response.custom_data.cvs_cleared.toLocaleString());
                /*** CRM */
                $('#custom_crm_sent').html(response.custom_data.crm_sent);
                $('#custom_crm_rejected').html(response.custom_data.crm_rejected);
                $('#custom_crm_requested').html(response.custom_data.crm_requested);
                $('#custom_crm_request_rejected').html(response.custom_data.crm_request_rejected);
                $('#custom_crm_confirmed').html(response.custom_data.crm_confirmed);
                $('#custom_crm_prestart_attended').html(response.custom_data.crm_prestart_attended);
                $('#crm_rebook').html(response.custom_data.crm_rebook);
                $('#custom_crm_not_attended').html(response.custom_data.crm_not_attended);
                $('#custom_crm_declined').html(response.custom_data.crm_declined);
                $('#custom_crm_date_started').html(response.custom_data.crm_date_started);
                $('#custom_crm_start_date_held').html(response.custom_data.crm_start_date_held);
                $('#custom_crm_invoiced').html(response.custom_data.crm_invoiced);
                $('#custom_crm_disputed').html(response.custom_data.crm_disputed);
                $('#custom_crm_paid').html(response.custom_data.crm_paid);
                $('#custom_crm_total').html(response.custom_data.crm_total);


               

                var chart_data = [
                    ['Crm Stage', 'Number of Applicants'],
                    ['crm_sent', response.custom_data.crm_sent],
                    ['crm_rejected', response.custom_data.crm_rejected],
                    ['crm_requested', response.custom_data.crm_requested],
                    ['crm_request_rejected', response.custom_data.crm_request_rejected],
                    ['crm_confirmed', response.custom_data.crm_confirmed],
                    ['crm_prestart_attended', response.custom_data.crm_prestart_attended],
                    ['crm_rebook', response.custom_data.crm_rebook],
                    ['crm_not_attended', response.custom_data.crm_not_attended],
                    ['crm_declined', response.custom_data.crm_declined],
                    ['crm_date_started', response.custom_data.crm_date_started],
                    ['crm_start_date_held', response.custom_data.crm_start_date_held],
                    ['crm_invoiced', response.custom_data.crm_invoiced],
                    ['crm_disputed', response.custom_data.crm_disputed],
                    ['crm_paid', response.custom_data.crm_paid]
                ];

                var color_data = [
                    '#303140','#383a4b','#535468','#494b62',
                    '#646580','#717392','#565974','#626483',
                    '#7d80a5','#8587aa','#757798','#9da0c2',
                    '#a7a8cf','#b6b8db'
                ];

                loadChart('custom_google-donut', chart_data, color_data);

            },
            error: function(response) {
                alert('<p>WHOOPS! Something went wrong!!</p>');
            }
        });

    });

    function loadChart(element, chart_data, color_data) {
        // Initialize chart
        // alert('here');
        // console.log(chart_data);
        google.charts.load('current', {
            callback: function () {

                // Draw chart
                drawDonut();

                // Resize on sidebar width change
                var sidebarToggle = document.querySelector('.sidebar-control');
                sidebarToggle && sidebarToggle.addEventListener('click', drawDonut);

                // Resize on window resize
                var resizeDonutBasic;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeDonutBasic);
                    resizeDonutBasic = setTimeout(function () {
                        drawDonut();
                    }, 200);
                });
            },
            packages: ['corechart']
        });

        function drawDonut() {

            // Define charts element

            var donut_chart_element = document.getElementById(element);

            var data = google.visualization.arrayToDataTable(chart_data);
            // Options
            var options_donut = {
                fontName: 'Roboto',
                pieHole: 0.35,
                height: 225,
                width: 225,
                backgroundColor: 'transparent',
                colors: color_data,
                chartArea: {
                    left: 10,
                    width: '90%',
                    height: '90%'
                },
                pieSliceText: 'value',
                sliceVisibilityThreshold:0,
                legend: 'none'
            };

            // Instantiate and draw our chart, passing in some options.
            var donut = new google.visualization.PieChart(donut_chart_element);
            donut.draw(data, options_donut);

        }
    }

    $('#user_stats_start_date').datetimepicker({
        pickTime: false,
        endDate: new Date(moment(moment($("#user_stats_end_date_value").val(), "DD-MM-YYYY")).format("YYYY-MM-DD"))
    });

    $('#user_stats_start_date').on('changeDate', function (ev) {
        $(this).datetimepicker('hide');
        $('#user_stats_end_date').datetimepicker('destroy');
        $('#user_stats_end_date').datetimepicker({
            pickTime: false,
            startDate: new Date(moment(moment($("#user_stats_start_date_value").val(), "DD-MM-YYYY")).format("YYYY-MM-DD"))
        });
    });

    $('#user_stats_end_date').datetimepicker({
        pickTime: false,
        startDate: new Date(moment(moment($("#user_stats_start_date_value").val(), "DD-MM-YYYY")).format("YYYY-MM-DD"))
    });

    $('#user_stats_end_date').on('changeDate', function (ev) {
        $(this).datetimepicker('hide');
        $('#user_stats_start_date').datetimepicker('destroy');
        $('#user_stats_start_date').datetimepicker({
            pickTime: false,
            endDate: new Date(moment(moment($("#user_stats_end_date_value").val(), "DD-MM-YYYY")).format("YYYY-MM-DD"))
        });
    });
});
$(document).on({
    ajaxStart: function(){
        $("body").addClass("loading"); 
    },
    ajaxStop: function(){ 
        $("body").removeClass("loading"); 
    }    
});
