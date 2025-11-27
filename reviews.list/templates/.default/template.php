<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
?>

<div class="reviews-list" id="reviews-component-<?= $this->randString() ?>" data-ajax-url="<?= $componentPath ?>/ajax.php">
    <!-- Статус кеша для отладки -->
    <?php if ($_GET['debug_cache']): ?>
    <div class="cache-info" style="background: #<?= $arResult['CACHE_INFO']['type'] === 'cached' ? '90EE90' : 'FFB6C1'; ?>; padding: 5px; margin: 10px 0;">
        Статус: <?= $arResult['CACHE_INFO']['type'] === 'cached' ? 'ИЗ КЕША' : 'СГЕНЕРИРОВАНЫ' ?> | 
        Время: <?= $arResult['CACHE_INFO']['time'] ?>ms
    </div>
    <?php endif; ?>

    <!-- Заголовок -->
    <div class="reviews-header">
        <h2>Отзывы о жилом комплексе</h2>
        <div class="reviews-stats">
            Всего отзывов: <span class="reviews-count"><?= $arResult['TOTAL_COUNT'] ?></span>
        </div>
    </div>

    <!-- Форма добавления отзыва -->
    <div class="review-form-container">
        <h3>Добавить отзыв</h3>
        <form class="review-form" id="review-form">
            <?= bitrix_sessid_post() ?>
            <div class="form-group">
                <label for="author">Ваше имя *</label>
                <input type="text" id="author" name="author" required maxlength="100">
                <div class="error-message" data-field="author"></div>
            </div>

            <div class="form-group">
                <label for="rating">Оценка *</label>
                <select id="rating" name="rating" required>
                    <option value="">Выберите оценку</option>
                    <option value="5">5 - Отлично</option>
                    <option value="4">4 - Хорошо</option>
                    <option value="3">3 - Удовлетворительно</option>
                    <option value="2">2 - Плохо</option>
                    <option value="1">1 - Ужасно</option>
                </select>
                <div class="error-message" data-field="rating"></div>
            </div>

            <div class="form-group">
                <label for="text">Текст отзыва *</label>
                <textarea id="text" name="text" required minlength="10" maxlength="2000" 
                         placeholder="Расскажите о вашем опыте..."></textarea>
                <div class="error-message" data-field="text"></div>
            </div>

            <button type="submit" class="submit-btn">
                <span class="btn-text">Опубликовать отзыв</span>
                <span class="btn-loading" style="display: none;">Отправка...</span>
            </button>
        </form>
    </div>

    <!-- Список отзывов -->
    <div class="reviews-container">
        <?php if (empty($arResult['REVIEWS'])): ?>
            <div class="no-reviews">
                <p>Пока нет отзывов. Будьте первым!</p>
            </div>
        <?php else: ?>
            <?php foreach ($arResult['REVIEWS'] as $review): ?>
                <div class="review-card" data-review-id="<?= $review['ID'] ?>">
                    <div class="review-header">
                        <div class="review-author"><?= htmlspecialchars($review['AUTHOR']) ?></div>
                        <div class="review-date"><?= $review['FORMATTED_DATE'] ?></div>
                    </div>
                    
                    <div class="review-rating">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['RATING'] ? 'active' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-value"><?= $review['RATING'] ?>/5</span>
                    </div>
                    
                    <div class="review-text"><?= nl2br(htmlspecialchars($review['TEXT'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Уведомления -->
<div id="review-notification" class="notification" style="display: none;"></div>

<!-- Подключаем стили и скрипты -->
<link rel="stylesheet" href="<?= $this->GetFolder() ?>/style.css">
<script src="<?= $this->GetFolder() ?>/script.js"></script>
