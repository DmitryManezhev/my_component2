<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<div class="tariffs-list">
<? foreach ($arResult['ITEMS'] as $arItem): ?>
    <div class="tariff-card js-popup-block" data-id="<?= $arItem['ID'] ?>">
        
        <!-- Скрытые данные для системы корзины -->
        <div style="display: none;" 
             data-item='{"ID":"<?= $arItem['ID'] ?>","IBLOCK_ID":"<?= $arItem['IBLOCK_ID'] ?>","PRODUCT_ID":"<?= $arItem['ID'] ?>","QUANTITY":<?= $arItem['QUANITY'] ?: 1 ?>,"PRICE":"<?= $arItem['PRICE'] ?>","NAME":"<?= htmlspecialchars($arItem['NAME'], ENT_QUOTES) ?>","CURRENCY":"RUB","MEASURE_RATIO":"1","AVAILABLE_QUANTITY":"<?= $arItem['CATALOG_QUANTITY'] ?>","CHECK_MAX_QUANTITY":"Y"}'></div>
        
        <? if ($arItem['PREVIEW_PIC']): ?>
            <img src="<?= $arItem['PREVIEW_PIC'] ?>" alt="<?= $arItem['NAME'] ?>" class="tariff-card__img">
        <? endif; ?>

        <h3 class="tariff-card__title"><?= $arItem['NAME'] ?></h3>
       <div class="tariff-card__price">
    <?= ($arItem['PRICE'] > 0)
        ? number_format($arItem['PRICE'], 0, '.', ' ') . ' ₽'
        : 'Цена по договорённости' ?>
</div>
        <div class="tariff-card__text"><?= $arItem['PREVIEW_TEXT'] ?></div>

        <div class="tariff-detail__props">
            <p><strong>Тип подключения:</strong> <?= htmlspecialchars($arItem['CONNECTION_TYPE']) ?></p>
            <p><strong>Оборудование:</strong> <?= $arItem['HAS_EQUIPMENT'] ? 'Да (' . htmlspecialchars($arItem['EQUIPMENT_TYPE']) . ')' : 'Нет' ?></p>
            <p><strong>CAN-шина:</strong> <?= $arItem['HAS_CAN'] ? 'Да' : 'Нет' ?></p>
            <p><strong>Блокировка двигателя:</strong> <?= $arItem['HAS_ENGINE_BLOCK'] ? 'Да' : 'Нет' ?></p>
            <p><strong>Установка и настройка:</strong> <?= $arItem['HAS_INSTALLATION'] ? 'Да' : 'Нет' ?></p>
            <p><strong>Консультация:</strong> <?= $arItem['HAS_CONSULTATION'] ? 'Да' : 'Нет' ?></p>
            <p><strong>Оборудование (₽):</strong> <?= number_format($arItem['EQUIPMENT_COST'], 0, '.', ' ') ?></p>
            <p><strong>Установка (₽):</strong> <?= number_format($arItem['INSTALLATION_COST'], 0, '.', ' ') ?></p>
            <p><strong>Абонентская плата (₽/год):</strong> <?= number_format($arItem['SUBSCRIPTION_COST'], 0, '.', ' ') ?></p>
            <p><strong>Состав пакета:</strong><br><?= $arItem['PACKAGE_DESCRIPTION'] ?></p>
        </div>

        <!-- Блок покупки в точном стиле Aspro -->
        <div class="catalog-block__info-bottom">
            <div class="line-block line-block--8 line-block--8-vertical flexbox--wrap flexbox--justify-center">
                <div class="line-block__item js-btn-state-wrapper flex-1 catalog-wide-button">
                    <div class="js-replace-btns js-config-btns" data-btn-config='{"BASKET_URL":"","BASKET":true,"ORDER_BTN":false,"BTN_CLASS":"btn-sm btn-wide","BTN_CLASS_MORE":"btn-sm bg-theme-target border-theme-target","BTN_IN_CART_CLASS":"btn-sm","BTN_CLASS_SUBSCRIBE":"btn-sm","BTN_ORDER_CLASS":"btn-sm btn-wide btn-transparent-border","ONE_CLICK_BUY":false,"SHOW_COUNTER":true,"CATALOG_IBLOCK_ID":"<?= $arItem['IBLOCK_ID'] ?>","ITEM_ID":"<?= $arItem['ID'] ?>"}'>
                        <div class="buy_block btn-actions__inner" id="bx_basket_div_<?= $arItem['ID'] ?>">
                            <div class="buttons">
                                <div class="line-block line-block--12-vertical line-block--align-normal flexbox--direction-column">
                                    <div class="line-block__item">
                                        <? if ($arItem['CATALOG_QUANTITY'] > 0): ?>
                                            <!-- Кнопка "В корзину" -->
                                            <span class="item-action item-action--basket">
                                                <span class="btn btn-default btn-sm btn-wide to_cart animate-load js-item-action has-ripple" 
                                                      data-action="basket" 
                                                      data-id="<?= $arItem['ID'] ?>" 
                                                      data-ratio="1" 
                                                      data-float_ratio="1" 
                                                      data-quantity="<?= $arItem['QUANITY'] ?: 1 ?>" 
                                                      data-max="<?= $arItem['CATALOG_QUANTITY'] ?>" 
                                                      data-bakset_div="bx_basket_div_<?= $arItem['ID'] ?>" 
                                                      data-props="" 
                                                      data-add_props="N" 
                                                      data-part_props="N" 
                                                      data-empty_props="Y" 
                                                      data-offers="" 
                                                      title="Заказать" 
                                                      data-title="Заказать" 
                                                      data-title_added="В корзине">Оформить заказ</span>
                                            </span>
                                            
                                            <!-- Счетчик количества (показывается когда товар в корзине) -->
                                            <div class="btn btn-default in_cart btn-sm has-ripple ts-tariff">
<span>Укажите кол-во ТС</span>
                                                <div class="counter js-ajax">
                                                    <span class="counter__action counter__action--minus" data-min="1">-</span>
                                                    <div class="counter__count-wrapper">
                                                        <input type="text" value="<?= $arItem['QUANITY'] ?: 1 ?>" class="counter__count" maxlength="255">
                                                    </div>
                                                    <span class="counter__action counter__action--plus" data-max="<?= $arItem['CATALOG_QUANTITY'] ?>">+</span>
                                                </div>
                                            </div>
                                        <? else: ?>
                                            <!-- Кнопка "Оставить заявку" -->
                                            <span class="item-action item-action--basket">
                                                <a href="javascript:void(0);" 
                                                   class="btn btn-default btn-sm btn-wide animate-load has-ripple" 
                                                   data-event="jqm" 
                                                   data-param-id="4">Оставить заявку</a>
                                            </span>
                                        <? endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ссылка "Подробнее" -->
            <div class="line-block line-block--8 line-block--8-vertical flexbox--wrap flexbox--justify-center" style="margin-top: 15px;">
                <div class="line-block__item flex-1">
                    <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" class="btn btn-default btn-sm btn-wide btn-transparent-border">Подробнее</a>
                </div>
            </div>
        </div>
    </div>
<? endforeach; ?>
</div>

<style>
.tariffs-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin: 40px auto;
    max-width: 1200px;
    padding: 0 20px;
}

.tariff-card {
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 25px;
    background: #fff;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.tariff-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #18bb44, #118530ff);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.tariff-card:hover {
    box-shadow: 0 8px 25px rgba(0,123,255,0.15);
    transform: translateY(-2px);
}

.tariff-card:hover::before {
    transform: scaleX(1);
}

.tariff-card__img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 15px;
}

.tariff-card__title { 
    font-size: 24px; 
    margin: 0 0 10px;
    color: #333;
    font-weight: 600;
}

.tariff-card__price { 
    font-size: 26px; 
    font-weight: 700; 
    color: #18bb44; 
    margin-bottom: 15px; 
}

.tariff-card__text { 
    font-size: 15px; 
    line-height: 1.5; 
    margin-bottom: 20px;
    color: #666;
}

.tariff-detail__props {
    margin-bottom: 20px;
    font-size: 14px;
}

.tariff-detail__props p {
    margin: 8px 0;
    line-height: 1.4;
}

.tariff-detail__props strong {
    color: #333;
}

/* Блок покупки в стиле Aspro */
.catalog-block__info-bottom {
    margin-top: 20px;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.catalog-wide-button {
    min-width: 0;
}

/* Состояния кнопок - как в стандартном Aspro */
.item-action--basket.active .to_cart {
    display: none;
}

.item-action--basket.active + .in_cart {
    display: flex !important;
}

.item-action--basket:not(.active) + .in_cart {
    display: none !important;
}

/* Адаптивность */
@media (max-width: 768px) {
    .tariffs-list { 
        grid-template-columns: 1fr;
        padding: 0 10px;
        gap: 20px;
    }
    
    .tariff-card {
        padding: 20px;
    }
    
    .tariff-card__title {
        font-size: 20px;
    }
    
    .tariff-card__price {
        font-size: 22px;
    }
}

@media (max-width: 480px) {
    .tariffs-list {
        padding: 0 5px;
    }
    
    .tariff-card {
        padding: 15px;
    }
    
    .tariff-card__title {
        font-size: 18px;
    }
    
    .tariff-card__price {
        font-size: 20px;
    }
    
    .tariff-detail__props {
        font-size: 13px;
    }
}
</style>