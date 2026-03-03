@extends('layouts.app')

@section('style')
<style>
    div.editable {
    height: 350px;
    overflow: scroll;
    overflow-x:hidden;
    border: 2px solid #ccc;
    padding: 40px 30px 40px 30px;
    background-color: rgb(246, 246, 246);
    border-radius: 5px;
    
}
.email_modal {
    padding: 50px;
}

strong {
  font-weight: bold;
  
}
</style>

    <script>
      $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
          $('#emails_sample_1').DataTable({
               "aoColumnDefs": [{"bSortable": false, "aTargets": [0,7]}],
               "bProcessing": true,
               "bServerSide": true,
               "aaSorting": [[0, "desc"]],
               "sPaginationType": "full_numbers",
               "sAjaxSource": "{{ route('get_emails') }}"
          });

          // table.destroy();

      });

    </script>

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
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Email</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Sent</a>
                        <span class="breadcrumb-item active">Emails</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">

                <table class="table" id="emails_sample_1">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Email Sent From</th>
                        <th>Email Sent To</th>
                        <th>CC Emails</th>
                        <th>Subject</th>
                        <th>Action</th>
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
   $(document).on('click', '.notes_history', function (event) {
        var emailData = $(this).data('email');

        $.ajax({
            url: "{{ route('get_email_details') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                email_id: emailData
            },
            success: function(response){

                // $('#office_notes_history'+emailData).find('span#title_text').text('aftab');
                $('#notes_history'+emailData).find('#modal_title').text(response.data.action_name);
                $('#notes_history'+emailData).find('#email_to').val(response.data.sent_to);
                $('#notes_history'+emailData).find('#cc_emails').val(response.data.cc_emails);
                $('#notes_history'+emailData).find('#email_title').val(response.data.subject);
                $('#notes_history'+emailData).find('#office_notes_history'+emailData).html(response.data.template);
                console.log(response.data);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#office_notes_history'+office).html(raw_html);
            }
        });
    });
</script>
@endsection
