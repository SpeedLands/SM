<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the Livewire component
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selectedStudentId' => 'required|exists:students,id',
            'activity' => 'required|string|max:255',
            'scheduledDate' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isSunday()) {
                        $fail('No se permite programar servicio comunitario los domingos.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'selectedStudentId' => 'alumno',
            'activity' => 'actividad',
            'scheduledDate' => 'fecha programada',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selectedStudentId.required' => 'Debe seleccionar un alumno.',
            'activity.required' => 'La actividad es obligatoria.',
            'scheduledDate.after_or_equal' => 'La fecha debe ser hoy o posterior.',
        ];
    }
}
