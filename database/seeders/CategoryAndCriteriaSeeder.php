<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\Category;
use App\Models\Criterion;
use Illuminate\Support\Str;
use App\Models\PageantEvent;
use Illuminate\Database\Seeder;

class CategoryAndCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = PageantEvent::first();
        if (!$event) {
            $this->command->error('⚠️ No event found. Seed PageantEvent first.');
            return;
        }

        // Build a tolerant phase lookup (lowercase, strip non-alnum)
        $phaseLookup = [];
        Phase::where('event_id', $event->id)->get()
            ->each(function ($p) use (&$phaseLookup) {
                $phaseLookup[$this->norm($p->name)] = $p->id;
            });

        $categories = [
            'Kickoff' => [
                ['Ethnic Wear', 'ethnic-wear', 10, [
                    ['Suitability to the candidate', 40],
                    ['Poise & Bearing', 30],
                    ['Projection', 30],
                ]],
                ['Casual Interview', 'casual-interview', 10, [
                    ['Substance', 50],
                    ['Spontaneity & Confidence', 50],
                ]],
            ],
            'Advocacy Pitch' => [
                ['Advocacy', 'advocacy-pitch', 15, [
                    ['Written Proposal', 50, [
                        ['Feasibility', 60],
                        ['Clarity', 20],
                        ['Organization', 20],
                    ]],
                    ['Presentation', 50, [
                        ['Persuasiveness', 30],
                        ['Effective Delivery', 30],
                        ['Ability to Answer Questions', 40],
                    ]],
                ]],
            ],
            'Pre-Pageant' => [
                ['Swimwear', 'swim-wear', 15, [
                    ['Suitability to the candidate', 40],
                    ['Poise & Bearing', 30],
                    ['Stage Presence', 30],
                ]],
                ['Talent', 'talent', 10, [
                    ['Presentation / Delivery / Mastery', 40],
                    ['Artistic Skill', 30],
                    ['Originality', 30],
                ]],
            ],
            'Final Pageant' => [
                ['Creative Wear', 'creative-wear', 20, [
                    ['Suitability to the candidate', 40],
                    ['Poise & Bearing', 30],
                    ['Stage Presence', 30],
                ]],
                ['Formal Wear', 'formal-wear', 20, [
                    ['Suitability to the candidate', 40],
                    ['Poise & Bearing', 30],
                    ['Stage Presence', 30],
                ]],
                ['Q & A', 'q-and-a', 40, [
                    ['Content', 50],
                    ['Delivery', 25],
                    ['Confidence & Overall Presence', 25],
                ]],
            ],
        ];

        foreach ($categories as $phaseName => $phaseCategories) {
            $normName = $this->norm($phaseName);
            $phaseId  = $phaseLookup[$normName] ?? null;

            // ✅ Auto-create the phase if it doesn't exist (prevents skipping)
            if (!$phaseId) {
                $phase = Phase::create([
                    'event_id' => $event->id,
                    'name'     => $phaseName,
                    'order'    => 0,
                    'is_active'=> true,
                ]);
                $phaseId = $phase->id;
                $phaseLookup[$normName] = $phaseId;
                $this->command->warn("ℹ️ Created missing phase: {$phaseName}");
            }

            foreach ($phaseCategories as [$catName, $catSlug, $catWeight, $criteria]) {
                $category = Category::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'phase_id' => $phaseId,
                        'slug'     => $catSlug,
                    ],
                    [
                        'name'      => $catName,
                        'weight'    => $catWeight,
                        'order'     => 0,
                        'is_active' => true,
                    ]
                );

                $this->seedCriteria($event->id, $category->id, null, $criteria);
            }
        }

        $this->command->info('✅ Categories (with slugs) and criteria seeded successfully!');
    }

    private function seedCriteria($eventId, $categoryId, $parentId, $criteria): void {
        foreach ($criteria as $item) {
            // supports both leaf ['Name', percent] and node ['Name', percent, [children...]]
            [$name, $percent, $subCriteria] = array_pad((array) $item, 3, []);

            $criterion = Criterion::create([
                'event_id'    => $eventId,
                'category_id' => $categoryId,
                'parent_id'   => $parentId,
                'name'        => $name,
                'percentage'  => $percent,
                'order'       => 0,
            ]);

            if (!empty($subCriteria)) {
                $this->seedCriteria($eventId, $categoryId, $criterion->id, $subCriteria);
            }
        }
    }

    private function norm(string $s): string {
        // normalize: lowercase + remove non-alphanumeric (so "Pre Pageant" matches "Pre-Pageant")
        return Str::of($s)->lower()->replaceMatches('/[^a-z0-9]+/i', '')->toString();
    }
}
