<?php
require 'vendor/autoload.php';
require 'spreadsheet.php';
require 'email_send.php';

$spreadsheetId = "1jA4Irh9G5XM4ePbWZAosnroMu0rY5vGvuc671TtykeA";
$spreadsheet = new Spreadsheet($spreadsheetId);


$highestRow = $spreadsheet->getHighestRow("Sheet1");
// $highestRow  = 100000;
$column = "P";

// get all the values from Processed column whether blank or Yes
$dataJson = json_decode($spreadsheet->getRangeColumn($column, 2, $highestRow), true);
print_r($dataJson);


function IsNullOrEmptyString(?string $str) {
    return $str === null || trim($str) === '';
}



$columnMap = [
    "1" => "Email address of thesis director/course director/GPD/ English faculty nominee (MA/BA program) for approval",
    "2" => "Email address of second reader/committee member/English faculty nominee (MA/BA program)  (if relevant)",
    "3" => "Email address of third reader/committee member/English faculty nominee (MA/BA program)  (if relevant)" 
];


$pendingRowIds = [];



// store the rowIds which are "blank" for processing and sending emails
foreach ($dataJson as $index => $value) {
    $rowId = $index + 2;
    $processedCell = strtolower(trim($value ?? ""));

    if (IsNullOrEmptyString($processedCell) || $processedCell !== "yes") {
        $pendingRowIds[] = $rowId;
    }
}



// count the number of new students since the last run
$newEnrollments = count($pendingRowIds);



// for each unprocessed rowIds send 3 emails to the 3 professors
foreach ($pendingRowIds as $rowId) {
    $dataJson = json_decode($spreadsheet->getRange("A$rowId:Z$rowId", true), true);

    foreach ($columnMap as $approvalId => $columnName) {
        $updateKeyword = "Yes";
        $emailAddress = $dataJson[0][$columnName] ?? "";
        // echo "Email Address: " . $emailAddress . "\n";

        if (!IsNullOrEmptyString($emailAddress)) {
            sendEmail($newEnrollments, $rowId-1, $approvalId, $emailAddress);

            $spreadsheet->updateYesNo($rowId, $column, $updateKeyword);
        }
    }
}
?>
