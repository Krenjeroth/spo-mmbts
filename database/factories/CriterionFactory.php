<?php

namespace Database\Factories;

use App\Models\Criterion;
use App\Models\PageantEvent;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Criterion>
 */
class CriterionFactory extends Factory
{
    protected $model = Criterion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => PageantEvent::factory(),
            'category_id' => Category::factory(),
            'parent_id' => null,
            'name' => $this->faker->word(),
            'percentage' => $this->faker->randomFloat(2, 10, 50),
            'order' => $this->faker->numberBetween(1, 5),
        ];
    }
}
