<?php

namespace App\Http\Controllers\Dropdowns;

use App\Http\Controllers\Controller;
use App\Models\RequisitionPurpose;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RequisitionPurposeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $purposes = RequisitionPurpose::with('routedDepartment')->get();
            return response()->json(['success' => true, 'data' => $purposes]);
        } catch (\Exception $e) {
            \Log::error('Error fetching purposes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purposes',
                'debug' => env('APP_DEBUG', false) ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $purpose = RequisitionPurpose::with('routedDepartment')->find($id);
            if (!$purpose) {
                return response()->json(['message' => 'Purpose not found'], 404);
            }
            return response()->json(['success' => true, 'data' => $purpose]);
        } catch (\Exception $e) {
            \Log::error('Error fetching purpose', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch purpose'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purpose_name' => 'required|string|max:50',
            'discount_type' => 'required|in:flat,percentage',
            'discount_fee' => 'nullable|numeric|min:0',
            'routes_to' => 'nullable|exists:departments,department_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $purpose = RequisitionPurpose::create([
                'purpose_name' => $request->purpose_name,
                'routes_to' => $request->routes_to,
                'discount_type' => $request->discount_type,
                'discount_fee' => $request->discount_fee
            ]);

            return response()->json(['success' => true, 'data' => $purpose], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating purpose', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create purpose'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $purpose = RequisitionPurpose::find($id);
        if (!$purpose) {
            return response()->json(['message' => 'Purpose not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'purpose_name' => 'required|string|max:50',
            'discount_type' => 'required|in:flat,percentage',
            'discount_fee' => 'nullable|numeric|min:0',
            'routes_to' => 'nullable|exists:departments,department_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $purpose->update([
                'purpose_name' => $request->purpose_name,
                'routes_to' => $request->routes_to,
                'discount_type' => $request->discount_type,
                'discount_fee' => $request->discount_fee
            ]);

            return response()->json(['success' => true, 'data' => $purpose]);
        } catch (\Exception $e) {
            \Log::error('Error updating purpose', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update purpose'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $purpose = RequisitionPurpose::find($id);
        if (!$purpose) {
            return response()->json(['message' => 'Purpose not found'], 404);
        }

        try {
            $purpose->delete();
            return response()->json(['message' => 'Purpose deleted successfully']);
        } catch (\Exception $e) {
            \Log::error('Error deleting purpose', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete purpose'], 500);
        }
    }
}