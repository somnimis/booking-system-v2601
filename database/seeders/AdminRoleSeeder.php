<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleSeeder extends Seeder
{
    // Business rules:
// Dashboard, Inventories, and Transactions Section are always visible for all roles. 
// For the Management section, filter navlinks based on role based on this seeder:

    public function run(): void
    {
        DB::table('admin_roles')->insert([
            [
                'role_id' => 1,
                'role_title' => 'Head Admin',
                'description' => 'Complete system access and administration, including adding new admins.'
                // SHOW ALL
            ],
            [
                'role_id' => 2,
                'role_title' => 'Approving Officer',
                'description' => 'Manage and review forms, equipment, and facilities.'
                // Hide Administrators
            ],
            [
                'role_id' => 3,
                'role_title' => 'Inventory Manager',
                'description' => 'Manage facilities & equipment only.'
                // Hide Administrators, Active Bookings, Pending Approval
            ]
        ]);
    }
}
