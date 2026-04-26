<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\FacilitySeeder;
use Database\Seeders\InsuranceSeeder;
use Database\Seeders\PatientSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FacilitySeeder::class,
            InsuranceSeeder::class,
            PatientSeeder::class,
        ]);
    }
}
