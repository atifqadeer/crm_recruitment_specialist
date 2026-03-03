<div id="crmExampleModal" style="z-index: 1040; position: fixed; margin-top:90px; right: 21px;width: 30%; display:none;" id="crm_scrol_event">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />
                
    <div class="container"  >
    <div class="toast bg-success text-white fade" id="crm_toaster_success">
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
                                    <h6 class="m-b-0" id="crm_msg_avatar_name"></h6>
                                    <!-- <small>Last seen: 2 hours ago</small> -->
                                </div>
                            </div>
                            <div class="col-lg-6 hidden-sm text-right">
                                <a class="btn btn-outline-info" id="crmBtnClose"><i class="fa fa-close" ></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="crm_chat_scroll">
                        <ul class="m-b-0 chat-history" id="testsss">
                        </ul>
                    </div>
                    <div class="chat-message clearfix">
                    <form role="form" id="crm_msg_form">
                            @csrf
                        <div class="input-group mb-0">
                            <div class="input-group-prepend">
                                
                                <a class="btn btn-info" id="crmSendMsg" ><i class="fa fa-send"></i></a>
                                <input type="hidden" id="crm_applicant_chatbox_id" name="crm_applicant_chatbox_id" />
                                <input type="hidden" id="crm_applicant_chatbox_name" name="crm_applicant_chatbox_name" />
                                <input type="hidden" id="crm_applicant_chatbox_phone" name="crm_applicant_chatbox_phone" />
                            </div>
                            <input type="text" class="form-control" name="crmMsgText" id="crmMsgText" placeholder="Enter text here...">                                    
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    
    
    
    
    
            </div>
    
    
    