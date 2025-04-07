<?php
require 'vendor/autoload.php';
require 'spreadsheet.php';

$spreadsheetId = $_GET["sheetId"];
$rowId = $_GET["rowId"];
$approvalId = $_GET["approvalId"];

$spreadsheet = new Spreadsheet($spreadsheetId);
$columnName = "Approval $approvalId";

$dataJson = json_decode($spreadsheet->getRange("A$rowId:Z$rowId", true), true); 

$approval_status = $dataJson[0][$columnName] ?? ""; 
echo json_encode(["approval_status" => $approval_status]);

?>
