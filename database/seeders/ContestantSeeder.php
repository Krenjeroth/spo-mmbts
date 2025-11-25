<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contestant;
use App\Models\Municipality;
use App\Models\PageantEvent;

class ContestantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = PageantEvent::first();

        if (!$event) {
            $this->command->warn('⚠️ No pageant events found. Please seed PageantEvents first.');
            return;
        }

        $contestants = [
            ['municipality' => 'Buguias',     'number' => '01', 'male' => 'Kurt Russell P. Taynan',      'female' => 'Claudine A. Tokiyas'],
            ['municipality' => 'Itogon',      'number' => '02', 'male' => 'Jomarie K. Tanacio',          'female' => 'Shikara Dumayag'],
            ['municipality' => 'Tublay',      'number' => '03', 'male' => 'Randolph M. Bonce',           'female' => 'Fara Mae V. Macido'],
            ['municipality' => 'Sablan',      'number' => '04', 'male' => 'Orlino T. Lerot',             'female' => 'Shazely C. Palaci'],
            ['municipality' => 'Kapangan',    'number' => '05', 'male' => 'Jamroel L. Damugo',           'female' => 'Christal A. Dagupen'],
            ['municipality' => 'La Trinidad', 'number' => '06', 'male' => 'Clifford D. Yagyagan',        'female' => 'Angelika Josierny B. Servinas'],
            ['municipality' => 'Atok',        'number' => '07', 'male' => 'Rudee L. Dolipas, Jr.',       'female' => 'Zairee D. Alcid'],
            ['municipality' => 'Kabayan',     'number' => '08', 'male' => 'Benar John D. Wallace',       'female' => 'Venus N. Belao'],
            ['municipality' => 'Bokod',       'number' => '09', 'male' => 'Raymund D. Pablo',            'female' => 'Honey Lynne A. Tictic'],
            ['municipality' => 'Mankayan',    'number' => '10', 'male' => 'Reden D. Saliwey',            'female' => 'Maria Teresa T. Velasco'],
            ['municipality' => 'Tuba',        'number' => '11', 'male' => 'Sidrick Azrell L. Sevilla',   'female' => 'Trixie Marie N. Togatag'],
            ['municipality' => 'Kibungan',    'number' => '12', 'male' => 'Kyle Brent S. Kimayong',      'female' => 'Harleth P. Pantaleon'],
            ['municipality' => 'Bakun',       'number' => '13', 'male' => 'Areli A. Ang-ayon',           'female' => 'Irish Amor B. Kudan'],
        ];

        foreach ($contestants as $data) {
            $municipality = Municipality::where('name', $data['municipality'])->first();

            if (!$municipality) {
                $this->command->warn("⚠️ Municipality '{$data['municipality']}' not found, skipping...");
                continue;
            }

            // Mister
            Contestant::updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'event_id' => $event->id,
                    'gender' => 'male',
                ],
                [
                    'name' => $data['male'],
                    'number' => $data['number'],
                ]
            );

            // Miss
            Contestant::updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'event_id' => $event->id,
                    'gender' => 'female',
                ],
                [
                    'name' => $data['female'],
                    'number' => $data['number'],
                ]
            );
        }

        $this->command->info('✅ Official Mr. & Ms. Benguet 2025 contestants seeded successfully.');
    }

}
