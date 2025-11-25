<?php

namespace Database\Seeders;

use App\Models\Criterion;
use Illuminate\Database\Seeder;

class CriterionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $criteria = [
            // Benguet Attire
            ['event_id' => '3', 'category_id' => '1', 'parent_id' => null, 'name' => 'Suitability to the candidate', 'percentage' => '40', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '1', 'parent_id' => null, 'name' => 'Poise and bearing', 'percentage' => '30', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '1', 'parent_id' => null, 'name' => 'Projection', 'percentage' => '30', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],

            // Casual Interview
            ['event_id' => '3', 'category_id' => '2', 'parent_id' => null, 'name' => 'Substance', 'percentage' => '50', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '2', 'parent_id' => null, 'name' => 'Spontaneity & Confidence', 'percentage' => '50', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],

            // Advocacy Pitch
            // Written Proposal
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => null, 'name' => 'Written Proposal', 'percentage' => '50', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            // Written Proposal Sub-criteria
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => '6', 'name' => 'Feasibility', 'percentage' => '60', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => '6', 'name' => 'Clarity', 'percentage' => '20', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => '6', 'name' => 'Organization', 'percentage' => '20', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],
            // End Written Proposal
            
            // Oral Presentation
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => null, 'name' => 'Oral Presentation', 'percentage' => '50', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            // Oral Presentation Sub-criteria
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => '10', 'name' => 'Persuasiveness', 'percentage' => '30', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => '10', 'name' => 'Effective Delivery', 'percentage' => '30', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '3', 'parent_id' => '10', 'name' => 'Ability to Answer Questions', 'percentage' => '40', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],
            // End Oral Presentation

            // Swimwear
            ['event_id' => '3', 'category_id' => '4', 'parent_id' => null, 'name' => 'Suitability to the candidate', 'percentage' => '40', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '4', 'parent_id' => null, 'name' => 'Poise and Bearing', 'percentage' => '30', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '4', 'parent_id' => null, 'name' => 'Stage Presence', 'percentage' => '30', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],

            // Talent
            ['event_id' => '3', 'category_id' => '5', 'parent_id' => null, 'name' => 'Presentation / Delivery / Mastery', 'percentage' => '40', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '5', 'parent_id' => null, 'name' => 'Artistic Skill', 'percentage' => '30', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '5', 'parent_id' => null, 'name' => 'Originality', 'percentage' => '30', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],

            // Creative Wear
            ['event_id' => '3', 'category_id' => '6', 'parent_id' => null, 'name' => 'Suitability to the candidate', 'percentage' => '40', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '6', 'parent_id' => null, 'name' => 'Poise and Bearing', 'percentage' => '30', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '6', 'parent_id' => null, 'name' => 'Stage Presence', 'percentage' => '30', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],


            // Formal Wear
            ['event_id' => '3', 'category_id' => '7', 'parent_id' => null, 'name' => 'Suitability to the candidate', 'percentage' => '40', 'order' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '7', 'parent_id' => null, 'name' => 'Poise and Bearing', 'percentage' => '30', 'order' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => '3', 'category_id' => '7', 'parent_id' => null, 'name' => 'Stage Presence', 'percentage' => '30', 'order' => '3', 'created_at' => now(), 'updated_at' => now()],
        ];

        Criterion::insert($criteria);
    }
}
