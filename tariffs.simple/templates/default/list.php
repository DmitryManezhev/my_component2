<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

$this->addExternalCss(SITE_TEMPLATE_PATH . "/css/style.css");
?>

<div class="aspro-tariff-grid">
    <?php foreach ($arResult['ITEMS'] as $arItem): ?>
        <div class="aspro-tariff-card">
            <div class="aspro-tariff-header">
                <h2 class="aspro-tariff-title"><?= htmlspecialchars($arItem['NAME']) ?></h2>
                <div class="aspro-tariff-price"><?= number_format($arItem['PRICE'], 0, ',', ' ') ?></div>
            </div>
            <div class="aspro-tariff-body-content">
                <?php if ($arItem['PREVIEW_TEXT']): ?>
                    <p class="aspro-tariff-description"><?= htmlspecialchars($arItem['PREVIEW_TEXT']) ?></p>
                <?php endif; ?>
                
                <ul class="aspro-tariff-features">
                    <?php 
                 foreach ($arItem['PROPERTIES'] as $code => $prop) {
    echo '<li><strong>' . htmlspecialchars($prop['NAME']) . ':</strong> ';
    echo '<span>' . (is_array($prop['VALUE']) ? implode(', ', $prop['VALUE']) : htmlspecialchars($prop['VALUE'])) . '</span></li>';
}
                    ?>
                                <li>
                                    <strong><?= htmlspecialchars($prop['NAME']) ?>:</strong>
                                    <span>&nbsp;<?= is_array($prop['VALUE']) ? implode(', ', $prop['VALUE']) : htmlspecialchars($prop['VALUE']) ?></span>
                                </li>
                            <?php 
                    
                    ?>
                </ul>

            </div>
            <div class="aspro-tariff-actions">
                <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" class="aspro-btn aspro-btn-secondary">Подробнее</a>
                <?php if ((int)$arItem['CATALOG_QUANTITY'] > 0): ?>
                    <a href="?action=ADD_TO_BASKET&PRODUCT_ID=<?= $arItem['ID'] ?>" class="aspro-btn aspro-btn-primary">
                        Добавить в корзину
                    </a>
                <?php else: ?>
                    <a href="#" class="aspro-btn aspro-btn-primary" data-event="jqm" data-param-id="4" data-name="Заявка на тариф">
                        Оставить заявку
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
