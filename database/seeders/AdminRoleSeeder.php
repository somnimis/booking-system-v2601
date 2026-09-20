<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleSeeder extends Seeder
{

    // admin_table (Eloquent model with relations: Admin) (Primary key: admin_id)

    public function run(): void
    {
        DB::table('admin_roles')->insert([

            // Approval chain summary: 
            // users = the person who submitted the form. admins = the people reviewing the form.
            // Stage 1. Approving officers first must approve
            // Stage 2. Final Approving officers will be notified and can take action only once all stage 1 approvals have been met. The form's is_finalized is set to true once all final approving officers have acted. This will also trigger the system to send an Invoice email to the user, prompting them to settle their fees.
            // Stage 3. This stage triggers once stage 2 approval has been met AND the uploadReceipt() method (user's action) has been hit, which flips the form_status.status_name to "Verifying Payment". Once these conditions are met, this will notify the Issuing Officers. After assessing the form, they may choose to finalize or close the form. If they choose to finalize, the system will then change the form's status from Verifying Payment to Reserved. The system will generate booking reference receipt and permit. This will then send a confirmation email to the user, with the link to their copy of the transaction and use of hall permit. 

            [
                'role_id' => 1,
                'role_title' => 'System Administrator',
                'description' => 'Manages system-wide settings and has full administrative access, including the ability to manually override request forms when necessary.'
            ],
            [
                'role_id' => 2,
                'role_title' => 'Final Approving Officer',
                'description' => "Can manage request forms, facilities, equipment, and extra services. All assigned Final Approving Officers must approve a request before it can be finalized and the approved fee is locked."
            ],

            [
                'role_id' => 3,
                'role_title' => 'Approving Officer',
                'description' => 'Can manage request forms, facilities, equipment, and extra services'
            ],

            [
                'role_id' => 4,
                'role_title' => 'Inventory Manager',
                'description' => 'Can manage equipment and facilities. Keeps facilities and equipment up-to-date in the system. Responsible for equipment tracking per request use.'
            ],

            [
                'role_id' => 5,
                'role_title' => 'Issuing Officer',
                'description' => 'Reviews uploaded payments, finalizes requests and authorizes usage permits.'

            ],
        ]);
    }
}
