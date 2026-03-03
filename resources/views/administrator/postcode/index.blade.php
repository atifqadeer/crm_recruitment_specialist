@extends('layouts.app')

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
                        <span class="font-weight-semibold">Postcode</span> - Finder
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Postcode Finder</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <!-- Inner container -->
            <div class="d-md-flex align-items-md-start">

                <!-- Left sidebar component -->
                <div
                    class="sidebar sidebar-light bg-transparent sidebar-component sidebar-component-left border-0 shadow-0 sidebar-expand-md">

                    <!-- Sidebar content -->
                    <div class="sidebar-content">

                        <!-- Filter -->
                        <div class="card">
                            <div class="card-header bg-transparent header-elements-inline">
                                <span class="text-uppercase font-size-sm font-weight-semibold">Find Postcode</span>
                            </div>

                            <div class="card-body">
                                <form action="{{ route('postcodeFinderResults') }}" method="post">
                                    @csrf()
                                    <div class="form-group form-group-feedback form-group-feedback-left">
                                        <input type="text" class="form-control" placeholder="Postcode" name="postcode" value="{{ isset($postcode) ? $postcode : '' }}" required>
                                        <span> <small class = "text-danger"> {{ $errors->first('postcode') }} </small> </span>
                                        <div class="form-control-feedback">
                                            <i class="icon-pin-alt text-muted"></i>
                                        </div>
                                    </div>
                                    <div class="form-group form-group-feedback form-group-feedback-left">

                                        <select class="form-control select-search" name="radius" required>
                                            <option value="">Select Radius</option>
											<option value="5" {{ isset($radius) && $radius == '5'? 'selected':'' }}>5 KMs</option>
                                            <option value="10" {{ isset($radius) && $radius == '10'? 'selected':'' }}>10 KMs</option>
                                            <option value="15" {{ isset($radius) && $radius == '15'? 'selected':'' }}>15 KMs</option>
                                            <option value="20" {{ isset($radius) && $radius == '20'? 'selected':'' }}>20 KMs</option>
                                            <option value="25" {{ isset($radius) && $radius == '25'? 'selected':'' }}>25 KMs</option>
                                            <option value="30" {{ isset($radius) && $radius == '30'? 'selected':'' }}>30 KMs</option>
                                            <option value="35" {{ isset($radius) && $radius == '35'? 'selected':'' }}>35 KMs</option>
                                            <option value="40" {{ isset($radius) && $radius == '40'? 'selected':'' }}>40 KMs</option>
                                            <option value="45" {{ isset($radius) && $radius == '45'? 'selected':'' }}>45 KMs</option>
                                            <option value="50" {{ isset($radius) && $radius == '50'? 'selected':'' }}>50 KMs</option>
                                        </select>
                                        <div class="form-control-feedback">
                                            <i class="icon-reading text-muted"></i>
                                        </div>
                                        <span> <small class = "text-danger"> {{ $errors->first('radius') }} </small> </span>
                                    </div>
									
									 <div class="form-group form-group-feedback form-group-feedback-left">
                                        <select class="form-control select-search" name="job_category" required>
                                            <option value="">Select Category</option>
                                          <!--  <option value="nurse" {{ isset($job_category) && $job_category == 'nurse'? 'selected':'' }}>Nurse</option>
                                            <option value="nonnurse" {{ isset($job_category) && $job_category == 'nonnurse'? 'selected':'' }}>Non-Nurse</option> -->
                                            <option value="specialist" {{ isset($job_category) && $job_category == 'specialist'? 'selected':'' }}>Specialist</option>
                                         <!--   <option value="chef" {{ isset($job_category) && $job_category == 'chef'? 'selected':'' }}>Chef</option>
                                            <option value="nursery" {{ isset($job_category) && $job_category == 'nursery'? 'selected':'' }}>Nursery</option> -->
                                        </select>
                                        <div class="form-control-feedback">
                                            <i class="icon-filter4 text-muted"></i>
                                        </div>
                                        <span> <small class = "text-danger"> {{ $errors->first('job_category') }} </small> </span>
                                    </div>
                                    <button type="submit" class="btn bg-teal legitRipple btn-block">
                                        <i class="icon-search4 font-size-base mr-2"></i>
                                        Find Postcode
                                    </button>
                                </form>
                            </div>
                        </div>
                        <!-- /filter -->
                    </div>
                    <!-- /sidebar content -->

                </div>
                <!-- /left sidebar component -->
                <!-- Right content -->
                <div class="flex-fill overflow-auto">
                    @if(isset($data['cordinate_results']))
                    @forelse($data['cordinate_results'] as $result)
                    <!-- Cards layout -->
                    <div class="card card-body">
                        <div class="media flex-column flex-sm-row">
                            <div class="mr-sm-3 mb-2 mb-sm-0">
                                <i class="icon-location4" style="font-size: xx-large;color: #009688;"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-title font-weight-semibold">
                                    <a href="javascript:void(0);"><?php echo $result->job_title_prof_res && $result->job_title_prof_res!=''? strtoupper($result->job_title).' ('.strtoupper($result->job_title_prof_res).') ': strtoupper($result->job_title);?>/{{ strtoupper($result->job_category) }}</a>
                                 @if($result->cv_limit == $result->send_cv_limit)
                                    <span class="badge badge-danger" style="font-size:90%">Limit Reached</span>
                                    @else
                                    <span class='badge badge-success' style='font-size:90%'>{{$result->send_cv_limit - $result->cv_limit." Cv's limit remaining  "}}</span>
                                    @endif
								</h6>
                                <ul class="list-inline list-inline-dotted text-muted mb-2">
                                    <li class="list-inline-item"><a href="{{ route('range',['id'=>$result->id,'radius'=>$radius]) }}" class="text-muted">{{ strtoupper($result->postcode) }}</a></li>
                                </ul>
                                <b>Benefits:</b>{{ $result->benefits }}
                                <div class="row" style="position: relative;top: 15px;">
                                    <div class="col-2">
                                        <b>Office</b> | {{ $result->office_name }}
                                    </div>
                                    <div class="col-2">
                                        <b>Unit</b> | {{ $result->unit_name }}
                                    </div>
                                    <div class="col-2">
                                        <b>Salary</b> | {{ $result->salary }}
                                    </div>
                                    <div class="col-2">
                                        <b>Qualification</b> | {{ $result->qualification }}
                                    </div>
                                    <div class="col-2">
                                        <b>Type</b> | {{ $result->job_type }}
                                    </div>
                                    <div class="col-2">
                                        <b>Time</b> | {{ $result->timing }}
                                    </div>
                                    <div class="col-2">
                                        <b>Experience</b> | {{ $result->experience }}

                                    </div>
									<div class="col-2 bntC">
                                        <a href="#" class="job_detail" data-id="{{$result->id}}">job_details</a>
                                    </div>
                                </div>
                            </div>
							<div class="ml-sm-3 mt-2 mt-sm-0">
                                <span class="badge bg-teal">Distance : {{ round(floatval(substr(strval($result->distance), 0, 6)),1) }}</span>
                            </div>
                            <div class="ml-sm-3 mt-2 mt-sm-0">
                                <span class="badge {{ ($result->days_diff=='true')? 'test_demo':'bg-teal'}}">{{ $result->posted_date }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- /cards layout -->
                    @empty
                        <div class="card card-body">
                            <p>No job found.</p>
                        </div>
                    @endforelse
                    @endif
                </div>
                <!-- /right content -->

            </div>
            <!-- /inner container -->
        </div>
        <!-- /content area -->
		
		 <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Job Description</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="media flex-column flex-md-row mb-4">
                            <div class="media-body">
                                <p id="dataPrint"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

@endsection
		@section('script')
    <script>
        $(document).ready(function() {
            $('.bntC').on('click','.job_detail',function (){
				 $('#dataPrint').html('Loading...');
                $('#exampleModal').modal('show');
               var saleId= $(this).data('id');
               var url="{{url('jobDetail')}}"+'/'+saleId
                $.ajax
                ({
                    type: "GET",
                    url:url,
                    // data: dataString,
                    success: function(response)
                    {
                        if(response.data.job_description != null)
                        $('#dataPrint').html(response.data.job_description);
                        else
                            $('#dataPrint').html('<div class="ml-md-5 text-center">Description of the job not available.</div>');

                    }
                });
            
            });
        });
    </script>
@endsection
