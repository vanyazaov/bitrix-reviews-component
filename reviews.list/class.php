<?php
namespace Custom\Reviews\Component;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Custom\Reviews\Component\Lib\Infrastructure\Repository\OrmReviewRepository;
use Custom\Reviews\Component\Lib\Application\Service\ReviewService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class ReviewsListComponent extends \CBitrixComponent
{
    private ReviewService $reviewService;
    
    public function __construct($component = null)
    {
        parent::__construct($component);
        
        // Инициализируем сервис с зависимостями
        $repository = new OrmReviewRepository();
        $this->reviewService = new ReviewService($repository);
    }

    public function onPrepareComponentParams($arParams): array
    {
        $arParams['COUNT'] = (int)($arParams['COUNT'] ?? 10);
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 300); // 5 минут
        $arParams['AJAX_MODE'] = $arParams['AJAX_MODE'] ?? 'Y';
        
        return $arParams;
    }

    public function executeComponent()
    {
        try {
            $this->initialize();
            
            if (!$this->readFromCache()) {
                $this->getReviews();
                $this->setResultCacheKeys(['REVIEWS', 'TOTAL_COUNT']);
                $this->includeComponentTemplate();
            }
            
        } catch (\Exception $e) {
            $this->arResult['ERROR'] = $e->getMessage();
            $this->includeComponentTemplate();
        }
    }

    private function initialize(): void
    {
        // Подключаем необходимые модули
        if (!Loader::includeModule('main')) {
            throw new \Exception('Main module not installed');
        }
        
        $this->arResult = [
            'REVIEWS' => [],
            'TOTAL_COUNT' => 0,
            'ERROR' => null,
        ];
    }

    private function getReviews(): void
    {
        $reviews = $this->reviewService->getReviews($this->arParams['COUNT']);
        $totalCount = $this->reviewService->getReviewCount();
        
        // Преобразуем сущности в массивы для шаблона
        $this->arResult['REVIEWS'] = array_map(function($review) {
            return [
                'ID' => $review->getId()->getValue(),
                'AUTHOR' => $review->getAuthor()->getValue(),
                'TEXT' => $review->getText()->getValue(),
                'RATING' => $review->getRating()->getValue(),
                'STARS' => $review->getRating()->getStars(),
                'CREATED_AT' => $review->getCreatedAt(),
                'FORMATTED_DATE' => $review->getFormattedDate(),
                'USER_ID' => $review->getUserId(),
            ];
        }, $reviews);
        
        $this->arResult['TOTAL_COUNT'] = $totalCount;
    }

    private function readFromCache(): bool
    {
        if ($this->arParams['CACHE_TIME'] > 0 && !$this->isAjaxRequest()) {
            $cacheId = $this->getCacheId();
            $cachePath = $this->getCachePath();
            
            if ($this->startResultCache($this->arParams['CACHE_TIME'], $cacheId, $cachePath)) {
                $this->getReviews();
                $this->endResultCache();
                return true;
            }
        }
        
        return false;
    }

    private function isAjaxRequest(): bool
    {
        return Context::getCurrent()->getRequest()->isAjaxRequest();
    }

    private function getCacheId(): string
    {
        return md5(serialize([
            $this->arParams['COUNT'],
            $this->getPageNumber(),
        ]));
    }

    private function getPageNumber(): int
    {
        $request = Context::getCurrent()->getRequest();
        return (int)$request->get('PAGEN_1') ?: 1;
    }
}
