<?php
// Автолоадер для компонента
$basePath = __DIR__ . '/lib/';

$classes = [
    'Custom\Reviews\Component\Lib\ReviewsTable' => $basePath . 'reviewsTable.php',
    'Custom\Reviews\Component\Lib\Domain\ValueObject\ReviewId' => $basePath . 'Domain/ValueObject/ReviewId.php',
    'Custom\Reviews\Component\Lib\Domain\ValueObject\Author' => $basePath . 'Domain/ValueObject/Author.php',
    'Custom\Reviews\Component\Lib\Domain\ValueObject\Rating' => $basePath . 'Domain/ValueObject/Rating.php',
    'Custom\Reviews\Component\Lib\Domain\ValueObject\Text' => $basePath . 'Domain/ValueObject/Text.php',
    'Custom\Reviews\Component\Lib\Domain\Entity\Review' => $basePath . 'Domain/Entity/Review.php',
    'Custom\Reviews\Component\Lib\Domain\Repository\ReviewRepositoryInterface' => $basePath . 'Domain/Repository/ReviewRepositoryInterface.php',
    'Custom\Reviews\Component\Lib\Infrastructure\Repository\OrmReviewRepository' => $basePath . 'Infrastructure/Repository/OrmReviewRepository.php',
    'Custom\Reviews\Component\Lib\Application\Service\ReviewService' => $basePath . 'Application/Service/ReviewService.php',
];

spl_autoload_register(function ($className) use ($classes) {
    if (isset($classes[$className])) {
        require_once $classes[$className];
    }
});
