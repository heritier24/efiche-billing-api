<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Facility;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            [
                'name' => 'King Faisal Hospital',
                'code' => 'KFH',
                'address' => 'Kigali, Nyarugenge District, Kigali Heights',
                'phone' => '+250788123456',
                'is_active' => true,
            ],
            [
                'name' => 'CHUK Kigali',
                'code' => 'CHUK',
                'address' => 'Kigali, Nyarugenge District',
                'phone' => '+250787123456',
                'is_active' => true,
            ],
            [
                'name' => 'Masaka Hospital',
                'code' => 'MASAKA',
                'address' => 'Kigali, Kicukiro District',
                'phone' => '+250786123456',
                'is_active' => true,
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }
    }
}
