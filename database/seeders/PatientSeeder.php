<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Patient;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            [
                'first_name' => 'Jean',
                'last_name' => 'Munyaneza',
                'email' => 'jean.munyaneza@example.com',
                'phone' => '+250788123001',
                'date_of_birth' => '1985-03-15',
                'gender' => 'male',
                'address' => 'Kigali, Kicukiro District, Kicukiro Sector',
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'Mukamana',
                'email' => 'marie.mukamana@example.com',
                'phone' => '+250788123002',
                'date_of_birth' => '1990-07-22',
                'gender' => 'female',
                'address' => 'Kigali, Nyarugenge District, Nyarugenge Sector',
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Niyonzima',
                'email' => 'pierre.niyonzima@example.com',
                'phone' => '+250788123003',
                'date_of_birth' => '1978-11-08',
                'gender' => 'male',
                'address' => 'Kigali, Gasabo District, Kimihurura Sector',
            ],
            [
                'first_name' => 'Grace',
                'last_name' => 'Uwimana',
                'email' => 'grace.uwimana@example.com',
                'phone' => '+250788123004',
                'date_of_birth' => '1995-05-30',
                'gender' => 'female',
                'address' => 'Kigali, Remera Sector',
            ],
            [
                'first_name' => 'Emmanuel',
                'last_name' => 'Hategeka',
                'email' => 'emmanuel.hategeka@example.com',
                'phone' => '+250788123005',
                'date_of_birth' => '1982-09-12',
                'gender' => 'male',
                'address' => 'Kigali, Nyabugogo Sector',
            ],
        ];

        foreach ($patients as $patient) {
            Patient::create($patient);
        }
    }
}
