<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'requester_name' => fake()->name(),
            'product_name' => fake()->words(3, true),
            'product_code' => null,
            'quantity' => fake()->numberBetween(1, 10),
            'reason' => fake()->sentence(),
            'urgency' => fake()->randomElement(['baixa', 'media', 'alta']),
            'justification' => fake()->paragraph(),
            'status' => 'pendente',
        ];
    }

    /**
     * Indicate that the request has been approved.
     */
    public function aprovado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovado',
        ]);
    }
}
