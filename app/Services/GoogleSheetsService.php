<?php

namespace App\Services;

use Revolution\Google\Sheets\Sheets;
use Illuminate\Support\Collection;

class GoogleSheetsService
{
    protected $sheets;
    protected $spreadsheetId;

    public function __construct(Sheets $sheets)
    {
        $this->sheets = $sheets;
        $this->spreadsheetId = config('sheets.spreadsheet_id');
    }

    public function getOrders(): Collection
    {
        $this->sheets->spreadsheet($this->spreadsheetId);
        return $this->sheets->sheet('Sheet1')->get();
    }

    public function updateOrderStatus(int $row, string $status): void
    {
        $this->sheets->spreadsheet($this->spreadsheetId);
        
        // Обновляем ячейку в столбце E (индекс 4)
        $this->sheets->range("E{$row}")->update([[$status]]);
    }

    public function getHeaderRow(): Collection
    {
        $this->sheets->spreadsheet($this->spreadsheetId);
        return $this->sheets->sheet('Sheet1')->range('A1:E1')->get();
    }
}