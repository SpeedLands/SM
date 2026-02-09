<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selectedStudentId' => 'required|exists:students,id',
            'reason' => 'required|string',
            'citationDate' => 'required|date|after_or_equal:today',
            'citationTime' => 'required',
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
            'reason' => 'motivo',
            'citationDate' => 'fecha de la cita',
            'citationTime' => 'hora de la cita',
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
            'reason.required' => 'El motivo es obligatorio.',
            'citationDate.after_or_equal' => 'La fecha de la cita debe ser hoy o posterior.',
        ];
    }
}
