<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => $this->faker->unique()->numberBetween(10000, 99999),
            'customer_name' => $this->faker->name(),
            'total' => $this->faker->numberBetween(500, 10000),
            'status' => $this->faker->randomElement([
                'new', 
                'processing', 
                'shipped', 
                'delivered', 
                'cancelled'
            ]),
        ];
    }
}