<?php

namespace Database\Factories;

use App\Models\KnowledgeArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArticle>
 */
class KnowledgeArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'problem' => fake()->paragraph(),
            'solution' => fake()->paragraph(),
            'keywords' => implode(', ', fake()->words(4)),
            'is_synthetic' => true,
        ];
    }
}
