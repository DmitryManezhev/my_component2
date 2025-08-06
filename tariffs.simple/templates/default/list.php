<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

// Проверяем наличие элементов
if (empty($arResult['ITEMS'])) {
    ?>
    <div class="no_goods catalog_block_view">
        <div class="no_products">
            <div class="wrap_text_empty">
                Тарифы не найдены
            </div>
        </div>
    </div>
    <?php
    return;
}

// Определяем количество в строке
$lineCount = (int)($arParams['LINE_ELEMENT_COUNT'] ?? 3);
?>

<div class="catalog-block tariffs-catalog">
    <div class="catalog-items">
        <div class="catalog_block_view">
            
            <!-- Список тарифов -->
            <div class="catalog-block__items">
                <div class="tariffs-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin: 40px 0;">
                     
                    <?php foreach ($arResult['ITEMS'] as $arItem): ?>
                        <?php
                        // Уникальный ID для элемента
                        $uniqueId = 'tariff_' . $arItem['ID'];
                        
                        // Подготавливаем данные для корзины
                        $basketData = [
                            'ID' => $arItem['ID'],
                            'IBLOCK_ID' => $arItem['IBLOCK_ID'],
                            'PRODUCT_ID' => $arItem['ID'],
                            'NAME' => $arItem['NAME'],
                            'PRICE' => $arItem['PRICE'],
                            'CURRENCY' => 'RUB',
                            'QUANTITY' => 1,
                            'AVAILABLE_QUANTITY' => $arItem['CATALOG_QUANTITY'],
                            'CAN_BUY' => $arItem['CAN_BUY'] ? 'Y' : 'N',
                        ];
                        ?>
                        
                        <div class="tariff-card" 
                             id="<?= $uniqueId ?>"
                             data-id="<?= $arItem['ID'] ?>"
                             data-item='<?= htmlspecialchars(json_encode($basketData), ENT_QUOTES) ?>'>
                             
                            <!-- Изображение -->
                            <?php if ($arItem['PREVIEW_PIC']): ?>
                                <img src="<?= $arItem['PREVIEW_PIC'] ?>" 
                                     alt="<?= htmlspecialchars($arItem['NAME']) ?>"
                                     class="tariff-card__img"
                                     loading="lazy">
                            <?php endif; ?>

                            <!-- Заголовок -->
                            <h3 class="tariff-card__title">
                                <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" 
                                   class="tariff-card__title-link">
                                    <?= $arItem['NAME'] ?>
                                </a>
                            </h3>
                            
                            <!-- Цена -->
                            <div class="tariff-card__price">
                                <?php if ($arItem['PRICE'] > 0): ?>
                                    <?= number_format($arItem['PRICE'], 0, '.', ' ') ?> ₽
                                <?php else: ?>
                                    Цена по договорённости
                                <?php endif; ?>
                            </div>

                            <!-- Превью текст -->
                            <?php if ($arItem['PREVIEW_TEXT']): ?>
                                <div class="tariff-card__text">
                                    <?= $arItem['PREVIEW_TEXT'] ?>
                                </div>
                            <?php endif; ?>

                            <!-- Характеристики тарифа -->
                            <div class="tariff-detail__props">
                                <?php if ($arItem['CONNECTION_TYPE']): ?>
                                    <p><strong>Тип подключения:</strong> <?= htmlspecialchars($arItem['CONNECTION_TYPE']) ?></p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['HAS_EQUIPMENT']): ?>
                                    <p><strong>Оборудование:</strong> 
                                        Да<?= $arItem['EQUIPMENT_TYPE'] ? ' (' . htmlspecialchars($arItem['EQUIPMENT_TYPE']) . ')' : '' ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['HAS_CAN']): ?>
                                    <p><strong>CAN-шина:</strong> Да</p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['HAS_ENGINE_BLOCK']): ?>
                                    <p><strong>Блокировка двигателя:</strong> Да</p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['HAS_INSTALLATION']): ?>
                                    <p><strong>Установка и настройка:</strong> Да</p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['HAS_CONSULTATION']): ?>
                                    <p><strong>Консультация:</strong> Да</p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['EQUIPMENT_COST'] > 0): ?>
                                    <p><strong>Оборудование (₽):</strong> <?= number_format($arItem['EQUIPMENT_COST'], 0, '.', ' ') ?></p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['INSTALLATION_COST'] > 0): ?>
                                    <p><strong>Установка (₽):</strong> <?= number_format($arItem['INSTALLATION_COST'], 0, '.', ' ') ?></p>
                                <?php endif; ?>
                                
                                <?php if ($arItem['SUBSCRIPTION_COST'] > 0): ?>
                                    <p><strong>Абонентская плата (₽/год):</strong> <?= number_format($arItem['SUBSCRIPTION_COST'], 0, '.', ' ') ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Нижний блок с кнопками -->
                            <div class="catalog-block__info-bottom">
                                <!-- Кнопка "В корзину" -->
                                <?php if ($arItem['CAN_BUY'] && $arItem['PRICE'] > 0): ?>
                                    <div class="line-block line-block--8">
                                        <div class="line-block__item flex-1">
                                            <button class="btn btn-default btn-sm catalog-wide-button js-item-action"
                                                    data-action="basket"
                                                    data-id="<?= $arItem['ID'] ?>"
                                                    data-quantity="1"
                                                    data-item='<?= htmlspecialchars(json_encode($basketData), ENT_QUOTES) ?>'>
                                                В корзину
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Кнопка "Подробнее" -->
                                <div class="line-block line-block--8 line-block--8-vertical flexbox--wrap flexbox--justify-center" style="margin-top: 15px;">
                                    <div class="line-block__item flex-1">
                                        <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" 
                                           class="btn btn-default btn-sm btn-wide btn-transparent-border">
                                            Подробнее
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                </div>
            </div>
            
            <!-- Навигация -->
            <?php if ($arResult['NAV_STRING']): ?>
                <div class="bottom-nav-wrapper">
                    <?= $arResult['NAV_STRING'] ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Подключаем CSS стили -->
<style>
.tariffs-catalog .tariff-card {
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    padding: 25px;
    background: #fff;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
}

.tariffs-catalog .tariff-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #007bff, #0056b3);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.tariffs-catalog .tariff-card:hover {
    box-shadow: 0 8px 32px rgba(0,123,255,0.15);
    transform: translateY(-3px);
}

.tariffs-catalog .tariff-card:hover::before {
    transform: scaleX(1);
}

.tariffs-catalog .tariff-card__img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}

.tariffs-catalog .tariff-card__title { 
    font-size: 24px; 
    margin: 0 0 10px;
    color: #333;
    font-weight: 600;
    line-height: 1.3;
}

.tariffs-catalog .tariff-card__title-link {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.tariffs-catalog .tariff-card__title-link:hover {
    color: #007bff;
    text-decoration: none;
}

.tariffs-catalog .tariff-card__price { 
    font-size: 28px; 
    font-weight: 700; 
    color: #007bff;
    margin-bottom: 15px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.tariffs-catalog .tariff-card__text { 
    font-size: 15px; 
    line-height: 1.6; 
    margin-bottom: 20px;
    color: #666;
}

.tariffs-catalog .tariff-detail__props {
    margin-bottom: 20px;
    font-size: 14px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    flex-grow: 1;
}

.tariffs-catalog .tariff-detail__props p {
    margin: 8px 0;
    line-height: 1.4;
}

.tariffs-catalog .tariff-detail__props strong {
    color: #333;
    font-weight: 600;
}

.tariffs-catalog .catalog-block__info-bottom {
    margin-top: auto;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.tariffs-catalog .catalog-wide-button {
    width: 100%;
    min-width: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
    .tariffs-list {
        grid-template-columns: 1fr !important;
        gap: 20px !important;
    }
    
    .tariff-card {
        padding: 20px;
    }
    
    .tariff-card__title {
        font-size: 20px;
    }
    
    .tariff-card__price {
        font-size: 24px;
    }
}
</style>

<!-- Инициализация корзины -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем кнопки корзины если есть JItemAction
    if (typeof JItemAction !== 'undefined') {
        const actionButtons = document.querySelectorAll('.js-item-action[data-action="basket"]');
        actionButtons.forEach(function(button) {
            if (!button.itemAction) {
                try {
                    JItemAction.factory(button);
                } catch (e) {
                    console.warn('Failed to initialize basket button:', e);
                }
            }
        });
    }
});
</script>