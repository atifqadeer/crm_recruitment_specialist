@extends('layouts.app')
@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark  has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Quality</span> - Sales
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Quality</a>
                        <a href="#" class="breadcrumb-item">Sales</a>
                        <span class="breadcrumb-item active">Cleared</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <div class="header-elements col-md-10 justify-content-end">
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-highlight">
                        {{--                        <li class="nav-item">--}}
                        {{--                            <a href="#close_sale_all" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="close_sale_all_sample">All</a>--}}
                        {{--                        </li>--}}
                        <li class="nav-item">
                            <a href="#cleared_sale_nurse" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="quality_cleared_sale_nurse_sample">Nurse</a>
                        </li>
                        <li class="nav-item">
                            <a href="#cleared_sale_nonnurse" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="quality_cleared_sale_nonnurse_sample">Non-Nurse</a>
                        </li>
                        <li class="nav-item">
                            <a href="#cleared_sale_specialist" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="quality_cleared_sale_specialist_sample">Specialist</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{--                        <div class="tab-pane active" id="close_sale_all">--}}
                        {{--                            @include('inc/revamp_crm/close_sale_all')--}}
                        {{--                        </div>--}}
                        <div class="tab-pane active" id="cleared_sale_nurse">
                            @include('inc/revamp_crm/quality_cleared_sale_nurse')
                        </div>
                        <div class="tab-pane" id="cleared_sale_nonnurse">
                            @include('inc/revamp_crm/quality_cleared_sale_nonnurse')
                        </div>
                        <div class="tab-pane" id="cleared_sale_specialist">
                            @include('inc/revamp_crm/quality_cleared_sale_specialist')
                        </div>
                    </div>
                </div>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection
@section('js_file')
    <script src="{{ asset('js/quality_cleared_sale.js') }}?v={{ time() }}"></script>
@endsection