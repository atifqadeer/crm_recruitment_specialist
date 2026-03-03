@extends('layouts.app')

@section('style')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<style>
    .input-group {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        background-color: #c3c3c3;
        border: 1px solid #ccc;
        border-right: none;
    }

    .input-group-text i {
        font-size: 16px;
        color: #ffffff;
    }

    .form-control {
        width: 100%;
        font-size: 14px;
        padding: 7px 10px;
        border: 1px solid #ccc;
        border-left: none;
    }

    .input-group-text,
    .form-control {
        height: 38px; /* Ensures both elements have the same height */
    }
	
	.highlight {
        background-color: #6c757d8c !important;
        font-weight: bold;
        color: #fff;
    }
</style>
<script>
     $(document).ready(function() {
        let nurseTable, nonnurseTable, historyTable;
        let inactivePostcodesNurse = [];
        let inactivePostcodesNonNurse = [];

        // Function to initialize DataTable
        function initializeDataTable(tableId) {
            return $(tableId).DataTable({
                destroy: true,
                searching: true,
                paging: true,
                ordering: true
            });
        }

        // Initialize all fetch functions
        fetchPostcodeHistory();
        fetchPostcodesNurse();
        fetchPostcodesNonnurse();

        // Function to fetch postcode history
        function fetchPostcodeHistory() {
            $.ajax({
                url: '/get-postcode-history',
                method: 'GET',
                success: function(data) {
                    console.log(data);
                    let tbody = '';
                    data.forEach(function(postcode, index) {
                        if (postcode.status === '0' && postcode.category.toLowerCase() === 'nurse') {
                            inactivePostcodesNurse.push(postcode.postcode);
                        } else if (postcode.status === '0' && postcode.category.toLowerCase() === 'non-nurse') {
                            inactivePostcodesNonNurse.push(postcode.postcode);
                        }

                        tbody += '<tr>';
                        tbody += '<td>' + (index + 1) + '</td>';
                        tbody += '<td class="postcode-text">' + postcode.postcode + '</td>';
                        tbody += '<td>' + postcode.category.toUpperCase() + '</td>';
                        tbody += '<td><span class="badge ' + (postcode.status === '1' ? 'badge-success' : 'badge-secondary') + '">' +
                                (postcode.status === '1' ? 'Active' : 'Inactive') + '</span></td>';
                        tbody += '<td>' + postcode.updated_at + '</td>';
                        tbody += '</tr>';
                    });

                    $('#historyTable tbody').html(tbody);
                    historyTable = initializeDataTable('#historyTable');

                    // Bind search for history table after table is initialized
                    $('#historySearch').on('keyup', function() {
                        historyTable.search(this.value).draw();
                    });

                    // After history is fetched, fetch nurse and non-nurse data
                    fetchPostcodesNurse();
                    fetchPostcodesNonnurse();
                },
                error: function() {
                    console.error('Error fetching postcode history');
                    toastr.error('Failed to load postcode history. Please try again.');
                }
            });
        }

        // Fetch unique postcodes for Nurse
        function fetchPostcodesNurse() {
            $.ajax({
                url: '/unique-postcodes-nurse',
                method: 'GET',
                success: function(data) {
                    let tbody = '';
                    data.forEach(function(postcode) {
                        const isInHistory = inactivePostcodesNurse.includes(postcode.postcode);
                        let highlightClass = isInHistory ? 'highlight' : '';

                        tbody += '<tr class="' + highlightClass + '">';
                        tbody += '<td><input type="checkbox" class="postcode-checkbox" value="' + postcode.postcode + '" ' + (postcode.checked ? 'checked' : '') + '></td>';
                        tbody += '<td class="postcode-text">' + postcode.postcode + '</td>';
                        tbody += '<td class="postcode-text">' + postcode.job_title + '</td>';
                        tbody += '</tr>';
                    });

                    $('#postcodesNurseTable tbody').html(tbody);
                    nurseTable = initializeDataTable('#postcodesNurseTable');

                    // Bind search for nurse table
                    $('#nurseSearch').on('keyup', function() {
                        nurseTable.search(this.value).draw();
                    });
                },
                error: function() {
                    console.error('Error fetching postcodes for Nurse');
                    toastr.error('Failed to load nurse postcodes. Please try again.');
                }
            });
        }

        // Fetch unique postcodes for Non-Nurse
        function fetchPostcodesNonnurse() {
            $.ajax({
                url: '/unique-postcodes-nonnurse',
                method: 'GET',
                success: function(data) {
                    let tbody = '';
                    data.forEach(function(postcode) {
                        const isInHistory = inactivePostcodesNonNurse.includes(postcode.postcode);
                        let highlightClass = isInHistory ? 'highlight' : '';

                        tbody += '<tr class="' + highlightClass + '">';
                        tbody += '<td><input type="checkbox" class="postcode-checkbox" value="' + postcode.postcode + '" ' + (postcode.checked ? 'checked' : '') + '></td>';
                        tbody += '<td class="postcode-text">' + postcode.postcode + '</td>';
                        tbody += '<td class="postcode-text">' + postcode.job_title + '</td>';
                        tbody += '</tr>';
                    });

                    $('#postcodesNonnurseTable tbody').html(tbody);
                    nonnurseTable = initializeDataTable('#postcodesNonnurseTable');

                    // Bind search for non-nurse table
                    $('#nonnurseSearch').on('keyup', function() {
                        nonnurseTable.search(this.value).draw();
                    });
                },
                error: function() {
                    console.error('Error fetching postcodes for Non-Nurse');
                    toastr.error('Failed to load non-nurse postcodes. Please try again.');
                }
            });
        }

        // Add selected postcodes for Nurse
        $('#addPostcodesNurse').click(function() {
            let selectedPostcodes = [];
            $('#postcodesNurseTable .postcode-checkbox:checked').each(function() {
                selectedPostcodes.push($(this).val());
            });

            $.ajax({
                url: '/add-postcodes-nurse',
                method: 'POST',
                data: {
                    postcodes: selectedPostcodes,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success('Postcodes added successfully!');
                    fetchPostcodeHistory();
                    fetchPostcodesNurse();
                    fetchPostcodesNonnurse();
                },
                error: function() {
                    console.error('Error adding postcodes');
                    toastr.error('Failed to add postcodes. Please try again.');
                }
            });
        });

        // Add selected postcodes for Non-Nurse
        $('#addPostcodesNonnurse').click(function() {
            let selectedPostcodes = [];
            $('#postcodesNonnurseTable .postcode-checkbox:checked').each(function() {
                selectedPostcodes.push($(this).val());
            });

            $.ajax({
                url: '/add-postcodes-non-nurse',
                method: 'POST',
                data: {
                    postcodes: selectedPostcodes,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success('Postcodes added successfully!');
                    fetchPostcodeHistory();
                    fetchPostcodesNurse();
                    fetchPostcodesNonnurse();
                },
                error: function() {
                    console.error('Error adding postcodes');
                    toastr.error('Failed to add postcodes. Please try again.');
                }
            });
        });

        // General search function
        function generalSearch(inputId, tableId, columnIndex = 1) {
            $(inputId).on('keyup', function() {
                var input = $(this).val().toUpperCase();
                var table = $(tableId);
                var tr = table.find('tr');

                for (let i = 0; i < tr.length; i++) {
                    let td = tr[i].getElementsByTagName("td")[columnIndex]; // Dynamic column index
                    if (td) {
                        let txtValue = td.textContent || td.innerText;
                        tr[i].style.display = txtValue.toUpperCase().indexOf(input) > -1 ? "" : "none";
                    }
                }
            });
        }

        // Attach search handler for each table
        generalSearch('#nurseSearch', '#postcodesNurseTable');
        generalSearch('#nonnurseSearch', '#postcodesNonnurseTable');
        generalSearch('#historySearch', '#historyTable');
    });
</script>
    
@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Follow Up</span> - Management
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Follow Up Management</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content area -->
        <div class="content">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Add PostCodes in Follow Up Sheet</h5>
                </div>
               <!--  <div class="card-body">
                    <span style="height:15px; width:15px; background-color: #394357;
                    border-radius: 50%; display: inline-block;"></span>
                   <span style="position: relative;bottom: 3px;">You may select up to 30 postcodes for Nurse & select up to 30 postcodes for Non-Nurse.</span>
                </div> -->
            </div>
            <div class="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header header-elements-inline">
                                <h5 class="card-title">Sales PostCodes (Nurse)</h5>
                            </div>
                            <div class="container">
                                 <div class="input-group mb-2">
                            		<span class="input-group-text"><i class="fas fa-search"></i></span>
									<input type="text" id="nurseSearch" class="form-control" placeholder="Search for Nurse Postcodes..." title="Type in a postcode">
								</div>
                                <div style="max-height: 52vh; overflow-y: auto;">
                                    <table id="postcodesNurseTable" class="table table-striped display" style="width: 100%;">
                                        <thead class="bg-secondary">
                                            <tr>
                                                <th>Select</th>
                                                <th>Postcode</th>
												<th>Job Title</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be filled by AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                                <button id="addPostcodesNurse" class="btn btn-primary my-2">Add Selected Postcodes</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header header-elements-inline">
                                <h5 class="card-title">Sales PostCodes (Non-Nurse)</h5>
                            </div>
                            <div class="container">
                                 <div class="input-group mb-2">
									<span class="input-group-text"><i class="fas fa-search"></i></span>
									<input type="text" id="nonnurseSearch" class="form-control" placeholder="Search for Non-Nurse Postcodes..." title="Type in a postcode">
								</div>
                                <div style="max-height: 52vh; overflow-y: auto;">
                                    <table id="postcodesNonnurseTable" class="table table-striped display" style="width: 100%;">
                                        <thead class="bg-secondary">
                                            <tr>
                                                <th>Select</th>
                                                <th>Postcode</th>
												<th>Job Title</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be filled by AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                                <button id="addPostcodesNonnurse" class="btn btn-primary my-2">Add Selected Postcodes</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header header-elements-inline">
                                <h5 class="card-title">History</h5>
                            </div>
                            <div class="container">
                                <div class="input-group mb-2">
									<span class="input-group-text"><i class="fas fa-search"></i></span>
									<input type="text" id="historySearch" class="form-control" placeholder="Search History..." title="Type in a postcode">
								</div>
                                <div style="max-height: 57.3vh; overflow-y: auto;">
                                    <table id="historyTable" class="table table-striped display" style="width: 100%;">
                                        <thead class="bg-secondary">
                                            <tr>
                                                <th>Sr.</th>
                                                <th>Postcode</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Dated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be filled by AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('script')
    <!-- Optionally, you can remove the initialization here since it's now done in the AJAX success callbacks -->
@endsection
