<?php

require __DIR__ . '/vendor/autoload.php';
require "util.php";
class Spreadsheet{

	private string $sheetId;
	private Google_Service_Sheets $service;


	public function __construct($sheetId)
	{

		$this->sheetId=$sheetId;
		$this->service=$this->getService();
		$this->headers=$this->service->spreadsheets_values->get($this->sheetId, "A1:Z1")->getValues()[0];
		$this->util= new Util();

	}
	

	public function getSheetId(): string {
		return $this->sheetId;
	}	



	public function getService(){

		$client = new \Google_Client();

		$client->setApplicationName('Google Sheets and PHP');

		$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);

		$client->setAccessType('offline');

		$client->setAuthConfig(__DIR__ . '/credentials.json');

		return new Google_Service_Sheets($client);
	}
	
	

	//incorporated json as well as csv, if json then true else return csv
	public function getRange($range, $asJson = true) {
		$response = $this->service->spreadsheets_values->get($this->sheetId, $range);
		$values = $response->getValues();
	
		if ($asJson) {
			$headers = $this->service->spreadsheets_values->get($this->sheetId, "A1:Z1")->getValues()[0];
	
			$jsonData = [];
			foreach ($values as $row) {
				$rowData = array_combine($headers, array_pad($row, count($headers), null));
				$jsonData[] = $rowData;
			}
			return json_encode($jsonData, JSON_PRETTY_PRINT);
		}
	
		return $this->csv($values);
	}
	
	public function getRangeColumn($column, $startRow, $endRow) {
		$range = "{$column}{$startRow}:{$column}{$endRow}";
		// $range = "P2:P2";

		$params = [
			'valueRenderOption' => 'FORMATTED_VALUE'
				];

		$response = $this->service->spreadsheets_values->get($this->sheetId, $range, $params);		
		$values = $response->getValues() ?? [0];

		// Flatten the response to a simple array
		$columnValues = [];
		foreach ($values as $row) {
			$columnValues[] = $row[0] ?? "";
		}
	
		return json_encode($columnValues, JSON_PRETTY_PRINT);
	}
	


	public function updateRange($range,$data){
		$body = new Google_Service_Sheets_ValueRange([

				'values' => $data

		]);

		$params = [

			'valueInputOption' => 'RAW'

		];

		$update_sheet = $this->service->spreadsheets_values->update($this->sheetId, $range, $body, $params);


	}

	//updates the approval columns with yes or no 
	public function updateRowColumn($row, $column, $data) {

		$range = "$column$row";
	
		$body = new Google_Service_Sheets_ValueRange([
			'values' => [[$data]]
		]);
	
		$params = ['valueInputOption' => 'RAW'];
	
		$this->service->spreadsheets_values->update($this->sheetId, $range, $body, $params);
	}
	

	public function batchGet($ranges) {
        $response = $this->service->spreadsheets_values->batchGet($this->sheetId, ['ranges' => $ranges]);
        return $response->getValueRanges();
    }


	public function csv($data) {
		$fh = fopen('php://temp', 'rw'); # don't create a file, attempt
			foreach ( $data as $row ) {
				fputcsv($fh, $row);
			}
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);

		return $csv;
	}

	// get last filled rowId
	public function getHighestRow(string $sheetName = "Sheet1"): ?int {
		$range = "!A:Z";
		$response = $this->service->spreadsheets_values->get($this->sheetId, $range);
		$values = $response->getValues();

		if (empty($values)) {
			return null;
		}

		return count($values);
	}


	// extract sheet ID from URL
	public function extractSheetIdFromUrl(string $url): ?string {
		if (preg_match('/\/d\/([a-zA-Z0-9-_]+)(?:\/|$)/', $url, $matches)) {
			return $matches[1];
		}
		return null;
	}


	// get a cell vlaue by providing COLUMN NAMES and row ID
	public function getCellFromRowByHeader(string $headerName, int $rowNumber): ?string {
			// calculate the column index for the given header
			$columnIndex = array_search($headerName, $this->headers);
		
			$columnName= $this->util->numberToColumnName($columnIndex+1);

			// defininig like "B4"
			$cell = $columnName . $rowNumber;
		
			// get the cell value
			$response = $this->service->spreadsheets_values->get($this->sheetId, $cell);
			$value = $response->getValues()[0][0] ?? "";
			return $value;
		}


		// function to add a new column to the end of the sheet with a given header name
		public function addNewColumnToEnd(string $sheetName, string $newColumnName): void {
			$headers = $this->service->spreadsheets_values
				->get($this->sheetId, "$sheetName!1:1")
				->getValues()[0] ?? [];

			// determine the next empty column
			$newColumnIndex = count($headers);
			$columnLetter = $this->columnIndexToLetter($newColumnIndex);

			// set the new header value in the first row of that column
			$range = "$sheetName!{$columnLetter}1";
			$body = new Google_Service_Sheets_ValueRange([
				'values' => [[$newColumnName]]
			]);

			$params = ['valueInputOption' => 'RAW'];
			$this->service->spreadsheets_values->update($this->sheetId, $range, $body, $params);
		}

		// function to convert column index to A, B, Z, AA, AB
		public function columnIndexToLetter($index): string {
			$letter = '';
			while ($index >= 0) {
				$letter = chr($index % 26 + 65) . $letter;
				$index = intval($index / 26) - 1;
			}
			return $letter;
		}
		


}
