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
                    <h5 class="card-title">Applicants --  </h5>
                </div>
                <div class="card-header header-elements-inline">
                </div>
                <table class="table table-striped" id="applicant_stats_table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Sent by</th>
                        <th>Name</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Position Type</th>
                        <th>Notes</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($reasons as $data)
                @php
                $agen_name=\Horsefly\User::where('id',$data->user_id)->first();
                @endphp
                        <tr>
                            <td>{{$data['crm_rejected_cv_time']}}</td>
                            <td>{{$data['crm_rejected_cv_date']}}</td>
                            <td>{{$agen_name->name}}</td>
                            <td>{{$data['applicant_name']}}</td>
                            <td>{{$data['applicant_job_title']}}</td>
                            <td>{{$data['job_category']}}</td>
                            <td>{{$data['applicant_postcode']}}</td>
                            <td>{{$data['applicant_number']}}</td>
                            <td>{{$data['reason']}}</td>
                            <td>{{$data['crm_rejected_cv_note']}}</td>
                            {{--                            <td><a href="{{url('notes_detail_applicant/'.$data->id)}}">ALl Notes</a></td>--}}

{{--                            @if($noteLatest!= null)--}}
{{--                                <td>{{$noteLatest->details}}|{{$noteLatest->moved_tab_to}}</td>--}}
{{--                            @else--}}
{{--                                <td>{{$data->applicant_notes}}</td>--}}

{{--                            @endif--}}
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
