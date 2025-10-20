<?php
require 'vendor/autoload.php';
require 'spreadsheet.php';
require 'email_send.php';

// Main spreadsheet ID

$dbSpreadsheetId = "1qCXZyon6chwQreXi8eBbaZ6Qmu5jWwJ2bFcvvVUmqXk";

if (array_key_exists('DDEV_USER',$_ENV)) {  
    $dbSpreadsheetId = "1eEGRFrrd93mKzseQEyO5eIrBvTfFHWWhs3UrtS_-HKI";
}

$masterSheet = new Spreadsheet($dbSpreadsheetId);

// Print the sheet name
$sheetName = $masterSheet->getSheetName($dbSpreadsheetId);
echo "Processing Master Sheet: $sheetName\n";

// 1. Read Master Sheet headers to find column positions dynamically
$headerData = $masterSheet->getSheetData('Sheet1!1:1');
if (empty($headerData)) {
    die("Error: Master sheet is empty or inaccessible.\n");
}

print_r($headerData);

$headers = array_map('strtolower', array_map('trim', $headerData[0]));

$sheetIdCol = array_search('google sheet id', $headers);
$processedCol = array_search('sheet processed', $headers);

if ($sheetIdCol === false || $processedCol === false) {
    $foundHeaders = implode("', '", $headers);
    die("Error: Could not find 'Google Sheet ID' and/or 'Sheet Processed' columns in master sheet. Please check spelling and spacing. Found headers: ['" . $foundHeaders . "']\n");
}

// Read all data from the master sheet
$masterSheetData = $masterSheet->getSheetData('Sheet1');


$sheetsToProcess = [];
if ($masterSheetData) {
    foreach ($masterSheetData as $rowIndex => $row) {
        if ($rowIndex == 0) continue; // Skip header row

        $sheetId = $row[$sheetIdCol] ?? null;
        // print_r($sheetId);
        $processedStatus = strtolower(trim($row[$processedCol] ?? ''));
        // print_r($processedStatus);


        if ($sheetId) {
            $sheetsToProcess[] = [
                'sheetId' => $sheetId,
                'isNew' => (strpos($processedStatus, 'yes') === false),
                'masterSheetRow' => $rowIndex + 1 // 1-based row index for updates
            ];
        }
    }
}

$rowsToMarkAsProcessed = [];

// 2. Process Each Sub-Sheet
foreach ($sheetsToProcess as $sheetInfo) {
    $sheetId = $sheetInfo['sheetId'];
    $isNew = $sheetInfo['isNew'];
    $subSheet = new Spreadsheet($sheetId);
    $spreadSheetTitle = $subSheet->getSpreadsheetTitle();
    $sheetName = $subSheet->getSheetName($sheetId);

    // Read all data from the sub-sheet at once

    $sheetQuery = "'" . $sheetName . "'!A:Z";

    $subSheetData = $subSheet->getSheetData($sheetQuery);


    if (empty($subSheetData)) {
        if ($isNew) {
            $rowsToMarkAsProcessed[] = $sheetInfo['masterSheetRow'];
        }
        continue; // Skip empty or inaccessible sheets
    }

    $originalHeaders = $subSheetData[0];
    $modifiedData = $subSheetData;

    if ($isNew) {
        $modifiedData = processingNewSheets($modifiedData);
        $rowsToMarkAsProcessed[] = $sheetInfo['masterSheetRow'];
    }

    // This function now works on the data array
    $result = processPendingApprovals($modifiedData, $sheetId, $spreadSheetTitle);
    $finalData = $result['data'];

    // 3. Write Changes Back to Sub-Sheet
    $rowCount = count($finalData);
    $colCount = count($finalData[0]);
    $range = $sheetName . '!A1:' . $subSheet->util->numberToColumnName($colCount) . $rowCount;

    $subSheet->clearSheet($range);

    if ($finalData) {
        $subSheet->updateSheetData($range, $finalData);
    }
}

// 4. Batch Update Master Sheet
if (!empty($rowsToMarkAsProcessed)) {
    $updateData = [];
    $sheetProcessedColumn = $masterSheet->util->numberToColumnName($processedCol + 1);
    foreach ($rowsToMarkAsProcessed as $rowIndex) {
        $updateData[] = [
            'range' => 'Sheet1!' . $sheetProcessedColumn . $rowIndex,
            'values' => [['Yes']]
        ];
    }
    $masterSheet->batchUpdateValues($updateData);
}

echo "Scheduler finished.\n";

function processingNewSheets(array $sheetData): array
{
    print_r("Processing New Sheet Structure...\n");

    $originalHeaders = $sheetData[0];
    $dataRows = array_slice($sheetData, 1);
    $newHeaders = [];
    $headerMap = []; // Maps new header index to old header index, -1 for new columns
    $emailColCount = 0;


    // 1. Build the new header row and a map to the old header indices
    foreach ($originalHeaders as $index => $header) {
        $newHeaders[] = $header;
        $headerMap[] = $index;

        if (strpos(strtolower(trim($header)), "email address") !== false) {
            $emailColCount++;
            $nextHeader = $originalHeaders[$index + 1] ?? null;
            if ($nextHeader === null || strpos(strtolower(trim($nextHeader)), "approval") === false) {
                $newHeaders[] = "Approval " . $emailColCount;
                print_r("Adding new header: Approval " . $emailColCount . "\n");
                print_r($newHeaders)    ;
                $headerMap[] = -1; // Mark this as a new column
            }
        }
    }

    // 2. Add 'Form Processed' if it doesn't exist
    $formProcessedFound = false;
    foreach ($newHeaders as $header) {
        if (strpos(strtolower(trim($header)), "form processed") !== false) {
            $formProcessedFound = true;
            break;
        }
    }
    if (!$formProcessedFound) {
        $newHeaders[] = "Form Processed";
        $headerMap[] = -1;
    }

    // 3. Build the new data rows using the map
    $newDataRows = [];
    foreach ($dataRows as $originalRow) {
        $newRow = [];
        foreach ($headerMap as $oldIndex) {
            if ($oldIndex !== -1) {
                $newRow[] = $originalRow[$oldIndex] ?? '';
            } else {
                $newRow[] = ''; // Add a blank cell for new columns
            }
        }
        $newDataRows[] = $newRow;
    }

    // 4. Combine the new headers and new data rows into the final sheet data
    return array_merge([$newHeaders], $newDataRows);
}

function processPendingApprovals(array $sheetData, string $sheetId, string $spreadSheetTitle): array
{
    $headers = $sheetData[0];
    $formProcessedIndex = -1;
    $firstNameIndex = -1;
    $lastNameIndex = -1;

    // Find header indices once
    foreach ($headers as $index => $header) {
        $lowerHeader = strtolower(trim($header));
        if (strpos($lowerHeader, "form processed") !== false) $formProcessedIndex = $index;
        if (strpos($lowerHeader, "first name") !== false) $firstNameIndex = $index;
        if (strpos($lowerHeader, "last name") !== false) $lastNameIndex = $index;
    }

    if ($formProcessedIndex === -1) {
        return ['data' => $sheetData]; // Cannot proceed
    }

    $emailColumnIndices = [];
    foreach ($headers as $index => $header) {
        if (strpos(strtolower(trim($header)), "email address") !== false) {
            $emailColumnIndices[] = $index;
        }
    }

    for ($i = 1; $i < count($sheetData); $i++) {
        $processedStatus = strtolower(trim($sheetData[$i][$formProcessedIndex] ?? ''));

        if (strpos($processedStatus, "yes") === false) {
            $approvalId = 0;
            $firstName = ($firstNameIndex !== -1) ? ($sheetData[$i][$firstNameIndex] ?? '') : '';
            $lastName = ($lastNameIndex !== -1) ? ($sheetData[$i][$lastNameIndex] ?? '') : '';
            // $array_combine = [$headers];
            foreach ($emailColumnIndices as $emailIndex) {
                $approvalId++;
                $emailAddress = $sheetData[$i][$emailIndex] ?? '';

                if (!empty($emailAddress)) {
                    $sheetInfo = ["rowId" => $i + 1, "approvalId" => $approvalId, "sheetId" => $sheetId];
                    $queryString = (new Util())->returnQueryString($sheetInfo);
                    $array_combine[] = $sheetData[$i];
                    $array_combine[] = array_pad($sheetData[$i], count($headers), '');
                    $rowDataJson = json_encode($array_combine);

                    print_r("Preparing to send email to $emailAddress for $firstName $lastName\n");
                    sendEmail($queryString, $emailAddress, $firstName, $lastName, $spreadSheetTitle, $rowDataJson);
                }
            }
            // // Mark row as processed in the array
            // print_r($sheetData[$i][$formProcessedIndex]);

            $sheetData[$i] = $sheetData[$i] + array_fill(0, count($headers), '');
            $sheetData[$i][$formProcessedIndex] = "Yes";
        }
    }

    return ['data' => $sheetData];

}
?>
