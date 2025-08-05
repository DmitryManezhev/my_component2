<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arItem = $arResult['ITEM'];
if (!$arItem) { 
    echo '<p>Тариф не найден</p>'; 
    return; 
}

// Подключаем стили и скрипты
$APPLICATION->AddHeadScript($this->GetFolder() . '/script.js');
$APPLICATION->SetAdditionalCSS($this->GetFolder() . '/style.css');
?>

<div class="tariff-detail">
    <div class="tariff-detail__nav">
        <a class="btn btn--outline btn--back" href="<?= $arParams['SEF_FOLDER'] ?>">&larr; Назад к списку</a>
    </div>

    <div class="tariff-detail__content">
        <h1 class="tariff-detail__title"><?= htmlspecialchars($arItem['NAME']) ?></h1>
        
        <div class="tariff-detail__main">
            <div class="tariff-detail__image-block">
                <? if ($arItem['DETAIL_PIC']): ?>
                    <img class="tariff-detail__image" src="<?= $arItem['DETAIL_PIC'] ?>" alt="<?= htmlspecialchars($arItem['NAME']) ?>">
                <? elseif ($arItem['PREVIEW_PIC']): ?>
                    <img class="tariff-detail__image" src="<?= $arItem['PREVIEW_PIC'] ?>" alt="<?= htmlspecialchars($arItem['NAME']) ?>">
                <? endif; ?>
            </div>
            
            <div class="tariff-detail__info">
                <div class="tariff-detail__price-block">
                    <div class="tariff-detail__price">
                        <?= ($arItem['PRICE'] > 0) 
                            ? number_format($arItem['PRICE'], 0, '.', ' ') . ' ₽' 
                            : 'По договорённости' ?>
                    </div>
                    <div class="tariff-detail__price-unit">за 1 транспортное средство</div>
                </div>

                <? if ($arItem['PREVIEW_TEXT']): ?>
                    <div class="tariff-detail__description"><?= $arItem['PREVIEW_TEXT'] ?></div>
                <? endif; ?>

                <!-- Блок покупки -->
                <div class="tariff-buy-section" data-entity="basket-item" data-id="<?= $arItem['ID'] ?>">
                    <!-- Скрытые данные для системы корзины -->
                    <div style="display: none;" 
                         data-item='{"ID":"<?= $arItem['ID'] ?>","IBLOCK_ID":"<?= $arItem['IBLOCK_ID'] ?>","PRODUCT_ID":"<?= $arItem['ID'] ?>","QUANTITY":<?= $arItem['QUANTITY'] ?: 1 ?>,"PRICE":"<?= $arItem['PRICE'] ?>","NAME":"<?= htmlspecialchars($arItem['NAME'], ENT_QUOTES) ?>","CURRENCY":"RUB","MEASURE_RATIO":"1","AVAILABLE_QUANTITY":"<?= $arItem['CATALOG_QUANTITY'] ?>","CHECK_MAX_QUANTITY":"Y"}'></div>
                    
                    <div class="quantity-selection">
                        <label class="quantity-label">Количество транспортных средств:</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn" data-action="minus">−</button>
                            <input type="number" 
                                   class="quantity-field" 
                                   value="<?= $arItem['VEHICLE_COUNT'] ?: 1 ?>" 
                                   min="1" 
                                   max="<?= $arItem['CATALOG_QUANTITY'] ?>"
                                   data-entity="basket-item-quantity-field">
                            <button type="button" class="quantity-btn" data-action="plus">+</button>
                        </div>
                        <div class="quantity-info">
                            <div class="total-price">
                                Итоговая стоимость: 
                                <span class="total-amount" data-price="<?= $arItem['PRICE'] ?>">
                                    <?= number_format($arItem['PRICE'] * ($arItem['VEHICLE_COUNT'] ?: 1), 0, '.', ' ') ?> ₽
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="purchase-actions">
                        <? if ($arItem['CATALOG_QUANTITY'] > 0): ?>
                            <span class="item-action item-action--basket">
                                <button class="btn btn--purchase js-item-action" 
                                        data-action="basket" 
                                        data-id="<?= $arItem['ID'] ?>" 
                                        data-ratio="1" 
                                        data-float_ratio="1" 
                                        data-quantity="<?= $arItem['QUANTITY'] ?: 1 ?>" 
                                        data-max="<?= $arItem['CATALOG_QUANTITY'] ?>" 
                                        data-bakset_div="bx_basket_div_<?= $arItem['ID'] ?>" 
                                        data-props="" 
                                        data-add_props="N" 
                                        data-part_props="N" 
                                        data-empty_props="Y" 
                                        data-offers="" 
                                        title="В корзину" 
                                        data-title="В корзину" 
                                        data-title_added="В корзине">
                                    <span class="btn-text">В корзину</span>
                                </button>
                            </span>
                        <? else: ?>
                            <a href="javascript:void(0);" 
                               class="btn btn--purchase" 
                               data-event="jqm" 
                               data-param-id="4">Оставить заявку</a>
                        <? endif; ?>
                        
                        <div class="purchase-benefits">
                            <div class="benefit-item">✓ Бесплатная консультация</div>
                            <div class="benefit-item">✓ Гарантия качества</div>
                            <div class="benefit-item">✓ Техподдержка 24/7</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Характеристики -->
        <div class="tariff-detail__characteristics">
            <h3>Характеристики тарифа</h3>
            <div class="characteristics-grid">
                <div class="characteristic-item">
                    <div class="characteristic-icon">🔗</div>
                    <div class="characteristic-content">
                        <span class="characteristic-label">Тип подключения</span>
                        <span class="characteristic-value"><?= htmlspecialchars($arItem['CONNECTION_TYPE']) ?></span>
                    </div>
                </div>
                
                <div class="characteristic-item">
                    <div class="characteristic-icon">📡</div>
                    <div class="characteristic-content">
                        <span class="characteristic-label">Оборудование</span>
                        <span class="characteristic-value <?= $arItem['HAS_EQUIPMENT'] ? 'text-success' : 'text-muted' ?>">
                            <?= $arItem['HAS_EQUIPMENT'] ? 'Да (' . htmlspecialchars($arItem['EQUIPMENT_TYPE']) . ')' : 'Нет' ?>
                        </span>
                    </div>
                </div>
                
                <div class="characteristic-item">
                    <div class="characteristic-icon">🚗</div>
                    <div class="characteristic-content">
                        <span class="characteristic-label">CAN-шина</span>
                        <span class="characteristic-value <?= $arItem['HAS_CAN'] ? 'text-success' : 'text-muted' ?>">
                            <?= $arItem['HAS_CAN'] ? 'Поддерживается' : 'Не поддерживается' ?>
                        </span>
                    </div>
                </div>
                
                <div class="characteristic-item">
                    <div class="characteristic-icon">🔒</div>
                    <div class="characteristic-content">
                        <span class="characteristic-label">Блокировка двигателя</span>
                        <span class="characteristic-value <?= $arItem['HAS_ENGINE_BLOCK'] ? 'text-success' : 'text-muted' ?>">
                            <?= $arItem['HAS_ENGINE_BLOCK'] ? 'Да' : 'Нет' ?>
                        </span>
                    </div>
                </div>
                
                <div class="characteristic-item">
                    <div class="characteristic-icon">🔧</div>
                    <div class="characteristic-content">
                        <span class="characteristic-label">Установка и настройка</span>
                        <span class="characteristic-value <?= $arItem['HAS_INSTALLATION'] ? 'text-success' : 'text-muted' ?>">
                            <?= $arItem['HAS_INSTALLATION'] ? 'Включена' : 'Не включена' ?>
                        </span>
                    </div>
                </div>
                
                <div class="characteristic-item">
                    <div class="characteristic-icon">💬</div>
                    <div class="characteristic-content">
                        <span class="characteristic-label">Консультация</span>
                        <span class="characteristic-value <?= $arItem['HAS_CONSULTATION'] ? 'text-success' : 'text-muted' ?>">
                            <?= $arItem['HAS_CONSULTATION'] ? 'Бесплатная' : 'Не предоставляется' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <? if ($arItem['EQUIPMENT_COST'] > 0 || $arItem['INSTALLATION_COST'] > 0 || $arItem['SUBSCRIPTION_COST'] > 0): ?>
            <!-- Детализация стоимости -->
            <div class="tariff-detail__costs">
                <h3>Детализация стоимости</h3>
                <div class="costs-table">
                    <? if ($arItem['EQUIPMENT_COST'] > 0): ?>
                        <div class="cost-row">
                            <span class="cost-label">Оборудование</span>
                            <span class="cost-dots"></span>
                            <span class="cost-value"><?= number_format($arItem['EQUIPMENT_COST'], 0, '.', ' ') ?> ₽</span>
                        </div>
                    <? endif; ?>
                    <? if ($arItem['INSTALLATION_COST'] > 0): ?>
                        <div class="cost-row">
                            <span class="cost-label">Установка и настройка</span>
                            <span class="cost-dots"></span>
                            <span class="cost-value"><?= number_format($arItem['INSTALLATION_COST'], 0, '.', ' ') ?> ₽</span>
                        </div>
                    <? endif; ?>
                    <? if ($arItem['SUBSCRIPTION_COST'] > 0): ?>
                        <div class="cost-row">
                            <span class="cost-label">Абонентская плата</span>
                            <span class="cost-dots"></span>
                            <span class="cost-value"><?= number_format($arItem['SUBSCRIPTION_COST'], 0, '.', ' ') ?> ₽/год</span>
                        </div>
                    <? endif; ?>
                </div>
            </div>
        <? endif; ?>

        <? if ($arItem['PACKAGE_DESCRIPTION']): ?>
            <!-- Состав пакета -->
            <div class="tariff-detail__package">
                <h3>Что входит в тариф</h3>
                <div class="package-content"><?= $arItem['PACKAGE_DESCRIPTION'] ?></div>
            </div>
        <? endif; ?>

        <? if ($arItem['DETAIL_TEXT']): ?>
            <!-- Подробное описание -->
            <div class="tariff-detail__description-full">
                <h3>Подробное описание</h3>
                <div class="description-content"><?= $arItem['DETAIL_TEXT'] ?></div>
            </div>
        <? endif; ?>
    </div>
</div>

