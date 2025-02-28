<?php

require __DIR__ . '/vendor/autoload.php';
class Spreadsheet{

	private string $sheetId;
	private Google_Service_Sheets $service;


	public function __construct($sheetId)
	{

		$this->sheetId=$sheetId;
		$this->service=$this->getService();
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
		$response = $this->service->spreadsheets_values->get($this->sheetId, $range);
		$values = $response->getValues();
	
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
	public function updateYesNo($row, $column, $isApproved) {
		$range = "$column$row";
		$isApproved = filter_var($isApproved, FILTER_VALIDATE_BOOLEAN);

		$value = $isApproved ? "Yes" : "No";
	
		$body = new Google_Service_Sheets_ValueRange([
			'values' => [[$value]]
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

}
