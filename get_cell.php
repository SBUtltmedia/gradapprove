<?php
require 'vendor/autoload.php';
require 'spreadsheet.php';

$spreadsheetId = "1jA4Irh9G5XM4ePbWZAosnroMu0rY5vGvuc671TtykeA";
$sheetName = "Sheet1";

$rowId = $_GET["rowId"];
$approvalId = $_GET["approvalId"];

$columnMap = [
    "1" => "Approval 1", // K column header
    "2" => "Approval 2", // M column header
    "3" => "Approval 3"  // O column header
];

// $rowId = "2";
// $approvalId = "3";

$columnName = $columnMap[$approvalId];

$spreadsheet = new Spreadsheet($spreadsheetId);
$dataJson = json_decode($spreadsheet->getRange("A$rowId:Z$rowId", true), true); 

$approval_status = $dataJson[0][$columnName] ?? ""; 

echo json_encode(["approval_status" => $approval_status]);
?>