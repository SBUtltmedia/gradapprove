<?php
require 'vendor/autoload.php';
require 'spreadsheet.php';
require 'email_send.php';

$spreadsheetId = "1jA4Irh9G5XM4ePbWZAosnroMu0rY5vGvuc671TtykeA";
$spreadsheet = new Spreadsheet($spreadsheetId);

$highestRow = $spreadsheet->getHighestRow("Sheet1");
// print($highestRow);


$columnMap = [
    "1" => "Email address of thesis director/course director/GPD/ English faculty nominee (MA/BA program) for approval",
    "2" => "Email address of second reader/committee member/English faculty nominee (MA/BA program)  (if relevant)",
    "3" => "Email address of third reader/committee member/English faculty nominee (MA/BA program)  (if relevant)" 
];

for ($rowId = 2; $rowId <= $highestRow; $rowId++) {
    $processedCell = strtolower(trim($spreadsheet->getRangeColumn("P", $rowId, $rowId)));
    // print($processedCell);   

    if (strpos($processedCell, "yes") === false) {
        $dataJson = json_decode($spreadsheet->getRange("A$rowId:Z$rowId", true), true);
        // print_r($dataJson);

        foreach ($columnMap as $approvalId => $columnName) {
            $emailAddress = $dataJson[0][$columnName] ?? "";
            // echo "Email Address: " . $emailAddress . "\n";
            if (!empty($emailAddress)) {
                sendEmail($rowId - 1, $approvalId, $emailAddress);
            }    

            $spreadsheet->updateRowColumn($rowId, "P", "Yes");
            }
    }
}
?>
