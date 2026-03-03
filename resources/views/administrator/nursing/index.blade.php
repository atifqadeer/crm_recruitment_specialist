@extends('layouts.app')
@section('style')

    <script>
      var table;
      var columns = [
          { "data":"added_date", "name": "applicant_notes.added_date" },
          { "data":"added_time", "name": "applicant_notes.added_time" },
          { "data":"applicant_name", "name": "applicants.applicant_name" },
          { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
          { "data":"job_category", "name": "applicants.job_category" },
          { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
          { "data":"applicant_phone", "name": "applicants.applicant_phone" },
          { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
          { "data":"applicant_source", "name": "applicants.applicant_source" },
          { "data":"details", "name": "applicant_notes.details" },
          { "data":"history", "name": "history", "orderable": false, "searchable": false }
      ];
      $(document).ready(function() {
          $.fn.dataTable.ext.errMode = 'none';
          <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('resource_No-Nursing-Home_revert-no-nursing-home')): ?>
                columns.unshift({ "data":"checkbox", "name": "checkbox", "orderable": false, "searchable": false });
          <?php endif; ?>
          table = $('#no_nursing_applicant_sample_1').DataTable({
               "processing": true,
               "serverSide": true,
               "order": [],
               "ajax":"getNurseHomeApplicantsAjax",
               "columns": columns,
               "rowCallback": function(row, data) {
                   $('.check-all-no-nurses').prop( "checked", false );
                   $.uniform.update('.check-all-no-nurses');
               }
          });

      });

    </script>

@endsection
@section('content')
<!-- Main content -->
<div class="content-wrapper">

    <!-- Page header -->
{{--    <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
    <div class="page-header page-header-dark has-cover">
        <div class="page-header-content header-elements-inline">

            <div class="page-title">
                <h5>
                    <i class="icon-arrow-left52 mr-2"></i>
                    <span class="font-weight-semibold">Applicants</span> - No Nursing Home
                </h5>
            </div>
        </div>

        <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
            <div class="d-flex">
                <div class="breadcrumb">
                    <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                    <a href="#" class="breadcrumb-item">Current</a>
                    <span class="breadcrumb-item active">No Nursing</span>
                </div>
            </div>
        </div>
    </div>
    <!-- /page header -->


    <!-- Content area -->
    <div class="content">
        <!-- Default ordering -->
        <div class="card">
            <div class="card-header header-elements-inline">
                <h5 class="card-title">No Nursing Home Applicants</h5>
                <div>
                    @can('resource_No-Nursing-Home_revert-no-nursing-home')
                    <a href="#"
                    class="btn bg-teal legitRipple float-right"
                    data-controls-modal="#revert_no_nurses_modal" data-backdrop="static"
                    data-keyboard="false" data-toggle="modal"
                    data-target="#revert_no_nurses_modal">
                        <i class="icon-redo"></i>&emsp;
                        Revert
                    </a>
                    <!-- Revert All Modal -->
                    <div id="revert_no_nurses_modal" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add Revert No Nursing Notes Below:</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form method="POST" class="form-horizontal" id="revert_no_nurses_form">
                                    @csrf()
                                    <div class="modal-body">
                                        <div id="revert_no_nurses_alert"></div>
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                            <textarea name="details" class="form-control" cols="30" rows="4" id="details"
                                                    placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
                                            Close
                                        </button>
                                        <button type="submit" class="btn bg-teal legitRipple revert-no-nurses-submit">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- /revert all modal -->
                    @endcan
                    @can('applicant_export')
                        <a href="{{ route('export_nursing_home_applicants_cv') }}" class="btn bg-slate-800 legitRipple mr-2">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Export</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
            </div>
            <table class="table table-hover table-striped" id="no_nursing_applicant_sample_1">
                <thead>
                <tr>
                    @can('resource_No-Nursing-Home_revert-no-nursing-home')
                    <th>
                        <a href="#" style="font-size: 12px;">
                            <input type="checkbox" class="form-check-input-styled check-all-no-nurses" data-fouc />
                        </a>
                    </th>
                    @endcan
                    <th>Date</th>
                    <th>Time</th>
                    <th>Name</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Postcode</th>
                    <th>Phone#</th>
                    <th>Landline#</th>
                    <th>Source</th>
                    <th>Notes</th>
                    <th>History</th>
                </tr>
                </thead>
                <tbody>
                
                </tbody>
            </table>
        </div>
        <!-- /default ordering -->

    </div>
    <!-- /content area -->

    @endsection
@section('script')
<script>
    $(document).on('click', '.reject_history', function () {
        var applicant = $(this).data('applicant');
        $.ajax({
            url: "{{ route('rejectedHistory') }}",
            type: "post",
            data: {
                _token: "{{ csrf_token() }}",
                applicant: applicant
            },
            success: function(response){
                $('#applicant_rejected_history'+applicant).html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#applicant_rejected_history'+applicant).html(raw_html);
            }
        });
    });

    /*** Select All feature */
    $('.form-check-input-styled').uniform();
    $(document).on('click', '.check-all-no-nurses', function () {
        let $element = $(this);
        if($element.prop("checked") === true) {
            $(".checkbox-index").prop( "checked", true );
        } else {
            $(".checkbox-index").prop( "checked", false );
        }
    });

    function hideModal(modalId) {
        $(`#${modalId}`).modal('hide');
        $('.modal-backdrop').remove();
        $("body").removeClass("modal-open");
        $("body").removeAttr("style");
    }
    /*** Revert No Nurses */
    $(document).on('click', '.revert-no-nurses-submit', function (event) {
        event.preventDefault();

        let selectedApplicants = [];
        $('.checkbox-index:checkbox:checked').each(function(input) {
            selectedApplicants.push($(this).attr('value'));
        });
        let $revert_no_nurses_form = $('#revert_no_nurses_form');
        let $revert_no_nurses_alert = $('#revert_no_nurses_alert');
        if (selectedApplicants.length > 0) {
            let details = $.trim($("#details").val());
            if (details) {
                $.ajax({
                    url: "revert-applicants",
                    type: "POST",
                    data: $revert_no_nurses_form.serialize() + '&selectedApplicants=' + selectedApplicants + '&action=revert-no-nursing-home',
                    success: function (response) {
                        $.fn.dataTable.ext.errMode = 'throw';
                        table.draw();
                        $revert_no_nurses_alert.html(response);
                        setTimeout(function () { hideModal('revert_no_nurses_modal') }, 1500);
                    },
                    error: function (response) {
                        var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                        $revert_no_nurses_alert.html(raw_html);
                    }
                })
            } else {
                $revert_no_nurses_alert.html('<p class="text-danger">Kindly Provide Details</p>');
            }
        } else {
            $revert_no_nurses_form.trigger('reset');
            $revert_no_nurses_alert.html('<p class="text-danger">Kindly select applicant(s) first.</p>');
            setTimeout(function () { hideModal('revert_no_nurses_modal') }, 1500);
        }
        $revert_no_nurses_form.trigger('reset');
        setTimeout(function () { $revert_no_nurses_alert.html(''); }, 1500);
        return false;
    });
</script>
@endsection