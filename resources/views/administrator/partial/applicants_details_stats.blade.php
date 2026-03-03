@if($page_name=='crm_prestart_attended' || $page_name=='crm_start_date' || $page_name == 'crm_confirmation' || 
$page_name == 'crm_request' || $page_name=='quality_cleared' || $page_name=='crm_invoice' || $page_name == 'crm_paid' || $page_name=='crm_req_reject'
|| $page_name=='crm_rebook' || $page_name=='crm_not_attended' || $page_name=='crm_start_date_hold' || $page_name=='crm_declined' || $page_name=='crm_dispute' || $page_name=='crm_revert' || $page_name=='quality_revert')
<div class="card-header header-elements-sm-inline" style="padding-top: 0 !important; padding-bottom: 5px !important;">
    <h5 class="card-title">{{$page_title}} CV Applicants in CRM Stages ({{$detail_stats_nurse+$detail_stats_non_nurse+$detail_stats_non_nurse_specialist}})</h5>
</div>

<div class="card-body d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 15px; !important;">
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fas fa-user-nurse text-teal-400" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
              
                 @if($page_name=='quality_revert'|| $page_name=='crm_revert')

                <a href="{{route('revertCv', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'nurse','page_name'=>$page_name]) }}" target="_blank" class="nav-link">
                <h6 class="font-weight-semibold mb-0" id="custom_quality_rejected sanat">{{ $detail_stats_nurse }}</h6>
                    <span class="text-muted">Nurses</span>
                </a>
                @else
                    <a href="{{route('statsDetailNurse', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'nurse']) }}" target="_blank" class="nav-link">
                        <h6 class="font-weight-semibold mb-0" id="custom_quality_rejected">{{ $detail_stats_nurse }}</h6>
                        <span class="text-muted">Nurses</span>
                    </a>
                @endif
               
        </div>
    </div>

    <div class="col-md-3 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-user text-secondary" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
            
                     @if($page_name=='quality_revert'|| $page_name=='crm_revert')
                        <a href="{{route('revertCv', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'non-nurse','page_name'=>$page_name]) }}" target="_blank" class="nav-link">

                <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ $detail_stats_non_nurse }}</h6>
                <span class="text-muted">Non Nurses rv</span>
                </a>
                @else
                    <a href="{{route('statsDetailNurse', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'non-nurse']) }}" target="_blank" class="nav-link">

                        <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ $detail_stats_non_nurse }}</h6>
                        <span class="text-muted">Non Nurses</span>
                    </a>
                @endif
   
        </div>
    </div>
	
	<div class="col-md-3 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-user text-secondary" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
            
                    @if($page_name=='quality_revert'|| $page_name=='crm_revert')
                        <a href="{{route('revertCv', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'specialist','page_name'=>$page_name]) }}" target="_blank" class="nav-link">

                    <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ $detail_stats_non_nurse_specialist }}</h6>
                <span class="text-muted">Specialist Non Nurses</span>
                    </a>
                @else
			                    <a href="{{route('statsDetailNurse', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'specialist']) }}" target="_blank" class="nav-link">

                    <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ $detail_stats_non_nurse_specialist }}</h6>
                    <span class="text-muted">Specialist Non Nurses</span>
			</a>
                @endif
              
        </div>
    </div>
	
	

</div>
@if($source_res!='')
<div class="card-body d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 15px; !important;">
    
@foreach ($source_res as $value)
		<a href="{{route('app_crm_home_detail_stats', ['user_home_type' => encrypt($value->applicant_source), 'stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type]) }}" target="_blank" class="nav-link">
    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-teal-400"></i>
        </div>

        <div class="ml-3">
            <span class="text-muted">{{ $value->applicant_source }} ({{$value->count}})</span>
        </div>
        
    </div>
</a>
	@if($source_res!=='')
<a href="{{route('app_crm_home_detail_stats', ['user_home_type' => encrypt($value->applicant_source), 'stats_date' => $stats_date,'range' => $range, 'stats_type' => $stats_type, 'unknown_src' => 'unknown_src']) }}" target="_blank" class="nav-link">

<div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
    <div>
        <i class="fa fa-check-square text-teal-400"></i>
    </div>

    <div class="ml-3">
        <span class="text-muted">Unknown Source ({{$unknown_source_res}})</span>
    </div>
    

</div>
</a>
@endif
@endforeach

@endif
@elseif($page_name=='crm_reject')
<div class="card-header header-elements-sm-inline" style="padding-top: 0 !important; padding-bottom: 5px !important;">
    <h5 class="card-title">{{$page_title}} CV Applicants in CRM Stages ({{$detail_stats_nurse+$detail_stats_non_nurse+$detail_stats_non_nurse_specialist}})</h5>
</div>

<div class="card-body d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 15px; !important;">
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fas fa-user-nurse text-teal-400" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
             
                <a href="{{route('statsDetailNurse', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'nurse']) }}" target="_blank" class="nav-link">

                <h6 class="font-weight-semibold mb-0" id="custom_quality_rejected sanat">{{ $detail_stats_nurse }}</h6>
                    <span class="text-muted">Nurses</span>
                </a>
               
        </div>
    </div>

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-user text-secondary" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
         	
			  
                    <a href="{{route('statsDetailNurse', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'non-nurse']) }}" target="_blank" class="nav-link">

                <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ $detail_stats_non_nurse }}</h6>
                <span class="text-muted">Non Nurses</span>
                </a>
               
        </div>

    </div>
	
	<div class="col-md-3 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-user text-secondary" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
             
                    <a href="{{route('statsDetailNurse', ['stats_date' => $stats_date,'range' => $range,'stats_type' =>$stats_type,'job_category'=>'specialist']) }}" target="_blank" class="nav-link">

                    <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ $detail_stats_non_nurse_specialist }}</h6>
                <span class="text-muted">Specialist Non Nurses</span>
                    </a>
              
        </div>
    </div>

</div>
<hr>
<div class="d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 10px; !important;">
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-teal-400"></i>
        </div>

        <div class="ml-3">
			          
                           <a href="{{route('positionCheck', ['type' => 'position_filled','stats_date' => $stats_date,'range' => $range,'job_category'=>'nurse'])}}" target="_blank">
                               <span class="text-muted">Position Filled({{$reasons['nurse_position_filled']}})</span>
                           </a>
                         
        </div>
        
    </div>
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-secondary" ></i>
        </div>

        <div class="ml-3">
            
                            <a href="{{route('positionCheck', ['type' => 'position_filled','stats_date' => $stats_date,'range' => $range,'job_category'=>'non-nurse'])}}" target="_blank">
                            <span class="text-muted">Position Filled({{$reasons['non-nurse_position_filled']}})</span>
                            </a>
                        
        </div>

    </div>
    

</div>
<div class="d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 10px; !important;">
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-teal-400"></i>
        </div>

        <div class="ml-3">
            
                            <a href="{{route('positionCheck', ['type' => 'agency','stats_date' => $stats_date,'range' => $range,'job_category'=>'nurse'])}}" target="_blank">
                            <span class="text-muted">Sent By Agency({{$reasons['nurse_agency']}})</span>
                            </a>
                            
        </div>
        
    </div>
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-secondary" ></i>
        </div>

        <div class="ml-3">
           
                            <a href="{{route('positionCheck', ['type' => 'agency','stats_date' => $stats_date,'range' => $range,'job_category'=>'non-nurse'])}}" target="_blank">

                            <span class="text-muted">Sent By Agency({{$reasons['non-nurse_agency']}})</span>
                            </a>
                           
        </div>

    </div>
    

</div>
<div class="d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 10px; !important;">
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-teal-400"></i>
        </div>

        <div class="ml-3">
           
                            <a href="{{route('positionCheck', ['type' => 'manager','stats_date' => $stats_date,'range' => $range,'job_category'=>'nurse'])}}" target="_blank">
                            <span class="text-muted">Reject By Manager({{$reasons['nurse_manager']}})</span>
                            </a>
                       
        </div>
        
    </div>
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-secondary" ></i>
        </div>

        <div class="ml-3">
                
                            <a href="{{route('positionCheck', ['type' => 'manager','stats_date' => $stats_date,'range' => $range,'job_category'=>'non-nurse'])}}" target="_blank">
                            <span class="text-muted">Reject By Manager({{$reasons['non-nurse_manager']}})</span>
                            </a>
                           
        </div>

    </div>
    

</div>
<div class="d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 10px; !important;">
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-teal-400"></i>
        </div>

        <div class="ml-3">
           
                            <a href="{{route('positionCheck', ['type' => 'no_response','stats_date' => $stats_date,'range' => $range,'job_category'=>'nurse'])}}" target="_blank">

                            <span class="text-muted">No Response({{$reasons['nurse_no_response']}})</span>
                            </a>
                           
        </div>
        
    </div>
    

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="	fa fa-check-square text-secondary" ></i>
        </div>

        <div class="ml-3">
            
                            <a href="{{route('positionCheck', ['type' => 'no_response','stats_date' => $stats_date,'range' => $range,'job_category'=>'non-nurse'])}}" target="_blank">

                            <span class="text-muted">No Response({{$reasons['non-nurse_no_response']}})</span>
                            </a>
                           
        </div>

    </div>
    

</div>
@elseif($page_name=='nursing_home' || $page_name=='non_nursing_home')
<div class="card-header header-elements-sm-inline" style="padding-top: 0 !important; padding-bottom: 5px !important;">
    <h5 class="card-title">{{$home}} CV Applicants ({{ ($detail_stats_home!=0)?$detail_stats_home:0}})</h5>
</div>
@if($home == 'Nurse')
<div class="card-body d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 15px; !important;">
    
    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fas fa-user-nurse text-teal-400" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
            <h6 class="font-weight-semibold mb-0" id="custom_quality_rejected">{{ ($detail_stats_home!=0)?$detail_stats_home:0 }}</h6>
            <span class="text-muted">Nurse</span>
        </div>
    </div>
</div>
@else
<div class="card-body d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 15px; !important;">

    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-user text-secondary" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
            <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ ($detail_stats_home!=0)?$detail_stats_home:0 }}</h6>
            <span class="text-muted">Non Nurses</span>
        </div>
    </div>
    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-user-circle text-secondary" style="font-size: 30px;"></i>
        </div>

        <div class="ml-3">
            <h6 class="font-weight-semibold mb-0" id="custom_quality_cleared">{{ ($specilaist!=0)?$specilaist:0 }}</h6>
            <span class="text-muted">Specialist Non Nurses</span>
        </div>
    </div>
</div>
    @endif

<hr>
@if($source_res!='')
<div class="card-body d-md-flex align-items-md-center justify-content-md-between flex-md-wrap" style="padding-bottom: 15px; !important;">
    
@foreach ($source_res as $value)
<a href="{{route('user_home_detail_stats', ['user_home_type' => encrypt($value->applicant_source), 'no_of_app' => $value->count,
    'stats_type_stage' => 'quality_cleared', 'home' => $home, 'range' => $range, 'stats_date' => $stats_date,'updateRecord'=>$updateRecord,'unknown_src'=>null]) }}" target="_blank" class="nav-link">
    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
        <div>
            <i class="fa fa-check-square text-{{$home=='Nurse'?'teal-400':'secondary'}}"></i>
        </div>

        <div class="ml-3">
            <span class="text-muted">{{ $value->applicant_source }} ({{$value->count}})</span>
        </div>
        
    </div>
	</a>
@endforeach
	<a href="{{route('user_home_detail_stats', ['user_home_type' => encrypt($value->applicant_source), 'no_of_app' => $value->count,
    'stats_type_stage' => 'quality_cleared', 'home' => $home, 'range' => $range, 'stats_date' => $stats_date,'updateRecord'=>$updateRecord, 'unknown_src' => 'unknown_src']) }}" target="_blank" class="nav-link">
    <div class="col-md-4 d-flex align-items-center mb-3 mb-md-0">
    <div>
        <i class="fa fa-check-square text-{{$home=='Nurse'?'teal-400':'secondary'}}"></i>
    </div>

    <div class="ml-3">
        <span class="text-muted">Unknown Source ({{ $other_source_res }}) </span>
    </div>
    
</div>
		</a>

</div>


@endif
@endif


