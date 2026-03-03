<?php
use Horsefly\SentEmail;
  
function changeDateFormate($date,$date_format){
    return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format($date_format);    
}
   
function productImagePath($image_name)
{
    return public_path('images/products/'.$image_name);
}
function uploadCV($id,$url)
{
    return 'here you goo';
}

function saveSentEmails($email_to, $email_cc, $email_from, $email_title, $email_body, $action_name)
{
    $sent_email = new SentEmail();
        $sent_email->action_name = $action_name;
        $sent_email->sent_from = $email_from;
        $sent_email->sent_to = $email_to;
        $sent_email->cc_emails = $email_cc;
        $sent_email->subject = $email_title;
        $sent_email->template = $email_body;
        $sent_email->email_added_date = date("jS F Y");
        $sent_email->email_added_time = date("h:i A");
        $sent_email->save();
        if($sent_email->id)
        {
            return 'success';
        }
        else
        {
            return 'error';
        }
}

?>