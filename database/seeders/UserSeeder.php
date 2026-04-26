<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::all();
        
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@efiche.rw',
                'password' => Hash::make('password123'),
                'phone' => '+250788123000',
                'role' => 'admin',
                'facility_id' => $facilities->first()->id,
                'is_active' => true,
            ],
            [
                'name' => 'Jean Cashier',
                'email' => 'cashier@efiche.rw',
                'password' => Hash::make('password123'),
                'phone' => '+250788123001',
                'role' => 'cashier',
                'facility_id' => $facilities->first()->id,
                'is_active' => true,
            ],
            [
                'name' => 'Marie Staff',
                'email' => 'staff@efiche.rw',
                'password' => Hash::make('password123'),
                'phone' => '+250788123002',
                'role' => 'staff',
                'facility_id' => $facilities->get(1)->id,
                'is_active' => true,
            ],
            [
                'name' => 'Pierre Cashier',
                'email' => 'cashier2@efiche.rw',
                'password' => Hash::make('password123'),
                'phone' => '+250788123003',
                'role' => 'cashier',
                'facility_id' => $facilities->get(1)->id,
                'is_active' => true,
            ],
            [
                'name' => 'Grace Staff',
                'email' => 'staff2@efiche.rw',
                'password' => Hash::make('password123'),
                'phone' => '+250788123004',
                'role' => 'staff',
                'facility_id' => $facilities->get(2)->id,
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
