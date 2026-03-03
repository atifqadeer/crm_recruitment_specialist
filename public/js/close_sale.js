/*** global variables */
var close_sale_table = '';
var table = 'close_sale_nurse_sample';
var route = 'closed-sales-nurse';

var columns = [
    { "data":"created_at", "name": "sales.created_at" },
    { "data":"updated_at", "name": "sales.updated_at" },
    { "data":"close_date", "name": "sales.close_date","orderable": false,"searchable": false },
    { "data":"agent_by", "name": "sales.agent_by" ,"orderable": false,"searchable": false},
    { "data":"job_title", "name": "sales.job_title" },
    { "data":"office_name", "name": "offices.office_name" },
    { "data":"unit_name", "name": "units.unit_name" },
    { "data":"postcode", "name": "sales.postcode" },
    { "data":"job_type", "name": "sales.job_type" },
    { "data":"experience", "name": "sales.experience" },
    { "data":"qualification", "name": "sales.qualification" },
    { "data":"salary", "name": "sales.salary" },
    { "data":"status", "name": "sales.status", "orderable": false },
    { "data":"action", "name": "action", "orderable": false }
];

function close_sales_tab(table, route, columns) {
    $.fn.dataTable.ext.errMode = 'throw';
    if ($.fn.DataTable.isDataTable("#"+table)) {
        $('#'+table).DataTable().clear().destroy();
    }
    close_sale_table = $('#'+table).DataTable({
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": route,
        "columns": columns
    });
}
$(document).ready(function() {
    close_sales_tab(table, route, columns);

    /*** shows Reject button in Sent CV popup */
    // $(document).on('change', '.crm_select_reason', function () {
    //     $('.reject_btn').css("display","block");
    // });

    /*** Year selector */
    // $(document).on('focus',".pickadate-year", function(){
    //     $(this).pickadate({
    //         selectYears: 4
    //     });
    // });
    /*** Time picker */
    // $(document).on('focus',".time_picker", function(){
    //     $('#'+$(this).attr('id')).AnyTime_picker({
    //         format: '%H:%i'
    //     });
    // });
    // $(document).on('click', '.crm-refresh', function () {
    //     close_sale_table.draw();
    // });
    $(document).on('shown.bs.tab', '.nav-tabs a', function (event) {
        var datatable_name = $(this).data('datatable_name');
        var tab_href = $(this).attr('href').substr(1);

        switch (tab_href) {
            case 'close_sale_nurse':
                table = 'close_sale_nurse_sample';
                route = 'closed-sales-nurse';
                columns = [
                    { "data":"created_at", "name": "sales.created_at" },
                    { "data":"updated_at", "name": "sales.updated_at" },
                    { "data":"close_date", "name": "sales.close_date","orderable": false,"searchable": false },
                    { "data":"agent_by", "name": "sales.agent_by" ,"orderable": false,"searchable": false},
                    { "data":"job_title", "name": "sales.job_title" },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"job_type", "name": "sales.job_type" },
                    { "data":"experience", "name": "sales.experience" },
                    { "data":"qualification", "name": "sales.qualification" },
                    { "data":"salary", "name": "sales.salary" },
                    { "data":"status", "name": "sales.status", "orderable": false },
                    { "data":"action", "name": "action", "orderable": false }
                ];
                close_sales_tab(table, route, columns);
            break;
            case 'close_sale_nonnurse':
                table = 'close_sale_nonnurse_sample';
                route = 'closed-sales-nonnurse';
                columns = [
                    { "data":"created_at", "name": "sales.created_at" },
                    { "data":"updated_at", "name": "sales.updated_at" },
                    { "data":"close_date", "name": "sales.close_date","orderable": false,"searchable": false },
                    { "data":"agent_by", "name": "sales.agent_by" ,"orderable": false,"searchable": false},
                    { "data":"job_title", "name": "sales.job_title" },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"job_type", "name": "sales.job_type" },
                    { "data":"experience", "name": "sales.experience" },
                    { "data":"qualification", "name": "sales.qualification" },
                    { "data":"salary", "name": "sales.salary" },
                    { "data":"status", "name": "sales.status", "orderable": false },
                    { "data":"action", "name": "action", "orderable": false }
                ];
                close_sales_tab(table, route, columns);
                break;
            case 'close_sale_specialist':
                table = 'close_sale_specialist_sample';
                route = 'closed-sales-specialist';
                columns = [
                    { "data":"created_at", "name": "sales.created_at" },
                    { "data":"updated_at", "name": "sales.updated_at" },
                    { "data":"close_date", "name": "sales.close_date","orderable": false,"searchable": false },
                    { "data":"agent_by", "name": "sales.agent_by" ,"orderable": false,"searchable": false},
                    { "data":"job_title", "name": "sales.job_title" },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"job_type", "name": "sales.job_type" },
                    { "data":"experience", "name": "sales.experience" },
                    { "data":"qualification", "name": "sales.qualification" },
                    { "data":"salary", "name": "sales.salary" },
                    { "data":"status", "name": "sales.status", "orderable": false },
                    { "data":"action", "name": "action", "orderable": false }
                ];
                close_sales_tab(table, route, columns);
            break;
            default:
        }
    });
});

$(document).ready(function() {
    var table;

    function filter_close_sales_tab(table, route, columns, param) {
        $.fn.dataTable.ext.errMode = 'throw';
        if ($.fn.DataTable.isDataTable("#" + table)) {
            $('#' + table).DataTable().clear().destroy();
        }
        table = $('#' + table).DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: route,
                data: function (d) {
                    d.office_id = param;
                }
            },
            columns: columns
        });
    }

    // Initial DataTable call
    function initializeDataTables() {
        filter_close_sales_tab('close_sale_nurse_sample', 'closed-sales-nurse', getColumns(), $('#office_id_nurse').val());
        filter_close_sales_tab('close_sale_nonnurse_sample', 'closed-sales-nonnurse', getColumns(), $('#office_id_nonnurse').val());
        filter_close_sales_tab('close_sale_specialist_sample', 'closed-sales-specialist', getColumns(), $('#office_id_specialist').val());
    }

    // Get columns definition (common columns for simplicity, adjust as needed)
    function getColumns() {
        return [
            { "data": "created_at", "name": "sales.created_at" },
            { "data": "updated_at", "name": "sales.updated_at" },
            { "data": "close_date", "name": "sales.close_date", "orderable": false, "searchable": false },
            { "data": "agent_by", "name": "sales.agent_by", "orderable": false, "searchable": false },
            { "data": "job_title", "name": "sales.job_title" },
            { "data": "office_name", "name": "offices.office_name" },
            { "data": "unit_name", "name": "units.unit_name" },
            { "data": "postcode", "name": "sales.postcode" },
            { "data": "job_type", "name": "sales.job_type" },
            { "data": "experience", "name": "sales.experience" },
            { "data": "qualification", "name": "sales.qualification" },
            { "data": "salary", "name": "sales.salary" },
            { "data": "status", "name": "sales.status", "orderable": false },
            { "data": "action", "name": "action", "orderable": false }
        ];
    }

    // Initialize DataTables on page load
    initializeDataTables();
	
   $(document).on('click', '#clear_filter_close_nurse_btn', function (event) {
        event.preventDefault();
        $('#office_id_nurse').prop('selectedIndex',0);
        $('#office_id_nurse').select2();
        close_sales_tab('close_sale_nurse_sample', 'closed-sales-nurse', getColumns());
    });

    $(document).on('click', '#clear_filter_close_nonnurse_btn', function (event) {
        event.preventDefault();
        $('#office_id_nonnurse').prop('selectedIndex',0);
        $('#office_id_nonnurse').select2();
        close_sales_tab('close_sale_nonnurse_sample', 'closed-sales-nonnurse', getColumns());
    });

    $(document).on('click', '#clear_filter_close_specialist_btn', function (event) {
        event.preventDefault();
        $('#office_id_nonnurse').prop('selectedIndex',0);
        $('#office_id_nonnurse').select2();
        close_sales_tab('close_sale_specialist_sample', 'closed-sales-specialist', getColumns());
    });


    // Handle change event for the select dropdown
    $(document).on('change', '.office_id', function () {
        var form_id = $(this).closest('form').attr('id');
        var office_id = $(this).val();

        switch (form_id) {
            case 'close_sale_nurse_form':
                filter_close_sales_tab('close_sale_nurse_sample', 'filter-closed-sales-nurse', getColumns(), office_id);
                break;
            case 'close_sale_nonnurse_form':
                filter_close_sales_tab('close_sale_nonnurse_sample', 'filter-closed-sales-nonnurse', getColumns(), office_id);
                break;
            case 'close_sale_specialist_form':
                filter_close_sales_tab('close_sale_specialist_sample', 'filter-closed-sales-specialist', getColumns(), office_id);
                break;
            default:
                console.error('Unknown form ID:', form_id);
        }
    });
});

$('#office_id_nurse').select2();
$('#office_id_nonnurse').select2();
$('#office_id_specialist').select2();
