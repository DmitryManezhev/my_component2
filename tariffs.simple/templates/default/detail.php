<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

$this->addExternalCss(SITE_TEMPLATE_PATH . "/css/style.css");
$arItem = $arResult['ITEM'];
?>

<div class="aspro-tariff-landing">
    <div class="aspro-landing-header">
        <h1 class="aspro-landing-title"><?= htmlspecialchars($arItem['NAME']) ?></h1>
        <?php if (!empty($arItem['PREVIEW_TEXT'])): ?>
            <p class="aspro-landing-subtitle"><?= htmlspecialchars($arItem['PREVIEW_TEXT']) ?></p>
        <?php endif; ?>
    </div>
    <div class="aspro-landing-content">
        <?php if (!empty($arItem['DETAIL_PIC'])): ?>
            <img src="<?= $arItem['DETAIL_PIC'] ?>" alt="<?= htmlspecialchars($arItem['NAME']) ?>" class="aspro-landing-image">
        <?php endif; ?>

        <div class="aspro-landing-price">
            <?= number_format($arItem['PRICE'], 0, ',', ' ') ?> руб.
        </div>

        <?php if (!empty($arItem['DETAIL_TEXT'])): ?>
            <div class="aspro-landing-description">
                <?= $arItem['DETAIL_TEXT'] ?>
            </div>
        <?php endif; ?>

        <div class="aspro-landing-actions">
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
    
    <div class="aspro-landing-features">
        <h3>Особенности тарифа</h3>
        <ul class="aspro-tariff-features">
            <?php
            if (isset($arItem['PROPERTIES']) && is_array($arItem['PROPERTIES'])):
                foreach ($arItem['PROPERTIES'] as $code => $prop):
                    if (!empty($prop['VALUE']) && $prop['VALUE'] !== null):
            ?>
                        <li>
                            <strong><?= htmlspecialchars($prop['NAME']) ?>:</strong>
                            <span>&nbsp;<?= is_array($prop['VALUE']) ? implode(', ', $prop['VALUE']) : htmlspecialchars($prop['VALUE']) ?></span>
                        </li>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
        </ul>
    </div>
</div>
