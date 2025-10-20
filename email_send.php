<?php
function sendEmail($queryString, $to, $firstName, $lastName, $columnH, $data) {
    print_r("Sending email to $to for $firstName $lastName regarding $columnH\n");
    //rowId, $approvalId, $sheetId, 

    $server = $_SERVER['SERVER_NAME'];

    $folderName = "sbuApprove";

    if(array_key_exists('SERVER_NAME', $_SERVER)){
        $server = $_SERVER['SERVER_NAME'];
    }
    else{
        $server = "apps.tlt.stonybrook.edu";
    }
    
    $to = "$to";
    $subject = "$firstName $lastName has requested an approval for \"$columnH\"";

    $formattedData = "";
    foreach ($data[0] as $key => $value) {
        $columnKey = strtolower($key);
    
        if (strpos($columnKey, 'approval') !== false || strpos($columnKey, 'email address of') !== false || strpos($columnKey, 'form processed') !== false) {
            continue;
        }
    
        if (strpos($columnKey, 'timestamp') !== false) {
            $key = "Time Submitted";
        }
    
        $formattedData .= "<strong>$key:</strong> $value<br>";
    }



    $message = "
        <html>
        <head>
            <title>Approval Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
                .approval-link { margin-top: 20px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h3>Approval Request Details</h3>
                <p><strong>Request from:</strong> $firstName $lastName</p>
                <p><strong>Topic:</strong> $columnH</p>
                <hr>
                <p>$formattedData</p>
            </div>
            <div class='approval-link'>

                <p><strong>You can access the new record here and give your approval:</strong> 
                <a href='https://$server/$folderName/show_row.html?$queryString' style='color: blue; text-decoration: underline;'>Approval Link</a></p>
            </div>
        </body>
        </html>
    ";

    $headers = "From: tltmedialab@connect.stonybrook.edu\r\n" .
               "Reply-To: tltmedialab@connect.stonybrook.edu\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n" .
               "X-Mailer: PHP/" . phpversion();

    print_r("Email prepared. Sending now...\n");
    // mail("priya@gmail.com", "Here is my first email", "Message", $headers);

    mail($to, $subject, $message, $headers);
}
?>
