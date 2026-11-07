<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequisitionPurpose;
use Illuminate\Support\Facades\DB;

class RequisitionPurposeSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch department IDs safely by department code
        $vpaaId   = DB::table('departments')
            ->where('department_code', 'VPAA')
            ->value('department_id');

        $vpsaId   = DB::table('departments')
            ->where('department_code', 'VPSA')
            ->value('department_id');

        $alumniId = DB::table('departments')
            ->where('department_code', 'AA')
            ->value('department_id');

        $purposes = [
            [
                'purpose_name' => 'Academic/Class/Seminar/Conference',
                'routes_to'    => $vpaaId,
            ],
            [
                'purpose_name' => 'Academic CPU Organization Led Activity',
                'routes_to'    => $vpaaId,
            ],
            [
                'purpose_name' => 'Student-Organized CPU Organization Led Activity',
                'routes_to'    => $vpsaId,
            ],
            [
                'purpose_name' => 'Student-Organized Activity',
                'routes_to'    => $vpsaId,
            ],
            [
                'purpose_name' => 'Alumni-Organized Activity',
                'routes_to'    => $alumniId,
            ],
            [
                'purpose_name' => 'Alumni-Organized Class Reunion',
                'routes_to'    => $alumniId,
            ],
            [
                'purpose_name' => 'Alumni-Organized Personal Event',
                'routes_to'    => $alumniId,
            ],
            [
                'purpose_name' => 'University Program/Activity',
                'routes_to'    => null,
            ],
            [
                'purpose_name' => 'Academic-Related External Event',
                'routes_to'    => $vpaaId,
            ],
            [
                'purpose_name' => 'External Event',
                'routes_to'    => null,
            ],
        ];

        foreach ($purposes as $purpose) {
            RequisitionPurpose::updateOrCreate(
                ['purpose_name' => $purpose['purpose_name']],
                ['routes_to' => $purpose['routes_to']]
            );
        }
    }
}