<?php
namespace Custom\Reviews\Component\Lib\Domain\ValueObject;

class Text
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        
        if (empty($value)) {
            throw new \InvalidArgumentException('Текст отзыва не может быть пустым.');
        }
        
        if (mb_strlen($value) < 10) {
            throw new \InvalidArgumentException('Текст отзыва слишком короткий.');
        }
        
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getExcerpt(int $length = 100): string
    {
        if (mb_strlen($this->value) <= $length) {
            return $this->value;
        }
        
        return mb_substr($this->value, 0, $length) . '...';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
