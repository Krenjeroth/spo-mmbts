<?php

namespace Database\Factories;

use App\Models\Score;
use App\Models\Judge;
use App\Models\Contestant;
use App\Models\Category;
use App\Models\Criterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
{
    protected $model = Score::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = $this->faker->randomFloat(2, 80, 100);

        return [
            'event_id' => 1, // assuming single test event
            'judge_id' => Judge::factory(),
            'contestant_id' => Contestant::factory(),
            'category_id' => Category::factory(),
            'criterion_id' => Criterion::factory(),
            'score' => $score,
            // weight will be multiplied by criterion weight later (default 1 for now)
            'weighted_score' => $score,
        ];
    }
}
