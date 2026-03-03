@extends('layouts.app')
@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Sales</span> - Closed Sales - Last 3 Months Closed Sales
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
						<a href="#" class="breadcrumb-item">Sales</a>
						<a href="#" class="breadcrumb-item">Closed Sales</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Last 3 Months Closed Sales</span>
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
                    {{-- <h5 class="card-title">All Closed Sales</h5> --}}
                    @if ($message = Session::get('error'))
                        <div class="alert alert-danger border-0 alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>×</span></button>
                            <span class="font-weight-semibold">Error!</span> {{ $message }}
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-highlight">
                        <li class="nav-item">
                            <a href="#close_sale_nurse" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="close_sale_nurse_sample">Nurse</a>
                        </li>
                        <li class="nav-item">
                            <a href="#close_sale_nonnurse" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="close_sale_nonnurse_sample">Non-Nurse</a>
                        </li>
                        <li class="nav-item">
                            <a href="#close_sale_specialist" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="close_sale_specialist_sample">Specialist</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="close_sale_nurse">
                            @include('inc/revamp_crm/closed_sales/last_3months_close_sale_nurse')
                        </div>
                        <div class="tab-pane" id="close_sale_nonnurse">
                            @include('inc/revamp_crm/closed_sales/last_3months_close_sale_nonnurse')
                        </div>
                        <div class="tab-pane" id="close_sale_specialist">
                            @include('inc/revamp_crm/closed_sales/last_3months_close_sale_specialist')
                        </div>
                    </div>
                </div>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection
@section('js_file')
    <script>
        $('#office_id').select2();
    </script>
    <script src="{{ asset('js/last_3months_close_sale.js') }}?v={{ time() }}"></script>
@endsection