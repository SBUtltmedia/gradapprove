<?php
require 'vendor/autoload.php';
require 'spreadsheet.php';
require 'email_send.php';



$dbSpreadsheetId = "1qCXZyon6chwQreXi8eBbaZ6Qmu5jWwJ2bFcvvVUmqXk";
$spreadsheet = new Spreadsheet($dbSpreadsheetId);



//iterate to find out the last row in "Record ID" sheet
$highestRow = $spreadsheet->getHighestRow("Sheet1");



//iterate over the "Record ID" sheet to find out whether the new rows/sheets/URLs have been added or not
for ($rowId = 2; $rowId <= $highestRow; $rowId++) {

    $headers = $spreadsheet->headers;


    $processedData = strtolower(trim($spreadsheet->getCellFromRowByHeader("Sheet Processed", $rowId) ?? ""));

    $sheetId = trim($spreadsheet->getCellFromRowByHeader("Google Sheet ID", $rowId) ?? "");

    // if the value of the column "Sheet Processed" is not yes it means its a new row so we call processingNewSheets() else processPendingApprovals()
    if (strpos($processedData, "yes") === false) {
        processingNewSheets($sheetId, $rowId);
        $sheetProcessedIndex = $spreadsheet->findHeaderIndex($headers, 'sheet processed');
        $sheetProcessedColumnLetter = $spreadsheet->util->numberToColumnName($sheetProcessedIndex + 1);
        $spreadsheet->updateRowColumn($rowId, $sheetProcessedColumnLetter, "Yes");
    } else {
        processPendingApprovals($sheetId, $rowId);
    }

}




// function to handle already once processed sheets from the "Record ID"
function processPendingApprovals($sheetId, $mainSheetRowId) { // Renamed for clarity
    $spreadsheetUpdate = new Spreadsheet($sheetId);

    $headers = $spreadsheetUpdate->headers;
    $highestRow = $spreadsheetUpdate->getHighestRow();

    if ($highestRow <= 1) {
        return; // No data to process
    }

    $allDataJson = $spreadsheetUpdate->getRange("A2:Z{$highestRow}", true);
    $allData = json_decode($allDataJson, true);
    
    $sheetName = $spreadsheetUpdate->getSheetName($sheetId);

    $formProcessedHIndex = $spreadsheetUpdate->findHeaderIndex($headers, 'form processed');
    if ($formProcessedHIndex === false) {
        return;
    }
    $formProcessedColumnLetter = $spreadsheetUpdate->util->numberToColumnName($formProcessedHIndex + 1);

    foreach ($allData as $rowIndex => $rowData) {
        $subSheetRowId = $rowIndex + 2; // because data starts from row 2

        $processedCell = strtolower(trim($rowData["Form Processed"] ?? ""));

        if (strpos($processedCell, "yes") === false) {
            $approvalId = 0;
            $firstName = $rowData['First Name'] ?? '';
            $lastName = $rowData['Last Name'] ?? '';

            foreach ($headers as $headerIndex => $header) {
                if ($headerIndex < 2) continue;

                $headerValue = strtolower(trim($header));

                if (strpos($headerValue, "email address") !== false) {
                    $approvalId++;
                    $emailAddress = $rowData[$header] ?? '';

                    if (!empty($emailAddress)) {
                        $sheetInfo = array("rowId" => $subSheetRowId, "approvalId" => "$approvalId", "sheetId" => "$sheetId");
                        $queryString = $spreadsheetUpdate->util->returnQueryString($sheetInfo);
                        sendEmail($queryString, $emailAddress, $firstName, $lastName, $sheetName, json_encode([$rowData]));
                        
                        // Update "Form Processed" cell for each email sent, to preserve original logic
                        $spreadsheetUpdate->updateRowColumn($subSheetRowId, $formProcessedColumnLetter, "Yes");
                    }
                }
            }
        }
    }
}


//function to handle new sheets by making sure all their email addresses field have a corresponding "Approvals" section
// and last column is "Form Processed"
function processingNewSheets($sheetId, $rowId){
    $spreadsheetNew = new Spreadsheet($sheetId);

    // --- Add Approval columns ---
    $emailColumnsCount = 0;
    $index = 2;
    while ($index < count($spreadsheetNew->headers)) {
        $headerValue = strtolower(trim($spreadsheetNew->headers[$index]));

        if (strpos($headerValue, "email address") !== false) {
            $emailColumnsCount++;
            $nextHeader = $spreadsheetNew->headers[$index + 1] ?? "";
            if (strpos(strtolower(trim($nextHeader)), "approval") === false) {
                $spreadsheetNew->insertColumnAtIndex($sheetId, "Approval " . $emailColumnsCount, $index + 1);
                // Re-fetch headers after modification
                $spreadsheetNew = new Spreadsheet($sheetId); 
            }
        }
        $index++;
    }

    // --- Add Form Processed column ---
    // Re-fetch headers again to be safe
    $spreadsheetNew = new Spreadsheet($sheetId);
    $headers = $spreadsheetNew->headers;
    $formProcessedFound = false;
    foreach ($headers as $header) {
        if (strpos(strtolower(trim($header)), "form processed") !== false) {
            $formProcessedFound = true;
            break;
        }
    }

    if (!$formProcessedFound) {
        $spreadsheetNew->insertColumnAtIndex($sheetId, "Form Processed", count($headers));
    }

    processPendingApprovals($sheetId, $rowId);
}

?>