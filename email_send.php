<?php
function sendEmail($rowId, $approvalId, $to) {
    $server = $_SERVER['SERVER_NAME'];

    if(array_key_exists('SERVER_NAME', $_SERVER)){
        $server = $_SERVER['SERVER_NAME'];
    }
    else{
        $server = "apps.tlt.stonybrook.edu";
    }

    $to = "$to";
    $subject = "New Student Enrollment Alert!"; ////////// "First Name" "Last name" has requested a thessis approval fo r"column H"

    $message = "You can access the new records here and give your approval: https://$server/gradapprove/show_row.html?rowId=$rowId&approvalId=$approvalId";

    $headers = "From: tltmedialab@connect.stonybrook.edu" . "\r\n" .
               "Reply-To: tltmedialab@connect.stonybrook.edu" . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $headers);
}
?>
