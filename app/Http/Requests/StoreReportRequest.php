<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'infractionId' => 'required|exists:infractions,id',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string|max:1000',
            'reportDate' => [
                'required',
                'date',
                'before_or_equal:tomorrow',
                function ($attribute, $value, $fail) {
                    if ($value && Carbon::parse($value)->isWeekend()) {
                        $fail('No se pueden registrar reportes para fines de semana.');
                    }
                },
            ],
            'reportTime' => 'required',
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
            'infractionId' => 'tipo de infracción',
            'subject' => 'asunto',
            'description' => 'descripción',
            'reportDate' => 'fecha del reporte',
            'reportTime' => 'hora del reporte',
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
            'infractionId.required' => 'Debe seleccionar un tipo de infracción.',
            'subject.required' => 'El asunto es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'reportDate.before_or_equal' => 'La fecha del reporte debe ser una fecha anterior o igual a hoy.',
        ];
    }
}
