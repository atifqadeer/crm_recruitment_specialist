<!-- Main content -->
<div class="content-wrapper">

    <!-- Content area -->
      <!-- <div class="content"> -->
        <table class="table table-hover table-striped" id="close_sale_all_sample">
            <thead>
            <tr>
                <th>Created Date</th>
               <!-- <th>Updated Date</th> -->
                <th>Closed Date</th>
                <th>Agent By</th>
                <th>Job Title</th>
                <th>Head Office</th>
                <th>Unit</th>
                <th>Postcode</th>
                <th>Type</th>
                <th>Experience</th>
                <th>Qualification</th>
                <th>Salary</th>
                <th>Status</th>
				<th>Notes</th>
                @canany(['sale_open','sale_closed-sale-notes'])
                    <th>Action</th>
                @endcanany
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
       <!-- </div> -->
</div>
