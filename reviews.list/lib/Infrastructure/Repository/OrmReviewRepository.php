<?php
namespace Custom\Reviews\Component\Lib\Infrastructure\Repository;

use Custom\Reviews\Component\Lib\Domain\Repository\ReviewRepositoryInterface;
use Custom\Reviews\Component\Lib\Domain\Entity\Review;
use Custom\Reviews\Component\Lib\Domain\ValueObject\ReviewId;
use Custom\Reviews\Component\Lib\Domain\ValueObject\Author;
use Custom\Reviews\Component\Lib\Domain\ValueObject\Rating;
use Custom\Reviews\Component\Lib\Domain\ValueObject\Text;
use Custom\Reviews\Component\Lib\ReviewsTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;

class OrmReviewRepository implements ReviewRepositoryInterface
{
    public function save(Review $review): void
    {
        $data = [
            'AUTHOR' => $review->getAuthor()->getValue(),
            'TEXT' => $review->getText()->getValue(),
            'RATING' => $review->getRating()->getValue(),
            'CREATED_AT' => $review->getCreatedAt(),
            'USER_ID' => $review->getUserId(),
        ];

        try {
            if ($review->getId()->getValue() > 0) {
                // Обновление существующей записи
                $result = ReviewsTable::update($review->getId()->getValue(), $data);
            } else {
                // Создание новой записи
                $result = ReviewsTable::add($data);
                
                if ($result->isSuccess()) {
                    // Обновляем ID в entity через Reflection (в реальном проекте лучше использовать Event)
                    $reflection = new \ReflectionClass($review);
                    $idProperty = $reflection->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($review, new ReviewId($result->getId()));
                }
            }
            
            if (!$result->isSuccess()) {
                throw new SystemException(implode(', ', $result->getErrorMessages()));
            }
            
        } catch (\Exception $e) {
            throw new SystemException('Не удалось сохранить отзыв: ' . $e->getMessage());
        }
    }

    public function findById(ReviewId $id): ?Review
    {
        try {
            $reviewData = ReviewsTable::getById($id->getValue())->fetch();
            
            if (!$reviewData) {
                return null;
            }
            
            return $this->mapToEntity($reviewData);
            
        } catch (\Exception $e) {
            throw new SystemException('Не удалось найти отзыв: ' . $e->getMessage());
        }
    }

    public function findAll(int $limit = 10, int $offset = 0): array
    {
        try {
            $reviews = [];
            
            $query = ReviewsTable::query()
                ->setSelect(['*'])
                ->setOrder(['CREATED_AT' => 'DESC'])
                ->setLimit($limit)
                ->setOffset($offset);
                
            $result = $query->exec();
            
            while ($reviewData = $result->fetch()) {
                $reviews[] = $this->mapToEntity($reviewData);
            }
            
            return $reviews;
            
        } catch (\Exception $e) {
            throw new SystemException('Не удалось найти отзывы: ' . $e->getMessage());
        }
    }

    public function countAll(): int
    {
        try {
            return ReviewsTable::getCount();
        } catch (\Exception $e) {
            throw new SystemException('Не удалось подсчитать отзывы: ' . $e->getMessage());
        }
    }

    public function getLatest(int $count = 10): array
    {
        return $this->findAll($count, 0);
    }

    private function mapToEntity(array $data): Review
    {
        return new Review(
            new ReviewId((int)$data['ID']),
            new Author($data['AUTHOR']),
            new Rating((int)$data['RATING']),
            new Text($data['TEXT']),
            $data['CREATED_AT'],
            $data['USER_ID'] ? (int)$data['USER_ID'] : null
        );
    }
}
