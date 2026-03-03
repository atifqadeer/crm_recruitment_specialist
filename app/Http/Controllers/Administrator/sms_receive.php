<?php
$dbhost = "localhost";
$dbuser = "crm_dbs83451";
$dbpass = "s&72feB2";
$db = "dbs83451";
$con='';
$sql='';
$conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
if(! $conn )  
{  
  die('Could not connect: ' . mysqli_error());  
}  
echo 'Connected successfully';
	$phoneNumber_gsm = isset($_GET['phoneNumber']) ? $_GET['phoneNumber'] : null ;
    $phoneNumber=str_replace("+44", "0", $phoneNumber_gsm);
    $query = "SELECT * FROM applicants WHERE applicant_phone = '".$phoneNumber."'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $appicant_id= $row['id'];

	$message = isset($_GET['message']) ? $_GET['message'] : null;
	$date_time = isset($_GET['time']) ? $_GET['time'] : null ;
    $date_time_arr = explode(" ", $date_time);
    $date_res = $date_time_arr[0];
    $date = str_replace("/", "-", $date_res);
    $time = $date_time_arr[1];
    $msg_id = substr(md5(time()), 0, 14); 
	if($message!='' && $phoneNumber!='')
	{
		$sql = "INSERT INTO applicant_messages (applicant_id, user_id, msg_id,message,phone_number,date,time,status,is_read)
VALUES ('$appicant_id', '', '$msg_id','$message','$phoneNumber','$date','$time','incoming','0')";
	}
	

if ($conn->query($sql) === TRUE) {
  echo "New record created successfully";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}


?>
