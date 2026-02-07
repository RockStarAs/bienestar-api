<?php

namespace App\Http\Requests;

use App\Models\TemplateQuestion;
use App\Support\QuestionOptionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
    public function rules(){
        return [
            'section' => ['nullable','string','max:200'],
            'title' => ['sometimes','string','max:1000'],
            'subtitle' => ['sometimes','string','max:1000'],
            'text' => ['sometimes','required','string','max:1000'],
            'type' => ['sometimes','required', Rule::in(TemplateQuestion::TYPES)],
            'required' => ['nullable','boolean'],
            'order' => ['nullable','integer','min:1'],

            // crear opciones en el mismo POST
            'options' => ['nullable','array','min:2'],
            'options.*.label' => ['required_with:options','string','max:255'],
            'options.*.value' => ['required_with:options','string','max:100'],
            'options.*.order' => ['nullable','integer','min:1'],
        ];
    }

    public function section(): ?string {
        /** @var string|null $v */
        $v = $this->input('section');
        return $v;
    }

    public function title(): ?string {
        /** @var string|null $v */
        $v = $this->input('title');
        return $v;
    }

    public function subtitle(): ?string {
        /** @var string|null $v */
        $v = $this->input('subtitle');
        return $v;
    }

    public function text(): ?string {
        /** @var string|null $v */
        $v = $this->input('text');
        return $v;
    }

    /**
     * @return string Uno de \App\Models\Question::TYPES
     */
    public function type(): ?string {
        /** @var string|null $v */
        $v = $this->input('type');
        return $v;
    }

    public function required(): ?bool {
        /** @var bool|null $v */
        $v = $this->input('required');
        return $v;
    }

    public function order(): ?int {
        $v = $this->input('order');
        if ($v === null) {
            return null;
        }
        return (int) $v;
    }

    /**
     * @return array<int, array{label:string,value:string,order?:int|null}>|null
     */
    public function options(): ?array {
        /** @var array<int, array{label:string,value:string,order?:int|null}>|null $v */
        $v = $this->input('options');
        return $v;
    }

    /** @return QuestionOptionRequest[] */
    public function optionObjects(): array {
        $options = $this->options();

        if (!$options) {
            return [];
        }

        return array_map(static function (array $opt): QuestionOptionRequest {
            return QuestionOptionRequest::fromArray($opt);
        }, $options);
    }
}
