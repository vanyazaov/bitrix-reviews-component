<?php
namespace Custom\Reviews\Component\Lib\Domain\Repository;

use Custom\Reviews\Component\Lib\Domain\Entity\Review;
use Custom\Reviews\Component\Lib\Domain\ValueObject\ReviewId;

interface ReviewRepositoryInterface
{
    public function save(Review $review): void;
    
    public function findById(ReviewId $id): ?Review;
    
    /**
     * @return Review[]
     */
    public function findAll(int $limit = 10, int $offset = 0): array;
    
    public function countAll(): int;
    
    public function getLatest(int $count = 10): array;
}
