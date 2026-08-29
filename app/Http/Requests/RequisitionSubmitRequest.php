<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequisitionSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'user_type' => 'required|in:Internal,External',
            'school_id' => 'required_if:user_type,Internal|nullable|string|max:20',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'contact_number' => ['nullable', 'regex:/^\d{1,15}$/', 'max:15'],
            'organization_name' => 'nullable|string|max:100',
            'event_title' => 'required|string|max:100',
            'event_details' => 'nullable|string|max:500',
            'num_participants' => 'required|integer|min:1',
            'num_tables' => 'required|integer|min:0',
            'num_chairs' => 'required|integer|min:0',
            'num_microphones' => 'required|integer|min:0',
            'all_day' => 'required|boolean',
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            'additional_requests' => 'nullable|string|max:250',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'event_documents_url' => 'nullable|url',
            'event_documents_public_id' => 'nullable|string|max:255',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'integer|exists:extra_services,service_id',
        ];

        if (!$this->all_day) {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
        }

        return $rules;
    }
}