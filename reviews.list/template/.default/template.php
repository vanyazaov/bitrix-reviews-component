<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
?>

<div class="reviews-list">
    <h2>Список отзывов (DDD)</h2>
    
    <?php if (!empty($arResult['ERROR'])): ?>
        <div class="alert alert-danger"><?= $arResult['ERROR'] ?></div>
    <?php endif; ?>

    <div class="reviews-container">
        <?php foreach ($arResult['REVIEWS'] as $review): ?>
            <div class="review-card">
                <h3><?= htmlspecialchars($review['AUTHOR']) ?></h3>
                <div class="rating"><?= $review['STARS'] ?? '★' ?></div>
                <p><?= htmlspecialchars($review['TEXT']) ?></p>
                <small><?= $review['FORMATTED_DATE'] ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>
