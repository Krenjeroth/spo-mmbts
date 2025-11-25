<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PageantEvent;
use App\Models\Phase;

class PageantEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the main event
        $event = PageantEvent::create([
            'title' => 'Mr. & Ms. Benguet 2025',
            'year' => 2025,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-12',
            'status' => 'active',
        ]);

        // Define the standard phases
        $phases = [
            ['name' => 'Kickoff', 'description' => 'Opening phase of the pageant.', 'order' => 1],
            ['name' => 'Advocacy Pitch', 'description' => 'Candidates present their advocacies.', 'order' => 2],
            ['name' => 'Pre Pageant', 'description' => 'Preliminary competition and presentation.', 'order' => 3],
            ['name' => 'Final Pageant', 'description' => 'The coronation and final judging.', 'order' => 4],
        ];

        foreach ($phases as $phase) {
            Phase::create(array_merge($phase, [
                'event_id' => $event->id,
                'is_active' => true,
            ]));
        }

        $this->command->info('✅ Mr. & Ms. Benguet 2025 event with phases seeded successfully.');
    }
}
