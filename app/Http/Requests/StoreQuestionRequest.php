<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
        return [
            'section' => ['nullable','string','max:200'],
            'text' => ['required','string','max:1000'],
            'type' => ['required','in:text,single_choice,multiple_choice,likert'],
            'required' => ['nullable','boolean'],
            'order' => ['nullable','integer','min:1'],

            // crear opciones en el mismo POST
            'options' => ['nullable','array','min:2'],
            'options.*.label' => ['required_with:options','string','max:255'],
            'options.*.value' => ['required_with:options','string','max:100'],
            'options.*.order' => ['nullable','integer','min:1'],
        ];
    }
}
