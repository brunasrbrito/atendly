<?php

namespace Database\Factories;

use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'E'.fake()->unique()->numerify('##'),
            'profile' => 'Técnico N1',
            'conducted_on' => now()->toDateString(),
            'consent_recorded' => true,
            'themes' => ['percepcao_atrasada'],
            'notes' => fake()->paragraph(),
            'is_synthetic' => true,
        ];
    }
}
