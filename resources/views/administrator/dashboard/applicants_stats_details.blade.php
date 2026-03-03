@extends('layouts.app')

@section('content')
    <div class="content-wrapper">

        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Dashboard - Applicants Statistics</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Dashboard</a>
                        <a href="#" class="breadcrumb-item">Applicants</a>
						<a href="#" class="breadcrumb-item">Statistics</a>
                        <span class="breadcrumb-item active">Details</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">

            <div class="card border-top-teal-400 border-top-3">
                <div class="card-header header-elements-inline">
                    <div class="header-elements col-md-10 justify-content-end">
                        @if ($message = Session::get('error'))
                            <div class="alert alert-danger border-0 alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Error!</span> {{ $message }}
                            </div>
                            @php(Session::forget('error'))
                        @endif
                        @if ($message = Session::get('success'))
                            <div class="alert alert-success border-0 alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Success!</span> {{ $message }}
                            </div>
                            @php(Session::forget('success'))
                        @endif
                    </div>
                </div>
				<div class="card-body">
                    @can('applicant_export')
                    <a href="javascript:;"  class="btn bg-slate-800 legitRipple float-right" data-date="{{$stats_date}}" data-home="{{$home}}" data-range="{{$range}}" data-applicanttype="{{$applicant_type}}" data-statstype="{{$stats_type_stage}}" data-unknown="{{$unknown_src}}" id="details_export" style="margin-right:20px;">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Export</a>
                    @endcan
                </div>

                <table class="table table-striped" id="applicant_stats_table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
						<th>Sent By</th>
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
                            <td>{{ date('jS M, Y',strtotime($data->applicant_added_date))}}</td>
                            <td>{{$data->applicant_added_time}}</td>
							<td>
								@if(!empty($data['cv_notes']) && isset($data['cv_notes'][0]['user']['name']))
									{{ $data['cv_notes'][0]['user']['name'] }}
								@else
									-
								@endif
							</td>
                            <td>{{ ucwords($data->applicant_name) }}</td>
                            <td>{{ strtoupper($data->applicant_job_title) }}</td>
                            <td>{{ strtoupper($data->job_category) }}</td>
                            <td>{{ strtoupper($data->applicant_postcode) }}</td>
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
	$(document).ready(function() {
    $("a[id=details_export]").on('click',function() {
        var date_val = $(this).data('date'); 
        var home = $(this).data('home'); 
        var range = $(this).data('range'); 
        var stats_type_stage = $(this).data('statstype'); 
        var applicant_type = $(this).data('applicanttype');
        var unknown_src = $(this).data('unknown');
        // console.log(date_val+' and '+home+' and '+range+' and '+stats_type_stage+' and '+unknown_src+' and '+applicant_type);
        $.ajax({
                url: "{{ route('applicants_stats_details_export') }}",
                type: "get",
                cache: false,
				dataType: "json",
                data: {date_val:date_val, home:home, range:range,stats_type_stage:stats_type_stage,applicant_type:applicant_type,unknown_src:unknown_src},
                success: function (response) {
                    var a = document.createElement("a");
                      a.href = response.file; 
                      a.download = response.name;
                      document.body.appendChild(a);
                      a.click();
                      a.remove();
            },
        error: function (response) {
            console.log(response.test);
            alert('Sms sent... unable to save message in database...');
        }
    });
 
    });
});
    </script>
@endsection
