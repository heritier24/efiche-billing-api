<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Insurance;

class InsuranceSeeder extends Seeder
{
    public function run(): void
    {
        $insurances = [
            [
                'name' => 'Rwanda Social Security Board',
                'code' => 'RSSB',
                'contact_phone' => '+250788111111',
                'contact_email' => 'info@rssb.gov.rw',
            ],
            [
                'name' => 'Mutuelle de Santé',
                'code' => 'MMI',
                'contact_phone' => '+250788222222',
                'contact_email' => 'info@mutuelle.gov.rw',
            ],
            [
                'name' => 'MediCare Rwanda',
                'code' => 'MEDICARE',
                'contact_phone' => '+250788333333',
                'contact_email' => 'info@medicare.rw',
            ],
            [
                'name' => 'Prime Insurance',
                'code' => 'PRIME',
                'contact_phone' => '+250788444444',
                'contact_email' => 'info@prime.rw',
            ],
        ];

        foreach ($insurances as $insurance) {
            Insurance::create($insurance);
        }
    }
}
