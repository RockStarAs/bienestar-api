<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(){
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(){
        return [
            'section' => ['sometimes', 'nullable', 'string', 'max:200'],
            'text' => ['sometimes', 'required', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', 'in:text,single_choice,multiple_choice,likert'],
            'required' => ['sometimes', 'nullable', 'boolean'],
            'order' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
