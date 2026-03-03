@extends('layouts.app')

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
                    <span class="font-weight-semibold">Sales</span> - Notes
                </h5>
            </div>
        </div>

        <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
            <div class="d-flex">
                <div class="breadcrumb">
                    <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Sales</a>
                    <a href="#" class="breadcrumb-item">Current</a>
                    <span class="breadcrumb-item active">Open</span>
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
                <h5 class="card-title">Active Sale's Notes</h5>
            </div>

            <div class="card-body">
            </div>

            <table class="table table-striped datatable-sorting">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Note</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($open_sale_notes as $sale)
                <tr>
                    <td>{{ $sale->id}}</td>
					<td>{{ $sale->user->name}}</td>
                    <td>{{ $sale->sales_note_added_date}}</td>
                    <td>{{ $sale->sales_note_added_time}}</td>
                    <td>{{ $sale->sale_note }}</td>
                    <td>
                        @if($sale->status == 'active')
                        <h5><span class="badge badge-success d-block">{{ ucfirst($sale->status) }}</span></h5>
                        @else
                            <h5><span class="badge badge-danger d-block">{{ ucfirst($sale->status) }}</span></h5>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <!-- /default ordering -->

    </div>
    <!-- /content area -->

    @endsection()
