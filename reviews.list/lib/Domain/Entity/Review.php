<?php
namespace Custom\Reviews\Component\Lib\Domain\Entity;

use Custom\Reviews\Component\Lib\Domain\ValueObject\ReviewId;
use Custom\Reviews\Component\Lib\Domain\ValueObject\Author;
use Custom\Reviews\Component\Lib\Domain\ValueObject\Rating;
use Custom\Reviews\Component\Lib\Domain\ValueObject\Text;
use Bitrix\Main\Type\DateTime;

class Review
{
    private ReviewId $id;
    private Author $author;
    private Rating $rating;
    private Text $text;
    private DateTime $createdAt;
    private ?int $userId;

    public function __construct(
        ReviewId $id,
        Author $author,
        Rating $rating,
        Text $text,
        DateTime $createdAt,
        ?int $userId = null
    ) {
        $this->id = $id;
        $this->author = $author;
        $this->rating = $rating;
        $this->text = $text;
        $this->createdAt = $createdAt;
        $this->userId = $userId;
    }

    // Геттеры
    public function getId(): ReviewId
    {
        return $this->id;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getRating(): Rating
    {
        return $this->rating;
    }

    public function getText(): Text
    {
        return $this->text;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
    
    // Бизнес-методы
    public function isFromUser(?int $userId): bool
    {
        return $this->userId !== null && $this->userId === $userId;
    }
    
    public function getFormattedDate(): string
    {
        return $this->createdAt->format('d.m.Y H:i');
    }
    
    // Статический конструктор для создания новых отзывов
    public static function create(
        string $author,
        string $text,
        int $rating,
        ?int $userId = null
    ): self {
        return new self(
            new ReviewId(0), // ID 0 для новых записей
            new Author($author),
            new Rating($rating),
            new Text($text),
            new DateTime(),
            $userId
        );
    }
}
