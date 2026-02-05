<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $periodId = $this->route('period');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('periods', 'name')->ignore($periodId)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'El nombre del período es obligatorio.',
            'name.unique' => 'Ya existe un período con este nombre.',
            'name.max' => 'El nombre del período no puede exceder los 255 caracteres.',
            'description.max' => 'La descripción no puede exceder los 1000 caracteres.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}