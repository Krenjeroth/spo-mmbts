<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\PageantEvent;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => PageantEvent::factory(),
            'phase_id' => Phase::factory(),
            'name' => $this->faker->word(),
            'weight' => $this->faker->randomFloat(2, 5, 40),
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
