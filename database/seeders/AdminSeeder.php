<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admins')->insert([

            // ------- For Developer ------- //

            [
                'first_name' => 'Dev',
                'last_name' => 'Admin',
                'middle_name' => null,
                'title' => 'System Developer',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 1,
                'school_id' => null,
                'email' => 'dev@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ------- Head Admins ------- //
            [
                'first_name' => 'Earnest',
                'last_name' => 'Dagohoy',
                'middle_name' => null,
                'title' => 'President of Administration',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 1,
                'school_id' => null,
                'email' => 'vpa@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Dany',
                'last_name' => 'Molina',
                'middle_name' => null,
                'title' => 'Vice President of Administration',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 1,
                'school_id' => null,
                'email' => 'ovpa@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ------- Signatories ------- //
            [
                'first_name' => 'Rey',
                'last_name' => 'Quimba',
                'middle_name' => null,
                'title' => 'Head of Buildings and Maintenance Services',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => '2249-12-43',
                'email' => 'bum@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Ivan',
                'last_name' => 'Cachopero',
                'middle_name' => null,
                'title' => 'Head of Electrical Maintenance Services',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => null,
                'email' => 'ems@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Jonathan',
                'last_name' => 'Tumalay',
                'middle_name' => null,
                'title' => 'Head of CTSSO',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => null,
                'email' => 'ctsso@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Donald',
                'last_name' => 'Lebrilla',
                'middle_name' => null,
                'title' => 'Educational Media Center Coordinator',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => null,
                'email' => 'emc@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Estinoco',
                'middle_name' => null,
                'title' => 'Head of Grounds and Upkeep Maintence Services',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => null,
                'email' => 'gum@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Lennon',
                'last_name' => 'Pajar',
                'middle_name' => null,
                'title' => 'Head of University Computer Service Center',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => null,
                'email' => 'ucsc@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ------ Placeholder Account For Schedule Coordinators and in Charge of Venues ------- //

            [
                'first_name' => 'Signatory',
                'last_name' => 'Head',
                'middle_name' => null,
                'title' => 'Schedule Coordinator or in charge of venue',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 2,
                'school_id' => null,
                'email' => 'signatory@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ------- Administrative Staff & Work Students ------- //

            // Department : EMC / Educational Media Center.
            [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'middle_name' => null,
                'title' => 'Inventory Managing Staff',
                'signature_url' => null,
                'signature_public_id' => null,
                'role_id' => 3,
                'school_id' => null,
                'email' => 'staff@example.test',
                'contact_number' => null,
                'hashed_password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
