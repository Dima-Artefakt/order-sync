<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Очищаем таблицу перед заполнением
        Order::query()->delete();

        // Создаем дополнительные случайные заказы
        Order::factory()->count(15)->create();
    }
}