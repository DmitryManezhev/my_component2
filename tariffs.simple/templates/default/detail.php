<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);

// Подключаем расширения Aspro
if (class_exists('TSolution\\Extensions')) {
    TSolution\Extensions::init(['catalog', 'notice', 'catalog_detail']);
}

$arItem = $arResult['ITEM'];
if (!$arItem) {
    return;
}

$uniqueId = $arItem['ID'] . '_' . md5($this->randString());
$itemIds = [
    'ID' => $uniqueId,
    'PICT' => $uniqueId . '_pict',
    'PRICE' => $uniqueId . '_price',
    'BASKET' => $uniqueId . '_basket',
    'QUANTITY' => $uniqueId . '_quantity',
];

// Подготавливаем данные для корзины
$basketData = [
    'ID' => $arItem['ID'],
    'IBLOCK_ID' => $arItem['IBLOCK_ID'],
    'PRODUCT_ID' => $arItem['ID'],
    'NAME' => htmlspecialchars($arItem['NAME']),
    'PRICE' => $arItem['PRICE'],
    'CURRENCY' => 'RUB',
    'QUANTITY' => 1,
    'AVAILABLE_QUANTITY' => $arItem['CATALOG_QUANTITY'],
    'CHECK_MAX_QUANTITY' => 'Y',
    'MEASURE_RATIO' => 1,
    'CAN_BUY' => $arItem['CAN_BUY'] ? 'Y' : 'N',
];
?>

<div class="catalog-detail tariff-detail" id="<?= $itemIds['ID'] ?>">
    <div class="maxwidth-theme">
        
        <!-- Хлебные крошки -->
        <div class="breadcrumb-wrapper">
            <?php $APPLICATION->IncludeComponent(
                "bitrix:breadcrumb",
                "main",
                array(
                    "START_FROM" => "0",
                    "PATH" => "",
                    "SITE_ID" => SITE_ID,
                ),
                false
            ); ?>
        </div>
        
        <div class="catalog-detail__wrapper" 
             data-id="<?= $arItem['ID'] ?>"
             data-item='<?= htmlspecialchars(json_encode($basketData), ENT_QUOTES) ?>'>
             
            <div class="row">
                
                <!-- Левая колонка - изображение -->
                <div class="col-md-6 col-sm-12">
                    <div class="catalog-detail__image-block">
                        <div class="catalog-detail__image-wrapper">
                            <?php if ($arItem['DETAIL_PIC'] || $arItem['PREVIEW_PIC']): ?>
                                <img id="<?= $itemIds['PICT'] ?>" 
                                     src="<?= $arItem['DETAIL_PIC'] ?: $arItem['PREVIEW_PIC'] ?>" 
                                     alt="<?= htmlspecialchars($arItem['NAME']) ?>"
                                     class="catalog-detail__image">
                            <?php else: ?>
                                <img id="<?= $itemIds['PICT'] ?>" 
                                     src="<?= SITE_TEMPLATE_PATH ?>/images/no_photo_big.png" 
                                     alt="<?= htmlspecialchars($arItem['NAME']) ?>"
                                     class="catalog-detail__image">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Правая колонка - информация -->
                <div class="col-md-6 col-sm-12">
                    <div class="catalog-detail__info-block">
                        
                        <!-- Заголовок -->
                        <div class="catalog-detail__title-block">
                            <h1 class="catalog-detail__title font_28 font_weight--600">
                                <?= $arItem['NAME'] ?>
                            </h1>
                        </div>
                        
                        <!-- Краткое описание -->
                        <?php if ($arItem['PREVIEW_TEXT']): ?>
                            <div class="catalog-detail__preview-text">
                                <div class="text color_666 font_16">
                                    <?= $arItem['PREVIEW_TEXT'] ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Цена -->
                        <div class="catalog-detail__price-block" id="<?= $itemIds['PRICE'] ?>">
                            <?php if ($arItem['PRICE'] > 0): ?>
                                <div class="price-current">
                                    <span class="price font_28 font_weight--700 color-theme-target">
                                        <?= number_format($arItem['PRICE'], 0, '.', ' ') ?> ₽
                                    </span>
                                    <span class="price-unit font_14 color_666">за единицу</span>
                                </div>
                            <?php else: ?>
                                <div class="price-current">
                                    <span class="price font_28 font_weight--700 color-theme-target">
                                        Цена по договорённости
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Блок покупки -->  
                        <?php if ($arItem['CAN_BUY'] && $arItem['PRICE'] > 0): ?>
                            <div class="catalog-detail__buy-block">
                                <div class="buy-block-wrapper">
                                    
                                    <!-- Количество -->
                                    <div class="quantity-block">
                                        <div class="quantity-wrapper">
                                            <label class="quantity-label">
                                                Количество ТС для мониторинга:
                                            </label>
                                            <div class="quantity-controls">
                                                <button type="button" class="quantity-btn quantity-btn--minus" 
                                                        data-quantity-change="-1">−</button>
                                                        
                                                <input type="number" 
                                                       id="<?= $itemIds['QUANTITY'] ?>"
                                                       class="quantity-field" 
                                                       name="quantity" 
                                                       value="1" 
                                                       min="1" 
                                                       max="<?= $arItem['CATALOG_QUANTITY'] > 0 ? $arItem['CATALOG_QUANTITY'] : 999 ?>"
                                                       data-entity="basket-item-quantity-field">
                                                       
                                                <button type="button" class="quantity-btn quantity-btn--plus" 
                                                        data-quantity-change="1">+</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Итоговая стоимость -->
                                        <div class="total-cost">
                                            <div class="total-label">Итого:</div>
                                            <div class="total-price font_24 font_weight--700 color-theme-target">
                                                <span class="total-amount" data-total-price="<?= $arItem['PRICE'] ?>">
                                                    <?= number_format($arItem['PRICE'], 0, '.', ' ') ?> ₽
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Кнопки действий -->
                                    <div class="actions-block">
                                        
                                        <!-- Кнопка "В корзину" -->
                                        <div class="item-action--basket">
                                            <button class="btn btn-lg btn-wide btn-default js-item-action"
                                                    id="<?= $itemIds['BASKET'] ?>"
                                                    data-action="basket"
                                                    data-id="<?= $arItem['ID'] ?>"
                                                    data-quantity="1"
                                                    data-item='<?= htmlspecialchars(json_encode($basketData), ENT_QUOTES) ?>'>
                                                <span class="to_cart">
                                                    <?= Loc::getMessage('TARIFFS_ADD_TO_BASKET') ?>
                                                </span>
                                            </button>
                                            
                                            <div class="in_cart added2cart_block" style="display: none;">
                                                <a href="<?= SITE_DIR ?>basket/" class="btn btn-lg btn-wide btn-default">
                                                    <?= Loc::getMessage('TARIFFS_IN_BASKET') ?>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Кнопка "Купить в 1 клик" (если включена) -->
                                        <?php if ($arParams['SHOW_ONE_CLICK_BUY'] === 'Y'): ?>
                                            <div class="item-action--oneclick">
                                                <button class="btn btn-lg btn-wide btn-transparent-border js-oneclick-buy"
                                                        data-id="<?= $arItem['ID'] ?>"
                                                        data-name="<?= htmlspecialchars($arItem['NAME']) ?>"
                                                        data-price="<?= $arItem['PRICE'] ?>">
                                                    <?= Loc::getMessage('TARIFFS_ONE_CLICK_BUY') ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        
                                    </div>
                                    
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                
            </div>
            
            <!-- Подробная информация -->
            <div class="catalog-detail__tabs-wrapper">
                <div class="tabs-wrapper">
                    
                    <!-- Навигация табов -->
                    <ul class="nav nav-tabs catalog-detail__tabs" role="tablist">
                        
                        <?php if ($arItem['DETAIL_TEXT']): ?>
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#description" role="tab">
                                    <?= Loc::getMessage('TARIFFS_TAB_DESCRIPTION') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php if (!$arItem['DETAIL_TEXT']): ?>active<?php endif; ?>" 
                               data-toggle="tab" href="#characteristics" role="tab">
                                <?= Loc::getMessage('TARIFFS_TAB_CHARACTERISTICS') ?>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#costs" role="tab">
                                <?= Loc::getMessage('TARIFFS_TAB_COSTS') ?>
                            </a>
                        </li>
                        
                        <?php if ($arItem['PACKAGE_DESCRIPTION']): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#package" role="tab">
                                    <?= Loc::getMessage('TARIFFS_TAB_PACKAGE') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                    </ul>
                    
                    <!-- Содержимое табов -->
                    <div class="tab-content catalog-detail__tab-content">
                        
                        <!-- Описание -->
                        <?php if ($arItem['DETAIL_TEXT']): ?>
                            <div class="tab-pane fade show active" id="description" role="tabpanel">
                                <div class="catalog-detail__description">
                                    <div class="text">
                                        <?= $arItem['DETAIL_TEXT'] ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Характеристики -->
                        <div class="tab-pane fade <?php if (!$arItem['DETAIL_TEXT']): ?>show active<?php endif; ?>" 
                             id="characteristics" role="tabpanel">
                            <div class="catalog-detail__characteristics">
                                <div class="characteristics-table">
                                    <table class="table table-striped">
                                        <tbody>
                                            
                                            <?php if ($arItem['CONNECTION_TYPE']): ?>
                                                <tr>
                                                    <td class="char-name">Тип подключения</td>
                                                    <td class="char-value"><?= htmlspecialchars($arItem['CONNECTION_TYPE']) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                            <tr>
                                                <td class="char-name">Оборудование</td>
                                                <td class="char-value">
                                                    <?php if ($arItem['HAS_EQUIPMENT']): ?>
                                                        Да<?= $arItem['EQUIPMENT_TYPE'] ? ' (' . htmlspecialchars($arItem['EQUIPMENT_TYPE']) . ')' : '' ?>
                                                    <?php else: ?>
                                                        Нет
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            
                                            <tr>
                                                <td class="char-name">CAN-шина</td>
                                                <td class="char-value"><?= $arItem['HAS_CAN'] ? 'Да' : 'Нет' ?></td>
                                            </tr>
                                            
                                            <tr>
                                                <td class="char-name">Блокировка двигателя</td>
                                                <td class="char-value"><?= $arItem['HAS_ENGINE_BLOCK'] ? 'Да' : 'Нет' ?></td>
                                            </tr>
                                            
                                            <tr>
                                                <td class="char-name">Установка и настройка</td>
                                                <td class="char-value"><?= $arItem['HAS_INSTALLATION'] ? 'Да' : 'Нет' ?></td>
                                            </tr>
                                            
                                            <tr>
                                                <td class="char-name">Консультация</td>
                                                <td class="char-value"><?= $arItem['HAS_CONSULTATION'] ? 'Да' : 'Нет' ?></td>
                                            </tr>
                                            
                                            <?php if (!empty($arItem['TRACKER_FEATURES'])): ?>
                                                <tr>
                                                    <td class="char-name">Функции трекера</td>
                                                    <td class="char-value">
                                                        <?= implode(', ', array_map('htmlspecialchars', $arItem['TRACKER_FEATURES'])) ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Стоимость -->
                        <div class="tab-pane fade" id="costs" role="tabpanel">
                            <div class="catalog-detail__costs">
                                <div class="costs-table">
                                    <table class="table table-striped">
                                        <tbody>
                                            
                                            <?php if ($arItem['EQUIPMENT_COST'] > 0): ?>
                                                <tr>
                                                    <td class="cost-name">Стоимость оборудования</td>
                                                    <td class="cost-value">
                                                        <?= number_format($arItem['EQUIPMENT_COST'], 0, '.', ' ') ?> ₽
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                            <?php if ($arItem['INSTALLATION_COST'] > 0): ?>
                                                <tr>
                                                    <td class="cost-name">Стоимость установки</td>
                                                    <td class="cost-value">
                                                        <?= number_format($arItem['INSTALLATION_COST'], 0, '.', ' ') ?> ₽
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                            <?php if ($arItem['SUBSCRIPTION_COST'] > 0): ?>
                                                <tr>
                                                    <td class="cost-name">Абонентская плата в год</td>
                                                    <td class="cost-value">
                                                        <?= number_format($arItem['SUBSCRIPTION_COST'], 0, '.', ' ') ?> ₽
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                            <?php if ($arItem['TOTAL_COST_PER_UNIT'] > 0): ?>
                                                <tr class="table-active">
                                                    <td class="cost-name font_weight--600">Итого за единицу</td>
                                                    <td class="cost-value font_weight--600">
                                                        <?= number_format($arItem['TOTAL_COST_PER_UNIT'], 0, '.', ' ') ?> ₽
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Описание пакета -->
                        <?php if ($arItem['PACKAGE_DESCRIPTION']): ?>
                            <div class="tab-pane fade" id="package" role="tabpanel">
                                <div class="catalog-detail__package">
                                    <div class="package-content">
                                        <?= $arItem['PACKAGE_DESCRIPTION'] ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функция управления количеством
    function initQuantityControls() {
        const quantityField = document.getElementById('<?= $itemIds['QUANTITY'] ?>');
        const totalAmountElement = document.querySelector('.total-amount');
        const basketButton = document.getElementById('<?= $itemIds['BASKET'] ?>');
        const basePrice = <?= $arItem['PRICE'] ?>;
        
        if (!quantityField || !totalAmountElement) return;
        
        // Обработчики кнопок +/-
        document.querySelectorAll('.quantity-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const change = parseInt(this.dataset.quantityChange);
                const currentValue = parseInt(quantityField.value);
                const newValue = Math.max(1, currentValue + change);
                const maxValue = parseInt(quantityField.getAttribute('max')) || 999;
                
                if (newValue <= maxValue) {
                    quantityField.value = newValue;
                    updateTotal();
                    updateBasketButton();
                }
            });
        });
        
        // Обработчик прямого ввода
        quantityField.addEventListener('input', function() {
            const value = parseInt(this.value) || 1;
            const maxValue = parseInt(this.getAttribute('max')) || 999;
            
            if (value < 1) {
                this.value = 1;
            } else if (value > maxValue) {
                this.value = maxValue;
            }
            
            updateTotal();
            updateBasketButton();
        });
        
        // Обновление итоговой суммы
        function updateTotal() {
            const quantity = parseInt(quantityField.value) || 1;
            const total = basePrice * quantity;
            totalAmountElement.textContent = total.toLocaleString('ru-RU') + ' ₽';
        }
        
        // Обновление данных кнопки корзины
        function updateBasketButton() {
            if (basketButton) {
                const quantity = parseInt(quantityField.value) || 1;
                basketButton.setAttribute('data-quantity', quantity);
                
                // Обновляем data-item
                const itemData = JSON.parse(basketButton.getAttribute('data-item'));
                itemData.QUANTITY = quantity;
                basketButton.setAttribute('data-item', JSON.stringify(itemData));
            }
        }
    }
    
    // Инициализация системы Aspro
    function waitForAspro(callback) {
        let attempts = 0;
        const maxAttempts = 50;
        
        function check() {
            attempts++;
            if (typeof BX !== 'undefined' && 
                typeof JItemAction !== 'undefined' &&
                BX.ready) {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            }
        }
        
        check();
    }
    
    // Инициализация всех систем
    initQuantityControls();
    
    waitForAspro(function() {
        BX.ready(function() {
            // Инициализируем кнопки действий
            const actionButtons = document.querySelectorAll('.js-item-action');
            actionButtons.forEach(function(button) {
                if (!button.itemAction) {
                    try {
                        JItemAction.factory(button);
                    } catch (e) {
                        console.warn('Failed to initialize action button:', e);
                    }
                }
            });
            
            // Обновляем состояния
            if (typeof JItemAction !== 'undefined') {
                try {
                    JItemAction.markItems();
                    JItemAction.markBadges();
                } catch (e) {
                    console.warn('Failed to update item states:', e);
                }
            }
        });
    });
});
</script>