<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\Category;
use App\Models\Criterion;
use App\Models\PageantEvent;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = PageantEvent::where('title', 'Mr. & Ms. Benguet 2025')->first();

        if (! $event) {
            $this->command->error('❌ No event found! Please run PageantEventSeeder first.');
            return;
        }

        $phases = Phase::where('event_id', $event->id)->get()->keyBy('name');

        // Define categories with weights and criteria
        $categories = [
            // Kickoff
            'Kickoff' => [
                ['name' => 'Ethnic Wear', 'weight' => 10, 'criteria' => [
                    ['name' => 'Suitability to the candidate', 'percentage' => 40],
                    ['name' => 'Poise & Bearing', 'percentage' => 30],
                    ['name' => 'Projection', 'percentage' => 30],
                ]],
                ['name' => 'Casual Interview', 'weight' => 10, 'criteria' => [
                    ['name' => 'Substance', 'percentage' => 50],
                    ['name' => 'Spontaneity & Confidence', 'percentage' => 50],
                ]],
            ],

            // Advocacy Pitch
            'Advocacy Pitch' => [
                ['name' => 'Advocacy', 'weight' => 15, 'criteria' => [
                    [
                        'name' => 'Written Proposal',
                        'percentage' => 50,
                        'subcriteria' => [
                            ['name' => 'Feasibility', 'percentage' => 60],
                            ['name' => 'Clarity', 'percentage' => 20],
                            ['name' => 'Organization', 'percentage' => 20],
                        ],
                    ],
                    [
                        'name' => 'Presentation',
                        'percentage' => 50,
                        'subcriteria' => [
                            ['name' => 'Persuasiveness', 'percentage' => 30],
                            ['name' => 'Effective Delivery', 'percentage' => 30],
                            ['name' => 'Ability to Answer Questions', 'percentage' => 40],
                        ],
                    ],
                ]],
            ],

            // Pre-Pageant
            'Pre Pageant' => [
                ['name' => 'Swimwear', 'weight' => 15, 'criteria' => [
                    ['name' => 'Suitability to the candidate', 'percentage' => 40],
                    ['name' => 'Poise & Bearing', 'percentage' => 30],
                    ['name' => 'Stage Presence', 'percentage' => 30],
                ]],
                ['name' => 'Talent', 'weight' => 10, 'criteria' => [
                    ['name' => 'Presentation / Delivery / Mastery', 'percentage' => 40],
                    ['name' => 'Artistic Skill', 'percentage' => 30],
                    ['name' => 'Originality', 'percentage' => 30],
                ]],
            ],

            // Final Pageant
            'Final Pageant' => [
                ['name' => 'Creative Wear', 'weight' => 20, 'criteria' => [
                    ['name' => 'Suitability to the candidate', 'percentage' => 40],
                    ['name' => 'Poise & Bearing', 'percentage' => 30],
                    ['name' => 'Stage Presence', 'percentage' => 30],
                ]],
                ['name' => 'Formal Wear', 'weight' => 20, 'criteria' => [
                    ['name' => 'Suitability to the candidate', 'percentage' => 40],
                    ['name' => 'Poise & Bearing', 'percentage' => 30],
                    ['name' => 'Stage Presence', 'percentage' => 30],
                ]],
                ['name' => 'Q & A', 'weight' => 40, 'criteria' => [
                    ['name' => 'Content', 'percentage' => 50],
                    ['name' => 'Delivery', 'percentage' => 25],
                    ['name' => 'Confidence & Overall Presence', 'percentage' => 25],
                ]],
            ],
        ];

        $categoryOrder = 1;
        $criteriaOrder = 1;

        foreach ($categories as $phaseName => $phaseCategories) {
            $phase = $phases->get($phaseName);

            if (! $phase) {
                $this->command->warn("⚠️ Phase '{$phaseName}' not found, skipping...");
                continue;
            }

            foreach ($phaseCategories as $catData) {
                $category = Category::create([
                    'event_id' => $event->id,
                    'phase_id' => $phase->id,
                    'name' => $catData['name'],
                    'weight' => $catData['weight'],
                    'order' => $categoryOrder++,
                    'is_active' => true,
                ]);

                foreach ($catData['criteria'] as $critData) {
                    $criteria = Criterion::create([
                        'event_id' => $event->id,
                        'category_id' => $category->id,
                        'name' => $critData['name'],
                        'percentage' => $critData['percentage'],
                        'order' => $criteriaOrder++,
                    ]);

                    if (isset($critData['subcriteria'])) {
                        $subOrder = 1;
                        foreach ($critData['subcriteria'] as $sub) {
                            Criterion::create([
                                'event_id' => $event->id,
                                'category_id' => $category->id,
                                'parent_id' => $criteria->id,
                                'name' => $sub['name'],
                                'percentage' => $sub['percentage'],
                                'order' => $subOrder++,
                            ]);
                        }
                    }
                }
            }
        }

        $this->command->info('✅ Categories and criteria (with subcriteria) seeded successfully!');
    }
}
