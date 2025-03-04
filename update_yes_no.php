<?php
require("spreadsheet.php");

$spreadsheet = new Spreadsheet("1jA4Irh9G5XM4ePbWZAosnroMu0rY5vGvuc671TtykeA");

// $rowId = "2";
// $column = "P";
// $isApproved = "Yes";

$rowId = $_GET["rowId"];
$column = $_GET["column"];
$approval_status = $_GET["approval_status"];

$spreadsheet->updateRowColumn($rowId, $column, $approval_status);
?>