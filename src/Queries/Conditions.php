<?php

namespace JoshuaMc1\Database\Queries;

class Conditions
{
    protected string $column;
    protected string $operator;
    protected mixed $value;
    protected string $placeholder;

    public function __construct(string $column, string $operator, mixed $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
        $this->placeholder = ':' . str_replace('.', '_', $column) . uniqid();
    }

    public function toSql(): string
    {
        return "{$this->column} {$this->operator} {$this->placeholder}";
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
