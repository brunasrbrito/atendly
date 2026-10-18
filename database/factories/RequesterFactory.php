<?php

namespace Database\Factories;

use App\Models\Requester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Requester>
 */
class RequesterFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->numerify('###');

        return [
            'external_id' => "SOL-{$code}",
            'name' => "Solicitante {$code}",
            'department' => fake()->randomElement(['Financeiro', 'RH', 'Comercial']),
            'is_synthetic' => true,
        ];
    }
}
