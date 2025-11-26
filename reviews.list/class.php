<?php
namespace Custom\Reviews\Component;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Custom\Reviews\Component\Lib\Infrastructure\Repository\OrmReviewRepository;
use Custom\Reviews\Component\Lib\Application\Service\ReviewService;

// Подключаем автолоадер
require_once __DIR__ . '/autoload.php';

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
        $startTime = microtime(true);
        
        try {
            $this->initialize();
            
            if ($this->startResultCache($this->arParams['CACHE_TIME'], $this->getCacheId())) {
                // Кеш ПУСТОЙ - генерируем данные
                $this->getReviews();
                $this->setResultCacheKeys(['REVIEWS', 'TOTAL_COUNT']);
                $fromCache = false;
                $this->includeComponentTemplate();
                $this->endResultCache();
            } else {
                // Кеш ЕСТЬ
                $fromCache = true;
                //$this->includeComponentTemplate();
            }
            
        } catch (\Exception $e) {
            $this->arResult['ERROR'] = $e->getMessage();
            $this->includeComponentTemplate();
        }
        // КОММЕНТАРИЙ ВНЕ кеширования
        if ($fromCache) {
            echo "<!-- Данные из кеша: " . round((microtime(true) - $startTime) * 1000, 2) . "ms -->";
        } else {
            echo "<!-- Данные сгенерированы: " . round((microtime(true) - $startTime) * 1000, 2) . "ms -->";
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

    public function readFromCache()
    {
        if ($this->arParams['CACHE_TIME'] > 0 && !$this->isAjaxRequest()) {
            $cacheId = $this->getCacheId();
            $cachePath = $this->getCachePath();
            
            // ЕСЛИ кеш ЕСТЬ - startResultCache вернет false и данные подгрузятся автоматически
            if (!$this->startResultCache($this->arParams['CACHE_TIME'], $cacheId, $cachePath)) {
                return true; // Кеш найден
            }
        }
        
        return false; // Кеширование отключено
    }

    public function isAjaxRequest(): bool
    {
        return Context::getCurrent()->getRequest()->isAjaxRequest();
    }

    public function getCacheId($additionalCacheId = false)
    {
        $cacheId = [
            $this->arParams['COUNT'],
            $this->getPageNumber(),
        ];
        
        if ($additionalCacheId !== false) {
            $cacheId[] = $additionalCacheId;
        }
        
        return md5(serialize($cacheId));
    }

    public function getPageNumber(): int
    {
        $request = Context::getCurrent()->getRequest();
        return (int)$request->get('PAGEN_1') ?: 1;
    }
}
