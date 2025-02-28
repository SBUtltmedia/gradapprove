<?php
require("spreadsheet.php");

$spreadsheet = new Spreadsheet("1jA4Irh9G5XM4ePbWZAosnroMu0rY5vGvuc671TtykeA");

// $rowId = "2";
// $column = "K";
// $data = "NOOO";

$rowId = $_GET["rowId"];
$column = $_GET["column"];
$isApproved = $_GET["isApproved"];

$spreadsheet->updateYesNo($rowId, $column, $isApproved);
?>