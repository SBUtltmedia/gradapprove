<?php
function sendEmail($rowId, $approvalId, $to, $firstName, $lastName, $columnH, $dataJson) {
    $server = $_SERVER['SERVER_NAME'];

    if(array_key_exists('SERVER_NAME', $_SERVER)){
        $server = $_SERVER['SERVER_NAME'];
    }
    else{
        $server = "apps.tlt.stonybrook.edu";
    }
    
    $to = "$to";
    $subject = "$firstName $lastName has requested a thesis approval for \"$columnH\"";

    $excludeFields = [
        "Approval 1",
        "Approval 2",
        "Approval 3",
        "Form Processed",
        "Email address of thesis director/course director/GPD/ English faculty nominee (MA/BA program) for approval",
        "Email address of second reader/committee member/English faculty nominee (MA/BA program)  (if relevant)",
        "Email address of third reader/committee member/English faculty nominee (MA/BA program)  (if relevant)"
    ];

    $formattedData = "";
    foreach ($dataJson[0] as $key => $value) {
        if (!in_array($key, $excludeFields)) {
            $formattedData .= "<strong>$key:</strong> $value<br>";
        }
    }


    $message = "
        <html>
        <head>
            <title>Thesis Approval Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
                .approval-link { margin-top: 20px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h3>Thesis Approval Request Details</h3>
                <p><strong>Request from:</strong> $firstName $lastName</p>
                <p><strong>Thesis Topic:</strong> $columnH</p>
                <hr>
                <p>$formattedData</p>
            </div>
            <div class='approval-link'>

                <p><strong>You can access the new record here and give your approval:</strong> 
                <a href='https://$server/gradapprove/show_row.html?rowId=$rowId&approvalId=$approvalId' style='color: blue; text-decoration: underline;'>Approval Link</a></p>
            </div>
        </body>
        </html>
    ";

    $headers = "From: tltmedialab@connect.stonybrook.edu\r\n" .
               "Reply-To: tltmedialab@connect.stonybrook.edu\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n" .
               "X-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $headers);
}
?>
