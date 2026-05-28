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

        // Approval chain summary: 
        // Stage 1. Approving officers first must approve
        // Stage 2. Final Approving officers will be notified and can take action only once all stage 1 approvals have been met. Alternatively, they can finalize even if all approval have not been met to be less strict on this rule.
        // Stage 3. This stage triggers once stage 2 approval has been met. This will notify the Issuing Officer. After assessing the form, they may finalize the request. the system will then change the form's status from Pending Approval to Awaiting Payment. This will also trigger the system to send an Invoice email to the user.
        // Stage 4: This is when the user has submitted a payment receipt for their request. This will trigger a notification to the Head Administrator. They will assess the fees, and then confirm the payment. This allows the system to mark the form's status to Reserved. This will trigger the system to send a Permit email to the user, where a link to their system-generated permit is attached.

            // For OVPA Manager. Oversees system performances and assesses payment fees. Note: this can be handed over to whoever handles the payment transactions for Use of Hall requests.
            [
                'role_id' => 1,
                'role_title' => 'Head Administrator',
                'description' => 'Complete system access and administration, including adding new admins. Can mark requests as officially reserved in the system after assessment of uploaded payments.'
            ],

            // For PA
            [
                'role_id' => 2,
                'role_title' => 'Final Approving Officer',
                'description' => "This user's approval is always required in all requests. Can manage and review forms, equipment, and facilities."
            ],

            // For other signatories
            [
                'role_id' => 3,
                'role_title' => 'Approving Officer',
                'description' => 'In charge of a facility, resource, or service. Can manage and review forms, equipment, and facilities.'
            ],

            // For EMC staff or equipment managers
            [
                'role_id' => 4,
                'role_title' => 'Inventory Manager',
                'description' => 'Manage facilities & equipment only. Keeps facilities and equipment up-to-date in the system. Responsible for equipment tracking per request use.'
            ],

            // For VPA
            [
                'role_id' => 5,
                'role_title' => 'Issuing Officer',
                'description' => 'Finalizes requests and authorizes usage permits.'

            ],
        ]);
    }
}
