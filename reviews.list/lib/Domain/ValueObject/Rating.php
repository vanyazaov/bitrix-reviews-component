<?php
namespace Custom\Reviews\Component\Lib\Domain\ValueObject;

class Rating
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 5) {
            throw new \InvalidArgumentException('Рейтинг должен быть от 1 до 5.');
        }
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
    
    public function getStars(): string
    {
        return str_repeat('★', $this->value) . str_repeat('☆', 5 - $this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
