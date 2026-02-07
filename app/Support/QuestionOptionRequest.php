<?php

namespace App\Support;

final class QuestionOptionRequest
{
    public string $label;
    public string $value;
    public ?int $order;

    public function __construct(string $label, string $value, ?int $order = null)
    {
        $this->label = $label;
        $this->value = $value;
        $this->order = $order;
    }

    /** @param array{label:string,value:string,order?:int|null} $a */
    public static function fromArray(array $a): self
    {
        return new self(
            $a['label'],
            $a['value'],
            $a['order'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'order' => $this->order,
        ];
    }
}
