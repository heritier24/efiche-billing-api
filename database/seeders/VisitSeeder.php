<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Facility;
use Carbon\Carbon;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $facilities = Facility::all();
        
        // Create 20 sample visits
        for ($i = 1; $i <= 20; $i++) {
            $patient = $patients->random();
            $facility = $facilities->random();
            
            Visit::create([
                'patient_id' => $patient->id,
                'facility_id' => $facility->id,
                'visit_type' => ['consultation', 'lab', 'emergency'][array_rand(['consultation', 'lab', 'emergency'])],
                'status' => ['active', 'completed', 'cancelled'][array_rand(['active', 'completed', 'cancelled'])],
                'completed_at' => rand(0, 1) ? Carbon::now()->subDays(rand(0, 30)) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
