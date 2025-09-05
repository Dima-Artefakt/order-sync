<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleSheetsService;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class SyncOrderStatuses extends Command
{
    protected $signature = 'sync:orders-status';
    protected $description = 'Синхронизирует статусы заказов между Google Sheets и БД';

    public function handle(GoogleSheetsService $sheetsService)
    {
        try {
            $this->info('Начинаем синхронизацию статусов заказов...');
            Log::info('Запуск синхронизации статусов заказов');
            
            // Получаем данные из Google Sheets
            $ordersData = $sheetsService->getOrders();
            
            $updatedCount = 0;
            $processedCount = 0;

            $headers = array_map('trim', $ordersData[0]);
            // Создаем mapping колонок
            $columnMapping = [];
            foreach ($headers as $index => $header) {
                $columnMapping[$header] = $index;
            }
            
            // Проверяем обязательные колонки
            $requiredColumns = ['№ заказа', 'Старый статус'];
            $missingColumns = array_diff($requiredColumns, array_keys($columnMapping));

            if (!empty($missingColumns)) {
                $message = "Таблица не содержит обязательных колонок: " . implode(', ', $missingColumns);
                Log::error($message, [
                    'headers' => $headers, 
                    'available_columns' => array_keys($columnMapping),
                    'required_columns' => $requiredColumns
                ]);
                return 1;
            }

            // Пропускаем заголовок (первая строка)
            $ordersData = $ordersData->slice(1);

            $updatedCount = 0;
            $processedCount = 0;
            
            foreach ($ordersData as $index => $row) {
                $rowNumber = $index + 1; // +1 для пропуска заголовка
                $processedCount++;

                // Получаем данные по названиям колонок
                $orderNumber = trim($row[$columnMapping['№ заказа']] ?? ''); // Столбец A - № заказа
                $oldStatus = trim($row[$columnMapping['Старый статус']] ?? '');  // Столбец D - Старый статус
                
                if (empty($orderNumber)) {
                    $message = "Пропускаем строку {$rowNumber}: отсутствует номер заказа";
                    Log::warning($message, ['row' => $row, 'row_number' => $rowNumber]);
                    continue;
                }
                
                // Ищем заказ в БД
                $order = Order::where('order_number', $orderNumber)->first();
                
                if (!$order) {
                    $message = "Заказ {$orderNumber} не найден в базе данных";
                    Log::warning($message, ['order_number' => $orderNumber, 'row_number' => $rowNumber]);

                    try {
                        $sheetsService->updateOrderStatus($rowNumber, "Not faund");
                        $updatedCount++;
                        Log::info("Обновлен статус заказа {$orderNumber} на 'Not faund'");
                    } catch (\Exception $e) {
                        $errorMessage = "Ошибка при обновлении статуса для заказа {$orderNumber}";
                        Log::error($errorMessage, [
                            'exception' => $e->getMessage(),
                            'order_number' => $orderNumber,
                            'row_number' => $rowNumber
                        ]);
                    }
                    continue;
                }
                
                try {
                    // Обновляем статус в Google Sheets
                    $sheetsService->updateOrderStatus($rowNumber, $order->status);
                    $updatedCount++;
                    
                    $message = "Обновлен статус заказа {$orderNumber}: {$oldStatus} -> {$order->status}";
                    Log::info($message, [
                        'order_number' => $orderNumber,
                        'old_status' => $oldStatus,
                        'new_status' => $order->status,
                        'row_number' => $rowNumber
                    ]);
                    
                } catch (\Exception $e) {
                    $errorMessage = "Ошибка при обновлении статуса заказа {$orderNumber}";
                    Log::error($errorMessage, [
                        'exception' => $e->getMessage(),
                        'order_number' => $orderNumber,
                        'row_number' => $rowNumber,
                        'old_status' => $oldStatus,
                        'new_status' => $order->status
                    ]);
                }
                
            }
            
            $successMessage = "Синхронизация завершена. Обработано {$processedCount} строк, обновлено {$updatedCount} записей.";
            $this->info($successMessage);
            Log::info($successMessage, [
                'processed_count' => $processedCount,
                'updated_count' => $updatedCount
            ]);
            
        } catch (\Exception $e) {
            $errorMessage = "Критическая ошибка синхронизации: " . $e->getMessage();
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
}