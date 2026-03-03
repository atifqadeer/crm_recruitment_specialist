/*** global variables */
var quality_cleared_sale_table = '';
var table = 'quality_cleared_sale_nurse_sample';
var route = 'get-cleared-sales-nurse';

function quality_cleared_sales_tab(table, route) {
    $.fn.dataTable.ext.errMode = 'throw';
    if ($.fn.DataTable.isDataTable("#" + table)) {
        $('#' + table).DataTable().clear().destroy();
    }
    quality_cleared_sale_table = $('#' + table).DataTable({
        "aoColumnDefs": [{"bSortable": false, "aTargets": [0, 10]}],
        "bProcessing": true,
        "bServerSide": true,
        "aaSorting": [[0, "desc"]],
        "sPaginationType": "full_numbers",
        "sAjaxSource": route,
        "aLengthMenu": [[10, 50, 100, 500], [10, 50, 100, 500]],
        "drawCallback": function(settings, json) {
            $('[data-popup="tooltip"]').tooltip();
        }
    });
}

function filter_cleared_sales_tab(table, route, param) {
    $.fn.dataTable.ext.errMode = 'throw';
    if ($.fn.DataTable.isDataTable("#" + table)) {
        $('#' + table).DataTable().clear().destroy();
    }
    $('#' + table).DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: route,
            data: function(d) {
                d.office_id = param;
            }
        },
    });
}

$(document).ready(function() {
    // Initialize only the first DataTable on page load
    quality_cleared_sales_tab('quality_cleared_sale_nurse_sample', 'get-cleared-sales-nurse');

    // Lazy load DataTables when respective tabs are clicked
    $(document).on('shown.bs.tab', '.nav-tabs a', function(event) {
        var tab_href = $(this).attr('href').substr(1);

        switch (tab_href) {
            case 'cleared_sale_nurse':
                quality_cleared_sales_tab('quality_cleared_sale_nurse_sample', 'get-cleared-sales-nurse');
                break;
            case 'cleared_sale_nonnurse':
                quality_cleared_sales_tab('quality_cleared_sale_nonnurse_sample', 'get-cleared-sales-nonnurse');
                break;
            case 'cleared_sale_specialist':
                quality_cleared_sales_tab('quality_cleared_sale_specialist_sample', 'get-cleared-sales-specialist');
                break;
            default:
                console.error('Unknown tab:', tab_href);
        }
    });

   $(document).on('click', '#clear_filter_nurse_btn', function (event) {
        event.preventDefault();
        $('#office_id_cleared_nurse').prop('selectedIndex',0);
        $('#office_id_cleared_nurse').select2();
        quality_cleared_sales_tab('quality_cleared_sale_nurse_sample', 'get-cleared-sales-nurse');
    });

    $(document).on('click', '#clear_filter_nonnurse_btn', function (event) {
        event.preventDefault();
        $('#office_id_cleared_nonnurse').prop('selectedIndex',0);
        $('#office_id_cleared_nonnurse').select2();
        quality_cleared_sales_tab('quality_cleared_sale_nonnurse_sample', 'get-cleared-sales-nonnurse');
    });

    $(document).on('click', '#clear_filter_specialist_btn', function (event) {
        event.preventDefault();
        $('#office_id_cleared_specialist').prop('selectedIndex',0);
        $('#office_id_cleared_specialist').select2();
        quality_cleared_sales_tab('quality_cleared_sale_specialist_sample', 'get-cleared-sales-specialist');
    });

    // Handle change event for the select dropdown
    $(document).on('change', '.cleared_office_id', function() {
        var form_id = $(this).closest('form').attr('id');
        var office_id = $(this).val();

        switch (form_id) {
            case 'cleared_sale_nurse_form':
                filter_cleared_sales_tab('quality_cleared_sale_nurse_sample', 'filter-cleared-sales-nurse', office_id);
                break;
            case 'cleared_sale_nonnurse_form':
                filter_cleared_sales_tab('quality_cleared_sale_nonnurse_sample', 'filter-cleared-sales-nonnurse', office_id);
                break;
            case 'cleared_sale_specialist_form':
                filter_cleared_sales_tab('quality_cleared_sale_specialist_sample', 'filter-cleared-sales-specialist', office_id);
                break;
            default:
                console.error('Unknown form ID:', form_id);
        }
    });

    // Initialize select2 dropdowns
    $('#office_id_cleared_nurse').select2();
    $('#office_id_cleared_nonnurse').select2();
    $('#office_id_cleared_specialist').select2();
});
