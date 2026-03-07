<?php

namespace App\Http\Controllers\Dropdowns;

use App\Http\Controllers\Controller;
use App\Models\RequisitionPurpose;

class RequisitionPurposeController extends Controller
{
    public function index()
    {
         $purpose = RequisitionPurpose::select('purpose_id', 'purpose_name')->get();
        return response()->json($purpose);
    }
}
