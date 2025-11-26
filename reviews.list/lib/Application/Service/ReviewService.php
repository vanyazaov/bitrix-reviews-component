<?php
namespace Custom\Reviews\Component\Lib\Application\Service;

use Custom\Reviews\Component\Lib\Domain\Entity\Review;
use Custom\Reviews\Component\Lib\Domain\Repository\ReviewRepositoryInterface;

class ReviewService
{
    private ReviewRepositoryInterface $repository;

    public function __construct(ReviewRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function addReview(
        string $author,
        string $text,
        int $rating,
        ?int $userId = null
    ): Review {
        // Создаем сущность (валидация происходит в Value Objects)
        $review = Review::create($author, $text, $rating, $userId);
        
        // Сохраняем через repository
        $this->repository->save($review);
        
        return $review;
    }

    public function getReviews(int $count = 10): array
    {
        return $this->repository->getLatest($count);
    }

    public function getReviewCount(): int
    {
        return $this->repository->countAll();
    }
    
    public function getReviewById(int $id): ?Review
    {
        return $this->repository->findById(new \Custom\Reviews\Component\Lib\Domain\ValueObject\ReviewId($id));
    }
}
