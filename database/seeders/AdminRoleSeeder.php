<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleSeeder extends Seeder
{
    // Business rules:
// Dashboard, Inventories, and Transactions Section are always visible for all roles. 
// For the Management section, filter navlinks based on role based on this seeder:

    // admin_table (Eloquent model with relations: Admin) (Primary key: admin_id)

    public function run(): void
    {
        DB::table('admin_roles')->insert([
            [
                'role_id' => 1,
                'role_title' => 'Head Admin',
                'description' => 'Complete system access and administration, including adding new admins.'
                // SHOW ALL action buttons, in sequence.
            ],
            [
                'role_id' => 2,
                'role_title' => 'Chief Approving Officer',
                'description' => 'Manage and review forms, equipment, and facilities.'
                // Approve/Reject button only, then 'you've already approved' once they've already made an action for the request_id
            ],
            [
                'role_id' => 3,
                'role_title' => 'Approving Officer',
                'description' => 'Manage and review forms, equipment, and facilities.'
                // Approve/Reject button only, then 'you've already approved' once they've already made an action for the request_id
            ],
            [
                'role_id' => 4,
                'role_title' => 'Inventory Manager',
                'description' => 'Manage facilities & equipment only.'
                // has no access to this view. ignore
            ]
        ]);
    }
}
