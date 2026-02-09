<?php

namespace App\Support;

final class ChildQuestionRequest
{
    public ?int $id;
    public string $text;
    public ?int $order;

    public function __construct(string $text, ?int $order = null, ?int $id = null)
    {
        $this->text = $text;
        $this->order = $order;
        $this->id = $id;
    }

    /** @param array{text:string,order?:int|null,id?:int|null} $a */
    public static function fromArray(array $a): self
    {
        return new self(
            $a['text'],
            $a['order'] ?? null,
            $a['id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'order' => $this->order,
        ];
    }
}
