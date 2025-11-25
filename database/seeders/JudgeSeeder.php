<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Judge;
use App\Models\Role;
use App\Models\PageantEvent;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Phase;
use Illuminate\Support\Facades\DB;

class JudgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = PageantEvent::first();

        if (!$event) {
            $this->command->warn('⚠️ No event found. Please seed PageantEvents first.');
            return;
        }

        $judgeRole = Role::firstOrCreate(['title' => 'Judge']);

        $phases = Phase::where('event_id', $event->id)->get();

        if ($phases->isEmpty()) {
            $this->command->warn('⚠️ No phases found. Please seed Phases first.');
            return;
        }

        // Judges per phase
        $phaseJudgeCounts = [
            'Kickoff' => 3,
            'Advocacy Pitch' => 5,
            'Pre Pageant' => 3,
            'Final Pageant' => 7,
        ];

        $overallCounter = 1;
        foreach ($phases as $phase) {
            $judgeCount = $phaseJudgeCounts[$phase->name] ?? 0;

            for ($i = 1; $i <= $judgeCount; $i++) {
                $judgeNumber = $i; // per phase numbering

                // Create unique user per judge
                $user = User::updateOrCreate(
                    ['email' => "judge{$overallCounter}@mmb.local"],
                    [
                        'name' => "Judge {$overallCounter}",
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );

                // Attach role
                $user->roles()->syncWithoutDetaching([$judgeRole->id]);

                // Create Judge entry
                $judge = Judge::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                    ],
                    [
                        'category_assignment' => null,
                        'judge_number' => $judgeNumber, // numbering per phase
                        'is_active' => true,
                    ]
                );

                // Attach to phase via pivot
                DB::table('judge_phase')->updateOrInsert(
                    [
                        'judge_id' => $judge->id,
                        'phase_id' => $phase->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $overallCounter++;
            }
        }

        $this->command->info("✅ Seeded judges successfully, with per-phase numbering.");
    }
}
