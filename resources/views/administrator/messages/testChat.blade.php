@extends('layouts.app')

<style>
    .container{max-width:1800px; margin:auto;}
    img{ max-width:100%;}
    .inbox_people {
        background: #f8f8f8 none repeat scroll 0 0;
        float: left;
        overflow: hidden;
        width: 40%; border-right:1px solid #c4c4c4;
        /*background-color: white;*/
    }
    .inbox_msg {
        border: 1px solid #c4c4c4;
        clear: both;
        overflow: hidden;
        /*background-color: white;*/
    }
    .top_spac{ margin: 20px 0 0;}


    .recent_heading {float: left; width:40%;}
    .srch_bar {
        display: inline-block;
        text-align: right;
        width: 60%; padding;
    }
    .headind_srch{ padding:10px 29px 10px 20px; overflow:hidden; border-bottom:1px solid #c4c4c4;}

    .recent_heading h4 {
        color: #05728f;
        font-size: 21px;
        margin: auto;
    }
    .srch_bar input{ border:1px solid #cdcdcd; border-width:0 0 1px 0; width:80%; padding:2px 0 4px 6px; background:none;}
    .srch_bar .input-group-addon button {
        background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
        border: medium none;
        padding: 0;
        color: #707070;
        font-size: 18px;
    }
    .srch_bar .input-group-addon { margin: 0 0 0 -27px;}

    .chat_ib h5{ font-size:15px; color:#464646; margin:0 0 8px 0;}
    .chat_ib h5 span{ font-size:13px; float:right;}
    .chat_ib p{ font-size:14px; color:#989898; margin:auto}
    .chat_img {
        float: left;
        width: 11%;
    }
    .chat_ib {
        float: left;
        padding: 0 0 0 15px;
        width: 88%;
    }

    .chat_people{ overflow:hidden; clear:both;}
    .chat_list {
        border-bottom: 1px solid #c4c4c4;
        margin: 0;
        padding: 18px 16px 10px;
    }
    .inbox_chat { height: 550px; overflow-y: scroll;}

    .active_chat{ background:#ebebeb;}

    .incoming_msg_img {
        display: inline-block;
        width: 6%;
    }
    .received_msg {
        display: inline-block;
        padding: 0 0 0 10px;
        vertical-align: top;
        width: 92%;
    }
    .received_withd_msg p {
        background: #ebebeb none repeat scroll 0 0;
        border-radius: 3px;
        color: #646464;
        font-size: 14px;
        margin: 0;
        padding: 5px 10px 5px 12px;
        width: 100%;
    }
    .time_date {
        color: #747474;
        display: block;
        font-size: 12px;
        margin: 8px 0 0;
    }
    .received_withd_msg { width: 57%;}
    .mesgs {
        float: left;
        padding: 30px 15px 0 25px;
        width: 60%;
    }

    .sent_msg p {
        background: #05728f none repeat scroll 0 0;
        border-radius: 3px;
        font-size: 14px;
        margin: 0; color:#fff;
        padding: 5px 10px 5px 12px;
        width:100%;
    }
    .outgoing_msg{ overflow:hidden; margin:26px 0 26px;}
    .sent_msg {
        float: right;
        width: 46%;
    }
    .input_msg_write input {
        background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
        border: medium none;
        color: #4c4c4c;
        font-size: 15px;
        min-height: 48px;
        width: 100%;
    }

    .type_msg {border-top: 1px solid #c4c4c4;position: relative;}
    .msg_send_btn {
        background: #05728f none repeat scroll 0 0;
        border: medium none;
        border-radius: 50%;
        color: #fff;
        cursor: pointer;
        font-size: 17px;
        height: 33px;
        position: absolute;
        right: 0;
        top: 11px;
        width: 33px;
    }
    .messaging { padding: 0 0 50px 0;}
    .msg_history {
        height: 516px;
        overflow-y: auto;
    }



    .active-chat {
        background-color: #d4edda; /* Change to your desired color */
        color: #fff; /* Text color for active chat item */
    }

    #loadingIcon {
        display: none;
        text-align: center;
        position: absolute; /* Add this */
        top: 50%; /* Add this */
        left: 50%; /* Add this */
        transform: translate(-50%, -50%); /* Add this */
    }
    .count_hidden{
        display: none;
    }
    .page-item .page-link{
        /*background-color: #37474f !important;*/
        color: black;
        border-color: #37474f !important;
    }
    .page-item.active .page-link{
        background-color: #37474f !important;
        color: #fff;
        border-color: #37474f !important;
    }
</style>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" type="text/css" rel="stylesheet">
@section('content')
    <!-- Main content -->
    <div class="content-wrapper" style="margin-top:60px;">
        <div class="container" style="width: 100%;">
            <h3 class=" text-center">Messaging</h3>
            <div class="messaging" style="width: 120%;margin-left: -120px;">
                <div class="inbox_msg">
                    <div class="inbox_people">
                        <div class="headind_srch">
                            <div class="recent_heading">
                                <h4>Recent</h4>
                            </div>
                            <div class="srch_bar">
                                {{--                                        <input type="text" name="search" placeholder="Search">--}}
                                {{--                                        <button type="submit">Search</button>--}}
                                <div class="stylish-input-group">
                                    <form action="{{ route('inbox') }}" method="get" id="searchForm">

                                        <div class="input-group">
                                            <input type="text" class="form-control search-input" name="search" placeholder="Applicant's search" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                            <div class="input-group-append">
                                                <button id="searchButton" class="btn btn-outline alpha-teal text-teal-400 border-teal-400 border-2 float-right legitRipple " type="submit" >
                                                    <i class="{{$iconClass}}" id="searchIcon"></i></button>

                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                        <div class="inbox_chat" id="myList" style="border-bottom: 1px solid; border-color: beige" >
                            @if($latestMessages->count()>0)
                            @foreach($latestMessages as $chat)
                           <a href="#" class="d-flex justify-content-between">
{{--                            <div class="chat_list"  data-id="{{$chat->id}}">--}}
                            <div class="chat_list"  data-id="{{$chat->applicant_id}}">
                                <div class="chat_people">
                                    <div class="chat_img"> <img src="https://bootdey.com/img/Content/avatar/avatar3.png"  class="rounded-circle mr-1" alt="sunil"> </div>
                                    <div class="chat_ib">
                                        @php
//                                            $countUnreadMessage=\Horsefly\Applicant_message::where('status','incoming')->where('applicant_id',$chat->id)->where('is_read',0)->count();
                                            $countUnreadMessage=\Horsefly\Applicant_message::where('status','incoming')->where('applicant_id',$chat->applicant_id)->where('is_read',0)->count();

                                        @endphp
                                        <h5>{{$chat->applicants->applicant_name}} <span class="chat_date">{{\Carbon\Carbon::parse($chat->time)->format('h:i A')}}</span>&nbsp;&nbsp;
{{--                                        <h5>{{$chat->applicant_name}} <span class="chat_date">{{\Carbon\Carbon::parse($chat->applicant_messages->first()->time)->format('h:i A')}}</span>&nbsp;&nbsp;--}}
                                            @if($countUnreadMessage !=0)
                                            <span class="badge bg-danger float-end countA" title="Total message unread" id="countHidden" style="margin-right: 82px">{{$countUnreadMessage}}</span>
                                            @endif
                                        </h5>
                                        <p>{{ str_limit(strip_tags($chat->message), 30) }}.</p>
{{--                                        <p>{{ str_limit(strip_tags($chat->applicant_messages->first()->message), 30) }}.</p>--}}
                                    </div>
                                </div>
                            </div>
                            </a>
                            @endforeach

                        </div>
                        <br>
                        {{ $latestMessages->links() }}


                        @else
                            <div style="margin-left: 180px; margin-top: 20px;">NO record found</div>
                        @endif
                    </div>

                    <div class="mesgs">
                        <div class="msg_history" id="user_name">

                        </div>
                        <div class="type_msg">
                            <form action="" id="" method="">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                            <input type="hidden" name="applicant_id" value="" id="applicant_id">
                            <div class="input_msg_write">
                                <textarea class="form-control" name="textMessage" id="textMessage" rows="4" style="margin-bottom: 10px" placeholder="Write the message here"></textarea>
                                <div class="text-right">
                                    <button class="btn bg-teal" id="sendMessageApplicant"><i class="icon-paperplane"></i> Send</button>
                                </div>
                                <div id="loadingIcon" style="display: none; text-align: center;">
                                    <i class="fa fa-spinner fa-spin"></i> Sending...
                                </div>
                            </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>


        @endsection

    @section('script')

        <script>
            $(document).ready(function() {

                // loadChatList(); // Load the initial page of chat data
                // var itemsPerPage = 10; // Number of items per page
                // function loadChatList() {
                //     $.ajax({
                //         type: 'GET',
                //         url: '/getLatestMessages?page=',// Use the appropriate URL for your Laravel route
                //         dataType: 'json',
                //         success: function (response) {
                //             var latestMessages = response.latestMessages.data; // Access the data array
                //             var chatList = $('.chat_list');
                //
                //             chatList.empty(); // Clear the existing chat list.
                //
                //             $.each(latestMessages, function (index, chat) {
                //                 var firstMessage = chat.applicant_messages[0]; // Get the first message
                //                 var unreadMessages = countUnreadMessages(chat.applicant_messages);
                //
                //                 var chatHtml = '<a href="#" class="d-flex justify-content-between">';
                //                 chatHtml += '<div class="chat_list" data-id="' + chat.id + '">';
                //                 chatHtml += '<div class="chat_people">';
                //                 chatHtml += '<div class="chat_img"><img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="sunil"></div>';
                //                 chatHtml += '<div class="chat_ib">';
                //                 chatHtml += '<h5>' + chat.applicant_name + ' <span class="chat_date">' + firstMessage.time + '</span>&nbsp;&nbsp;';
                //                 if (unreadMessages > 0) {
                //                     chatHtml += '<span class="badge bg-danger float-end" title="Total message unread" style="margin-right: 82px">' + unreadMessages + '</span>';
                //                 }
                //                 chatHtml += '</h5>';
                //                 chatHtml += '<p>' + firstMessage.message.substring(0, 30) + '.</p>';
                //                 chatHtml += '</div>';
                //                 chatHtml += '</div>';
                //                 chatHtml += '</div>';
                //                 chatHtml += '</a>';
                //
                //                 chatList.append(chatHtml); // Append the chat to the chat list.
                //             });
                //             // Display pagination controls
                //             displayPage(currentPage, itemsPerPage);
                //         },
                //         error: function (error) {
                //             console.log(error);
                //         }
                //     });
                //     function countUnreadMessages(messages) {
                //         var unreadCount = 0;
                //         for (var i = 0; i < messages.length; i++) {
                //             if (messages[i].is_read === 0 && messages[i].status === "incoming") {
                //                 unreadCount++;
                //             }
                //         }
                //         return unreadCount;
                //     }
                // }

            });






            $(document).ready(function(){

                // chat list show js code




                let pollingInterval;
                function pollForNewMessages(applicantId) {
                    clearInterval(pollingInterval); // Clear the previous polling interval

                    pollingInterval =setInterval(function () {
                        $.ajax({
                            {{--url: "{{ route('applicantChatHistory') }}",--}}
                            url: "applicantChatHistory/"+applicantId,
                            type : 'get',
                            success:function(responses){
                                var dataChat=responses.data;
                                // console.log(dataChat);
                                $('#applicant_id').val(applicantId);
                                var html='';
                                // for (var i = dataCHat.length  1; i >= 0; i++) {
                                for (var i = 0; i < dataChat.length; i++) {
                                    var responseData = dataChat[i];
                                    // console.log(responseData.status);
                                    if(responseData.status=="incoming") {
                                        // console.log('Received data incoming:', responseData);
                                        html +=' <div class="incoming_msg">';
                                        html +=' <div class="incoming_msg_img"> <img src="https://bootdey.com/img/Content/avatar/avatar3.png" alt="sunil"  class="rounded-circle mr-1"> </div>';
                                        html +='  <div class="received_msg">';
                                        html +='<div class="received_withd_msg">';
                                        html +=' <p><small style="color: black"><b>'+responseData.applicants.applicant_name+'</b></small><br>'+responseData.message+'</p>';
                                        html +=' <span class="time_date" style="float: right"> '+ responseData.FormattedTime + '  |  ' +responseData.date+'</span>';
                                        html +=' </div>';
                                        html +=' </div>';
                                        html +=' </div>';
                                    }else {
                                        // console.log('Received data:', blanchedalmond);
                                        html +='<div class="outgoing_msg">';
                                        html +='<div class="sent_msg">';
                                        html +='<p><small style="color: blanchedalmond"><b>'+responseData.user.name+'</b></small><br>'+  responseData.message +'</p>';
                                        html +='<span class="time_date"> '+ responseData.FormattedTime + '  |  ' +responseData.date+'</span>';
                                        html +='</div>';
                                        html +='</div>';


                                    }
                                    // html +='<li class="bg-white mb-3">';
                                    // html +='<div class="form-outline">';
                                    // html +='<textarea class="form-control" id="textAreaExample2" rows="4"></textarea>';
                                    // html +='<label class="form-label" for="textAreaExample2">Message</label>';
                                    // html +='</div>';
                                    // html +='</li>';
                                    // html +='<button type="button" class="btn btn-info btn-rounded float-end">Send</button>';
                                    $('#user_name').html(html);
                                    var chatMessagesContainer = document.getElementById('user_name');
                                    // chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;

                                    // Process the response data in reverse order here
                                }

                            }
                        });
                    }, 30000); // Adjust the polling interval as needed (e.g., 5 seconds)
                }

                    // function handleChatListClick() {
                $('#myList').on('click', '.chat_list',function() {
                    // Handle the click event for each <li> here
                    $('#myList .chat_list').removeClass('active-chat');
                    // $('#myList .chat_list #countHidden').addClass('count_hidden');


                    // Add the active-chat class to the clicked chat_list item
                     $(this).addClass('active-chat');
                    $(this).find('span.countA').hide();
                    // $(this).addClass('count_hidden');

                    const applicantId = $(this).data('id');
                    //pollForNewMessages(applicantId);

                    // $("#spinner-div").show();

                        $.ajax({
                            {{--url: "{{ route('applicantChatHistory') }}",--}}
                            url: "applicantChatHistory/"+applicantId,
                            type : 'get',
                            success:function(responses){
                                var dataChat=responses.data;
                                // console.log(dataChat);
                                 $('#applicant_id').val(applicantId);
                                var html='';
                                // for (var i = dataCHat.length  1; i >= 0; i++) {
                                for (var i = 0; i < dataChat.length; i++) {
                                    var responseData = dataChat[i];
                                    // console.log(responseData.status);
                                    if(responseData.status=="incoming") {
                                        // console.log('Received data incoming:', responseData);
                                        html +=' <div class="incoming_msg">';
                                        html +=' <div class="incoming_msg_img"> <img src="https://bootdey.com/img/Content/avatar/avatar3.png"   class="rounded-circle mr-1" alt="sunil"> </div>';
                                        html +='  <div class="received_msg">';
                                        html +='<div class="received_withd_msg">';
                                        html +=' <p><small style="color: black"><b>'+responseData.applicants.applicant_name+'</b></small><br>'+responseData.message+'</p>';
                                        html +=' <span class="time_date" style="float: right"> '+ responseData.FormattedTime + '  |  ' +responseData.date+'</span>';
                                        html +=' </div>';
                                        html +=' </div>';
                                        html +=' </div>';
                                    }else {
                                        // console.log('Received data:', blanchedalmond);
                                        html +='<div class="outgoing_msg">';
                                        html +='<div class="sent_msg">';
                                        html +='<p><small style="color: blanchedalmond"><b>'+responseData.user.name+'</b></small><br>'+  responseData.message +'</p>';
                                        html +='<span class="time_date"> '+ responseData.FormattedTime + '  |  ' +responseData.date+'</span>';
                                        html +='</div>';
                                        html +='</div>';


                                    }
                                    // html +='<li class="bg-white mb-3">';
                                    // html +='<div class="form-outline">';
                                    // html +='<textarea class="form-control" id="textAreaExample2" rows="4"></textarea>';
                                    // html +='<label class="form-label" for="textAreaExample2">Message</label>';
                                    // html +='</div>';
                                    // html +='</li>';
                                    // html +='<button type="button" class="btn btn-info btn-rounded float-end">Send</button>';
                                    $('#user_name').html(html);
                                    var chatMessagesContainer = document.getElementById('user_name');
                                    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;

                                    // Process the response data in reverse order here
                                }

                            }
                        });

                    // });
                });

                //applicant chat history



                // }
            // });



            // $(document).ready(function() {
                $('#sendMessageApplicant ').on('click',function (event){
                    event.preventDefault();

                   var app_id=$('#applicant_id').val();

                    var token = $("meta[name='csrf-token']").attr("content");
                    var message=document.getElementById("textMessage").value;
                   // alert(message);
                    $('#sendMessageApplicant').prop('disabled', true);
                    $('#loadingIcon').show();
                    if (app_id !== null && app_id !== "") {
                     

                        $.ajax({
                            {{--url: "{{ route('applicantChatHistory') }}",--}}
                            url: "sendMessageApplcant/",
                            type: 'get',
                            data: {'applicant_id': app_id, "_token": token, 'message': message},
                            success: function (responses) {
                                $('#loadingIcon').hide();
                                $('#myList .chat_list[data-id="' + app_id + '"]').trigger('click');
                                document.getElementById("textMessage").value = "";
                                toastr.success('Message sent to applicants successfully');
                                $('#sendMessageApplicant').prop('disabled', false);

                            },
                            error: function (error) {
								console.log(error);
                                $('#sendMessageApplicant').prop('disabled', false);
                                $('#loadingIcon').hide();
                                toastr.warning('Please add applicant first');

                            }


                        });
                    }else {
						
                        $('#loadingIcon').hide();
                        
                        toastr.warning('Please add applicant first');

                    }
                });

            });
            //
            // Get the button and icon elements by their IDs


            document.addEventListener('DOMContentLoaded', function() {
                // Get the search input field
                var searchInput = document.querySelector('.search-input');

                // Get all elements with class "search-result"
                var searchResults = document.querySelectorAll('.chat_list');

                // Attach an event listener to the search input field
                searchInput.addEventListener('keyup', function() {
                    // Get the search query
                    var searchQuery = searchInput.value.toLowerCase();

                    // Loop through all search result elements
                    searchResults.forEach(function(searchResult) {
                        // Get the text content of each search result
                        var resultText = searchResult.textContent.toLowerCase();

                        // Check if the search query is found in the result text
                        if (resultText.includes(searchQuery)) {
                            searchResult.style.display = 'block'; // Show the result
                        } else {
                            searchResult.style.display = 'none'; // Hide the result
                        }
                    });
                });
            });
            // Assuming you have loaded all data into initialData variable on page load.

        </script>

    @endsection
