@section('style')
<style>
body{margin-top:20px;}

.chat-online {
    color: #34ce57
}

.chat-offline {
    color: #e4606d
}

.chat-messages {
    display: flex;
    flex-direction: column;
    max-height: 800px;
    overflow-y: scroll
}

.chat-message-left,
.chat-message-right {
    display: flex;
    flex-shrink: 0
}

.chat-message-left {
    margin-right: auto
}

.chat-message-right {
    flex-direction: row-reverse;
    margin-left: auto
}
.py-3 {
    padding-top: 1rem!important;
    padding-bottom: 1rem!important;
}
.px-4 {
    padding-right: 1.5rem!important;
    padding-left: 1.5rem!important;
}
.flex-grow-0 {
    flex-grow: 0!important;
}
.border-top {
    border-top: 1px solid #dee2e6!important;
}
.navbar-light{
    display: none !important;
}
</style>
@endsection
		<div class="card">
			<div class="row g-0">
				
				<div class="col-12 col-lg-12 col-xl-12">


					<div class="position-relative">
						<div class="chat-messages p-4" id="inbox_data">

                        <input type="hidden" name="hidden_page" id="hidden_page" value="1" />
@forelse($data as $value)

    
    <div>
        @if($value->status=="outgoing")
        
            <div class="chat-message-right pb-4">
            <div>
                <img src="https://bootdey.com/img/Content/avatar/avatar1.png" class="rounded-circle mr-1" alt="Chris Wood" width="40" height="40">
                    <div class="text-muted small text-nowrap mt-2">{{ date('M j, Y', strtotime($value->date)) }}<br>{{ date('h:i A',strtotime($value->time))}}</div>
                    </div>
                    <div class="flex-shrink-1 bg-light rounded py-2 px-3 mr-3">
                        <div class="font-weight-bold mb-1" style="text-align:right;">{{$value->user->name}}</div>{{$value->message}}</div>
                </div>
        
        @else
        
            <div class="chat-message-left pb-4">
                <div>
                    <img src="https://bootdey.com/img/Content/avatar/avatar3.png" class="rounded-circle mr-1" alt="Sharon Lessman" width="40" height="40">
                    <div class="text-muted small text-nowrap mt-2">{{ date('M j, Y', strtotime($value->date)) }}<br>{{ date('h:i A',strtotime($value->time))}}</div>
                </div>
                <div class="flex-shrink-1 bg-light rounded py-2 px-3 ml-3">
                    <div class="font-weight-bold mb-1">{{$value->applicant_name . ' (' . strtoupper($value->job_title) . ')'}}</div>{{$value->message}}</div>
            </div>
            
        @endif
        </div>
@empty
                            <div class="alert alert-danger text-center">No data found</div>
                        @endforelse

</div>
					</div>

					

				</div>
			</div>
		</div>
        <div class="d-flex justify-content-center" id="pagination">
            {!! $data->links() !!}
        </div>
	
    