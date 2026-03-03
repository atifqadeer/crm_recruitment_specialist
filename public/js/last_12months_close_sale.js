/*** global variables */
var close_sale_table = "";
var table = "close_sale_nurse_sample";
var route = "last-12months-closed-sales-nurse-ajax";

var columns = [
  { data: "created_at", name: "sales.created_at" },
//   { data: "updated_at", name: "sales.updated_at" },
  {
    data: "close_date",
    name: "sales.close_date",
    orderable: false,
    searchable: false,
  },
  {
    data: "agent_by",
    name: "sales.agent_by",
    orderable: false,
    searchable: false,
  },
  { data: "job_title", name: "sales.job_title" },
  { data: "office_name", name: "offices.office_name" },
  { data: "unit_name", name: "units.unit_name" },
  { data: "postcode", name: "sales.postcode" },
  { data: "job_type", name: "sales.job_type" },
  { data: "experience", name: "sales.experience" },
  { data: "qualification", name: "sales.qualification" },
  { data: "salary", name: "sales.salary" },
  { data: "status", name: "sales.status", orderable: false },
  { data: "latest_note", name: "latest_sales_notes.sale_note" },
  { data: "action", name: "action", orderable: false },
];

function close_sales_tab(table, route, columns) {
  $.fn.dataTable.ext.errMode = "throw";
  if ($.fn.DataTable.isDataTable("#" + table)) {
    $("#" + table)
      .DataTable()
      .clear()
      .destroy();
  }
  close_sale_table = $("#" + table).DataTable({
    processing: true,
    serverSide: true,
    order: [],
    ajax: route,
    columns: columns,
  });
}
$(document).ready(function() {
  close_sales_tab(table, route, columns);
  $(document).on("shown.bs.tab", ".nav-tabs a", function(event) {
    var datatable_name = $(this).data("datatable_name");
    var tab_href = $(this)
      .attr("href")
      .substr(1);

    switch (tab_href) {
      case "close_sale_nurse":
        table = "close_sale_nurse_sample";
        route = "last-12months-closed-sales-nurse-ajax";
        columns = [
          { data: "created_at", name: "sales.created_at" },
        //   { data: "updated_at", name: "sales.updated_at" },
          {
            data: "close_date",
            name: "sales.close_date",
            orderable: false,
            searchable: false,
          },
          {
            data: "agent_by",
            name: "sales.agent_by",
            orderable: false,
            searchable: false,
          },
          { data: "job_title", name: "sales.job_title" },
          { data: "office_name", name: "offices.office_name" },
          { data: "unit_name", name: "units.unit_name" },
          { data: "postcode", name: "sales.postcode" },
          { data: "job_type", name: "sales.job_type" },
          { data: "experience", name: "sales.experience" },
          { data: "qualification", name: "sales.qualification" },
          { data: "salary", name: "sales.salary" },
          { data: "status", name: "sales.status", orderable: false },
          { data: "latest_note", name: "latest_sales_notes.sale_note" },
          { data: "action", name: "action", orderable: false },
        ];
        close_sales_tab(table, route, columns);
        break;
      case "close_sale_nonnurse":
        table = "close_sale_nonnurse_sample";
        route = "last-12months-closed-sales-nonnurse-ajax";
        columns = [
          { data: "created_at", name: "sales.created_at" },
        //   { data: "updated_at", name: "sales.updated_at" },
          {
            data: "close_date",
            name: "sales.close_date",
            orderable: false,
            searchable: false,
          },
          {
            data: "agent_by",
            name: "sales.agent_by",
            orderable: false,
            searchable: false,
          },
          { data: "job_title", name: "sales.job_title" },
          { data: "office_name", name: "offices.office_name" },
          { data: "unit_name", name: "units.unit_name" },
          { data: "postcode", name: "sales.postcode" },
          { data: "job_type", name: "sales.job_type" },
          { data: "experience", name: "sales.experience" },
          { data: "qualification", name: "sales.qualification" },
          { data: "salary", name: "sales.salary" },
          { data: "status", name: "sales.status", orderable: false },
          { data: "latest_note", name: "latest_sales_notes.sale_note" },
          { data: "action", name: "action", orderable: false },
        ];
        close_sales_tab(table, route, columns);
        break;
      case "close_sale_specialist":
        table = "close_sale_specialist_sample";
        route = "last-12months-closed-sales-specialist-ajax";
        columns = [
          { data: "created_at", name: "sales.created_at" },
        //   { data: "updated_at", name: "sales.updated_at" },
          {
            data: "close_date",
            name: "sales.close_date",
            orderable: false,
            searchable: false,
          },
          {
            data: "agent_by",
            name: "sales.agent_by",
            orderable: false,
            searchable: false,
          },
          { data: "job_title", name: "sales.job_title" },
          { data: "office_name", name: "offices.office_name" },
          { data: "unit_name", name: "units.unit_name" },
          { data: "postcode", name: "sales.postcode" },
          { data: "job_type", name: "sales.job_type" },
          { data: "experience", name: "sales.experience" },
          { data: "qualification", name: "sales.qualification" },
          { data: "salary", name: "sales.salary" },
          { data: "status", name: "sales.status", orderable: false },
          { data: "latest_note", name: "latest_sales_notes.sale_note" },
          { data: "action", name: "action", orderable: false },
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
    $.fn.dataTable.ext.errMode = "throw";
    if ($.fn.DataTable.isDataTable("#" + table)) {
      $("#" + table)
        .DataTable()
        .clear()
        .destroy();
    }
    table = $("#" + table).DataTable({
      processing: true,
      serverSide: true,
      order: [[0, "desc"]],
      ajax: {
        url: route,
        data: function(d) {
          d.office_id = param;
        },
      },
      columns: columns,
    });
  }

  // Initial DataTable call
  function initializeDataTables() {
    filter_close_sales_tab(
      "close_sale_nurse_sample",
      "last-12months-closed-sales-nurse-ajax",
      getColumns(),
      $("#office_id_nurse").val()
    );
    filter_close_sales_tab(
      "close_sale_nonnurse_sample",
      "last-12months-closed-sales-nonnurse-ajax",
      getColumns(),
      $("#office_id_nonnurse").val()
    );
    filter_close_sales_tab(
      "close_sale_specialist_sample",
      "last-12months-closed-sales-specialist-ajax",
      getColumns(),
      $("#office_id_specialist").val()
    );
  }

  // Get columns definition (common columns for simplicity, adjust as needed)
  function getColumns() {
    return [
      { data: "created_at", name: "sales.created_at" },
    //   { data: "updated_at", name: "sales.updated_at" },
      {
        data: "close_date",
        name: "sales.close_date",
        orderable: false,
        searchable: false,
      },
      {
        data: "agent_by",
        name: "sales.agent_by",
        orderable: false,
        searchable: false,
      },
      { data: "job_title", name: "sales.job_title" },
      { data: "office_name", name: "offices.office_name" },
      { data: "unit_name", name: "units.unit_name" },
      { data: "postcode", name: "sales.postcode" },
      { data: "job_type", name: "sales.job_type" },
      { data: "experience", name: "sales.experience" },
      { data: "qualification", name: "sales.qualification" },
      { data: "salary", name: "sales.salary" },
      { data: "status", name: "sales.status", orderable: false },
      { data: "latest_note", name: "latest_sales_notes.sale_note" },
      { data: "action", name: "action", orderable: false },
    ];
  }

  // Initialize DataTables on page load
  initializeDataTables();

  $(document).on("click", "#clear_filter_close_nurse_btn", function(event) {
    event.preventDefault();
    $("#office_id_nurse").prop("selectedIndex", 0);
    $("#office_id_nurse").select2();
    close_sales_tab(
      "close_sale_nurse_sample",
      "last-12months-closed-sales-nurse-ajax",
      getColumns()
    );
  });

  $(document).on("click", "#clear_filter_close_nonnurse_btn", function(event) {
    event.preventDefault();
    $("#office_id_nonnurse").prop("selectedIndex", 0);
    $("#office_id_nonnurse").select2();
    close_sales_tab(
      "close_sale_nonnurse_sample",
      "last-12months-closed-sales-nonnurse-ajax",
      getColumns()
    );
  });

  $(document).on("click", "#clear_filter_close_specialist_btn", function(
    event
  ) {
    event.preventDefault();
    $("#office_id_nonnurse").prop("selectedIndex", 0);
    $("#office_id_nonnurse").select2();
    close_sales_tab(
      "close_sale_specialist_sample",
      "last-12months-closed-sales-specialist-ajax",
      getColumns()
    );
  });

  // Handle change event for the select dropdown
  $(document).on("change", ".office_id", function() {
    var form_id = $(this)
      .closest("form")
      .attr("id");
    var office_id = $(this).val();

    switch (form_id) {
      case "close_sale_nurse_form":
        filter_close_sales_tab(
          "close_sale_nurse_sample",
          "last-12months-filter-closed-sales-nurse",
          getColumns(),
          office_id
        );
        break;
      case "close_sale_nonnurse_form":
        filter_close_sales_tab(
          "close_sale_nonnurse_sample",
          "last-12months-filter-closed-sales-nonnurse",
          getColumns(),
          office_id
        );
        break;
      case "close_sale_specialist_form":
        filter_close_sales_tab(
          "close_sale_specialist_sample",
          "last-12months-filter-closed-sales-specialist",
          getColumns(),
          office_id
        );
        break;
      default:
        console.error("Unknown form ID:", form_id);
    }
  });
});

$("#office_id_nurse").select2();
$("#office_id_nonnurse").select2();
$("#office_id_specialist").select2();

$(document).ready(function () {
  // Bind to the 'show.bs.modal' event for modals with dynamic IDs
  $('[id^="editNote"]').on('show.bs.modal', function () {
      console.log('Modal is being shown');
  }).on('hide.bs.modal', function () {
      console.log('Modal is being hidden');
  });

  // Handle form submission for dynamically generated modals
  $(document).on('submit', '[id^="editSaleNoteForm"]', function (e) {
    e.preventDefault(); // Prevent full page reload

    const formId = $(this).attr('id');
    const formData = $(this).serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content');
    const url = '/update-closed-sales-notes';

    // Get the closest tab that is active
    const activeTab = $('.nav-link.active');  // This finds the currently active tab
    const datatableName = activeTab.data('datatable_name');  // Get the data-datatable_name attribute

    // Define routes for each datatable name
    let ajaxUrl;
    switch (datatableName) {
        case 'close_sale_nurse_sample':
            ajaxUrl = '/last-12months-closed-sales-nurse-ajax'; // Define your actual route here
            break;
        case 'close_sale_nonnurse_sample':
            ajaxUrl = '/last-12months-closed-sales-nonnurse-ajax'; // Define your actual route here
            break;
        case 'close_sale_specialist_sample':
            ajaxUrl = '/last-12months-closed-sales-specialist-ajax'; // Define your actual route here
            break;
        default:
            console.error('No route defined for this datatable');
            return; // Exit the function if no route is defined
    }

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        success: function(response) {
            toastr.success(response.message);

            // Close the modal
            $('#' + formId).modal('hide');
            $('.modal-backdrop').remove();

            
            // Reload the DataTable based on the tab
            switch (datatableName) {
                case 'close_sale_nurse_sample':
                    close_sales_tab("close_sale_nurse_sample", "last-12months-closed-sales-nurse-ajax", columns);
                    break;
                case 'close_sale_nonnurse_sample':
                    close_sales_tab("close_sale_nonnurse_sample", "last-12months-closed-sales-nonnurse-ajax", columns);
                    break;
                case 'close_sale_specialist_sample':
                    close_sales_tab("close_sale_specialist_sample", "last-12months-closed-sales-specialist-ajax", columns);
                    break;
                default:
                    console.error('Unknown tab for DataTable reload');
            }

            // Optionally show a success message
        },
        error: function(xhr, status, error) {
            // Handle errors if any
            toastr.error(response.message);
        }
    });
});



});
