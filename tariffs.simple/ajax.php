<?php
use Bitrix\Main\Web\Json,
    Bitrix\Main\SystemException,
    Bitrix\Main\Loader;

// Отключаем статистику и права
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

// Подключаем Битрикс
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

// Заголовки JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$arResult = [
    'success' => true,
    'error' => '',
    'items' => [],
    'count' => 0,
    'title' => 'Корзина'
];

try {
    $request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
    $request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

    $action = $request->get('action');
    $state = $request->get('state');
    $productId = (int)$request->get('ID');
    $quantity = (float)$request->get('quantity');
    
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Проверяем сессию
    if (!check_bitrix_sessid()) {
        throw new SystemException('Invalid bitrix sessid');
    }
    
    // Проверяем параметры
    if ($action !== 'basket' || $productId <= 0) {
        throw new SystemException('Invalid items');
    }
    
    // Подключаем модули
    if (!CModule::IncludeModule('sale')) {
        throw new SystemException('Модуль sale не подключен');
    }
    
    if (!CModule::IncludeModule('catalog')) {
        throw new SystemException('Модуль catalog не подключен');
    }
    
    if (!CModule::IncludeModule('iblock')) {
        throw new SystemException('Модуль iblock не подключен');
    }
    
    // Проверяем товар
    $arElement = CIBlockElement::GetByID($productId)->Fetch();
    if (!$arElement || $arElement['ACTIVE'] !== 'Y') {
        throw new SystemException('Товар не найден');
    }
    
    // Подключаем решение и TSolution классы если доступны
    $bUseTSolution = false;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/vendor/php/solution.php')) {
        include_once($_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/vendor/php/solution.php');
        
        if (defined('VENDOR_MODULE_ID') && Loader::includeModule(VENDOR_MODULE_ID)) {
            if (class_exists('TSolution\\Itemaction\\Basket')) {
                $bUseTSolution = true;
            }
        }
    }
    
    if ($bUseTSolution) {
        // Используем TSolution\Itemaction\Basket
        if ($state) {
            $arPropsOptions = [];
            
            if (class_exists('TSolution') && TSolution::isSaleMode()) {
                $arPropsOptions['bAddProps'] = $request->get('add_props') === 'Y';
                $arPropsOptions['bPartProps'] = $request->get('part_props') === 'Y';
                $arPropsOptions['propsList'] = $request->get('props') ? json_decode($request->get('props')) : [];
                $arPropsOptions['skuTreeProps'] = $request->get('basket_props') ? $request->get('basket_props') : '';
                $arPropsOptions['propsValues'] = $request->get('prop') ? $request->get('prop') : [];
            }
            
            TSolution\Itemaction\Basket::addItem($productId, $quantity, $arPropsOptions);
        } else {
            TSolution\Itemaction\Basket::removeItem($productId);
        }
        
        $basketData = TSolution\Itemaction\Basket::getItems();
        $arResult['items'] = $basketData['BASKET'] ?? [];
        $arResult['count'] = count($arResult['items']);
        $arResult['title'] = TSolution\Itemaction\Basket::getTitle();
        
    } else {
        // Fallback к стандартной корзине Битрикс
        $userId = CSaleBasket::GetBasketUserID();
        
        if ($state) {
            // Добавляем/обновляем товар в корзине
            $dbBasket = CSaleBasket::GetList(
                array(),
                array(
                    'FUSER_ID' => $userId,
                    'LID' => SITE_ID,
                    'PRODUCT_ID' => $productId,
                    'ORDER_ID' => false
                )
            );
            
            if ($arBasket = $dbBasket->Fetch()) {
                // Обновляем количество существующего товара
                $updateResult = CSaleBasket::Update($arBasket['ID'], array(
                    'QUANTITY' => $quantity
                ));
                if (!$updateResult) {
                    throw new SystemException('Ошибка обновления количества в корзине');
                }
            } else {
                // Добавляем новый товар
                $arFields = array(
                    'PRODUCT_ID' => $productId,
                    'QUANTITY' => $quantity,
                    'LID' => SITE_ID,
                    'MODULE' => 'catalog',
                    'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                    'NAME' => $arElement['NAME'],
                    'CURRENCY' => CCurrency::GetBaseCurrency(),
                    'PRICE' => 0, // Цена установится автоматически через провайдер
                );
                
                $basketId = CSaleBasket::Add($arFields);
                if (!$basketId) {
                    $lastError = $GLOBALS['APPLICATION']->GetException();
                    $errorMessage = $lastError ? $lastError->GetString() : 'Неизвестная ошибка добавления';
                    throw new SystemException('Ошибка добавления в корзину: ' . $errorMessage);
                }
            }
            
        } else {
            // Удаляем товар из корзины
            $dbBasket = CSaleBasket::GetList(
                array(),
                array(
                    'FUSER_ID' => $userId,
                    'LID' => SITE_ID,
                    'PRODUCT_ID' => $productId,
                    'ORDER_ID' => false
                )
            );
            
            while ($arBasket = $dbBasket->Fetch()) {
                CSaleBasket::Delete($arBasket['ID']);
            }
        }
        
        // Получаем актуальное состояние корзины
        $basketItems = array();
        $totalCount = 0;
        
        $dbBasket = CSaleBasket::GetList(
            array(),
            array(
                'FUSER_ID' => $userId,
                'LID' => SITE_ID,
                'ORDER_ID' => false
            )
        );

        while ($arBasket = $dbBasket->Fetch()) {
            $basketItems[$arBasket['PRODUCT_ID']] = (float)$arBasket['QUANTITY'];
            $totalCount += (float)$arBasket['QUANTITY'];
        }
        
        // Формируем результат
        $arResult['items'] = $basketItems;
        $arResult['count'] = $totalCount;
        $arResult['title'] = $totalCount > 0 ? 'Корзина (' . $totalCount . ')' : 'Корзина';
    }
    
} catch (SystemException $e) {
    $arResult['success'] = false;
    $arResult['error'] = $e->getMessage();
} catch (Exception $e) {
    $arResult['success'] = false;
    $arResult['error'] = $e->getMessage();
}

// Выводим JSON в том же формате что и стандартный item.php
die(Json::encode($arResult));