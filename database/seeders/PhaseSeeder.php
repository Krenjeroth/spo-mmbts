<?php

namespace Database\Seeders;

use App\Models\PageantEvent;
use App\Models\Phase;
use Illuminate\Database\Seeder;

class PhaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PageantEvent::all() as $event) {
            $phases = ['Kickoff', 'Advocacy', 'Pre-Pageant', 'Final Pageant'];
            foreach ($phases as $i => $name) {
                Phase::firstOrCreate([
                    'event_id' => $event->id,
                    'name' => $name,
                    'order' => $i + 1,
                ]);
            }
        }
    }
}
