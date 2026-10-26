<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $identity = $this->attributes->get('clinic_identity');

        if (! is_array($identity)) {
            return false;
        }

        return in_array($identity['role'] ?? null, ['admin', 'nurse_head'], true)
            || (($identity['role'] ?? null) === 'nurse' && ($identity['campus_id'] ?? null) !== null);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'visit_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
            'treatment' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'bp' => ['nullable', 'string', 'max:20'],
            'temp' => ['nullable', 'numeric', 'min:25', 'max:50'],
            'pulse' => ['nullable', 'integer', 'min:0', 'max:300'],
            'rr' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
