<?php
namespace Custom\Reviews\Component\Lib\Domain\ValueObject;

class Author
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        
        if (empty($value)) {
            throw new \InvalidArgumentException('Имя автора не может быть пустым.');
        }
        
        if (mb_strlen($value) > 100) {
            throw new \InvalidArgumentException('Имя автора слишком длинное.');
        }
        
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
