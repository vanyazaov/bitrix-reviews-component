<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Custom\Reviews\Component\Lib\Infrastructure\Repository\OrmReviewRepository;
use Custom\Reviews\Component\Lib\Application\Service\ReviewService;

/**
 * AJAX обработчик для добавления отзывов
 */
class ReviewsAjaxHandler
{
    private ReviewService $reviewService;
    
    public function __construct()
    {
        $repository = new OrmReviewRepository();
        $this->reviewService = new ReviewService($repository);
    }
    
    public function handleRequest()
    {
        $request = Context::getCurrent()->getRequest();
        
        if (!$request->isPost() || !$request->isAjaxRequest()) {
            return $this->jsonError('Invalid request method');
        }
        
        if (!check_bitrix_sessid()) {
            return $this->jsonError('Invalid sessid');
        }
        
        $action = $request->get('action') ?: 'add_review';
        
        switch ($action) {
            case 'add_review':
                return $this->addReview($request);
            case 'get_reviews':
                return $this->getReviews($request);
            default:
                return $this->jsonError('Unknown action');
        }
    }
    
    private function addReview($request)
    {
        try {
            $author = trim($request->get('author'));
            $text = trim($request->get('text'));
            $rating = (int)$request->get('rating');
            
            // Базовая валидация
            if (empty($author)) {
                return $this->jsonError('Имя автора обязательно');
            }
            
            if (empty($text) || mb_strlen($text) < 10) {
                return $this->jsonError('Текст отзыва должен быть не менее 10 символов');
            }
            
            if ($rating < 1 || $rating > 5) {
                return $this->jsonError('Оценка должна быть от 1 до 5');
            }
            
            // Текущий пользователь (если авторизован)
            global $USER;
            $userId = $USER->IsAuthorized() ? $USER->GetID() : null;
            
            // Добавляем отзыв через сервис
            $review = $this->reviewService->addReview($author, $text, $rating, $userId);
            
            // Очищаем кеш компонента
            $this->clearComponentCache();
            
            return $this->jsonSuccess('Отзыв успешно добавлен', [
                'review' => [
                    'ID' => $review->getId()->getValue(),
                    'AUTHOR' => $review->getAuthor()->getValue(),
                    'TEXT' => $review->getText()->getValue(),
                    'RATING' => $review->getRating()->getValue(),
                    'FORMATTED_DATE' => $review->getFormattedDate(),
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
    
    private function getReviews($request)
    {
        try {
            $count = (int)($request->get('count') ?: 10);
            $reviews = $this->reviewService->getReviews($count);
            
            $reviewsData = array_map(function($review) {
                return [
                    'ID' => $review->getId()->getValue(),
                    'AUTHOR' => $review->getAuthor()->getValue(),
                    'TEXT' => $review->getText()->getValue(),
                    'RATING' => $review->getRating()->getValue(),
                    'FORMATTED_DATE' => $review->getFormattedDate(),
                ];
            }, $reviews);
            
            return $this->jsonSuccess('', ['reviews' => $reviewsData]);
            
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
    
    private function clearComponentCache()
    {
        // Очищаем кеш по тегу (если используется tagged cache)
        if (class_exists('Bitrix\Main\Data\TaggedCache')) {
            $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
            $taggedCache->clearByTag('custom_reviews_list');
        }
        
        // Очищаем управляемый кеш
        $managedCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
        $managedCache->cleanDir('custom/reviews');
    }
    
    private function jsonSuccess($message = '', $data = [])
    {
        return $this->jsonResponse(true, $message, $data);
    }
    
    private function jsonError($message = '')
    {
        return $this->jsonResponse(false, $message);
    }
    
    private function jsonResponse($success, $message = '', $data = [])
    {
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Запускаем обработчик
try {
    $handler = new ReviewsAjaxHandler();
    $handler->handleRequest();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
