@extends('layouts.app')

@section('content')
    <div class="content-wrapper">

        <div class="page-header page-header-light">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicants</span> - Statistics - Details
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i>Applicants</a>
                        <a href="#" class="breadcrumb-item">Statistics</a>
                        <span class="breadcrumb-item active">Details</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            

            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">{{$applicant_type_home}} Applicants</h5>
                </div>
                <div class="card-header header-elements-inline">
                </div>
                <table class="table table-striped" id="applicant_stats_table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Source</th>
                        <th>Notes</th>            
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($userData as $data)
                        <tr>
                            <td>{{$data->applicant_added_date}}</td>
                            <td>{{$data->applicant_added_time}}</td>
                            <td>{{$data->applicant_name}}</td>
                            <td>{{$data->applicant_job_title}}</td>
                            <td>{{$data->job_category}}</td>
                            <td>{{$data->applicant_postcode}}</td>
                            <td>{{$data->applicant_phone}}</td>
                            <td>{{$data->applicant_source}}</td>
                            <td>{{$data->applicant_notes}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>


@endsection
@section('script')

<script>
       $(document).ready(function(){
           $('#applicant_stats_table').DataTable();
       });
    </script>
@endsection
