<?php

require "spreadsheet.php";

// "Sheet ID" sheet's ID
// $spreadsheetId = "1qCXZyon6chwQreXi8eBbaZ6Qmu5jWwJ2bFcvvVUmqXk";
$sheetName = "Sheet1";
$spreadsheet = new Spreadsheet($spreadsheetId);

// $url = $_GET["url"];
$url = "https://docs.google.com/spreadsheets/d/1jA4Irh9G5XM4ePbWZAosnroMu0rY5vGvuc671TtykeA/edit#gid=0";

$sheetId = $spreadsheet->extractSheetIdFromUrl($url);

print($sheetId);