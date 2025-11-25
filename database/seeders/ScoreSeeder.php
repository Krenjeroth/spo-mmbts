<?php

namespace Database\Seeders;

use App\Models\Contestant;
use App\Models\Category;
use App\Models\Judge;
use App\Models\PageantEvent;
use App\Models\Criterion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = PageantEvent::first();
        if (! $event) {
            $this->command->warn('⚠️ No event found. Seed PageantEvent first.');
            return;
        }

        // clean slate
        DB::table('scores')->truncate();

        $judges = Judge::with('phases')->where('event_id', $event->id)->get();
        $contestants = Contestant::where('event_id', $event->id)->get();
        $categories = Category::where('event_id', $event->id)
            ->where('is_active', true)
            ->get()
            ->groupBy('phase_id');

        $criteria = Criterion::where('event_id', $event->id)
            ->get()
            ->groupBy('category_id');

        $totalInserted = 0;

        foreach ($judges as $judge) {
            foreach ($judge->phases as $phase) {
                $phaseCategories = $categories[$phase->id] ?? collect();

                foreach ($phaseCategories as $category) {
                    $categoryCriteria = $criteria[$category->id] ?? collect();

                    foreach ($contestants as $contestant) {
                        // Random base score (80–100)
                        foreach ($categoryCriteria as $criterion) {
                            $score = fake()->randomFloat(2, 80, 100);
                            $weighted = ($score * ($criterion->percentage / 100));

                            DB::table('scores')->insert([
                                'event_id' => $event->id,
                                'judge_id' => $judge->id,
                                'contestant_id' => $contestant->id,
                                'category_id' => $category->id,
                                'criterion_id' => $criterion->id,
                                'score' => $score,
                                'weighted_score' => $weighted,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $totalInserted++;
                        }
                    }
                }
            }
        }

        $this->command->info("✅ Inserted {$totalInserted} scores with correct weighted values.");
    }
}
