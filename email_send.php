<?php
function sendEmail($newEnrollments, $rowId, $approvalId, $to) {
    $to = "$to";
    $subject = "New Student Enrollment Alert!";
    $message = "Here are the number of students who have enrolled in the last hour: $newEnrollments.\n\n";
    $message .= "You can access the updated records here and give your approval: https://gradapprove.ddev.site/show_row.html?rowId=$rowId&approvalId=$approvalId";

    $headers = "From: tltmedialab@connect.stonybrook.edu" . "\r\n" .
               "Reply-To: tltmedialab@connect.stonybrook.edu" . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $headers);
}
?>
