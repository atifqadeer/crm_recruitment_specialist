
<div id="exampleModal" style="z-index: 1040; position: fixed; margin-top:90px; right: 21px;width: 30%; display:none;">
   <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />
        
<div class="container" >
	<div class="toast bg-success text-white fade" id="toaster_success">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto"><i class="bi bi-chat-text-fill"></i> Message Sent Successfully.</strong>    
                </div>  
            </div>
<div class="row clearfix" >
    <div class="col-lg-12">
        <div class="card chat-app">
            <div class="chat">
                <div class="chat-header clearfix">
                    <div class="row">
                        <div class="col-lg-6">
                            <a href="javascript:void(0);" data-toggle="modal" data-target="#view_info">
                                <img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="avatar">
                            </a>
                            <div class="chat-about">
                                <h6 class="m-b-0" id="avatar_name"></h6>
                                <input type="hidden" id="notify_phone" name="notify_phone">
								<input type="hidden" id="notify_applicant_id" name="notify_applicant_id">
								<input type="hidden" id="notify_applicant_name" name="notify_applicant_name">
                            </div>
                        </div>
                        <div class="col-lg-6 hidden-sm text-right">
                            <a class="btn btn-outline-info" id="btnClose"><i class="fa fa-close"></i></a>
                        </div>
                    </div>
                </div>
                <div id="scroll">
                    <ul class="m-b-0 chat-history" id="testsss">
           
                    </ul>
                </div>
                <div class="chat-message clearfix">
                <form role="form" id="msg_form">
                        @csrf
                    <div class="input-group mb-0">
                        <div class="input-group-prepend">
                            
                            <a class="btn btn-info" id="sendMsg" ><i class="fa fa-send"></i></a>
                            <input type="hidden" id="applicant_chatbox_id" name="applicant_chatbox_id" />
                            <input type="hidden" id="applicant_chatbox_name" name="applicant_chatbox_name" />
                            <input type="hidden" id="applicant_chatbox_phone" name="applicant_chatbox_phone" />
                        </div>
                        <input type="text" class="form-control" name="msgText" id="msgText" placeholder="Enter text here...">                                    
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>






        </div>


