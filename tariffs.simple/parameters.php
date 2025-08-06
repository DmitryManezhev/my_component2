<?php
/**
 * ПОЛНАЯ ВЕРСИЯ COMPONENT.PHP - исправленная
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Main\Application;

if (!Loader::includeModule("iblock") || !Loader::includeModule("catalog")) {
    ShowError("Модули информационных блоков и каталога не установлены");
    return;
}

class MonitoringTariffsComponent extends CBitrixComponent
{
    protected $componentPage = '';
    
    /**
     * Подготовка параметров компонента
     */
    public function onPrepareComponentParams($params)
    {
        // Безопасная функция получения параметров Aspro
        $getAsproParam = function($paramName, $default = '') {
            // Сначала проверяем CLite (Aspro Lite)
            if (class_exists('CLite') && method_exists('CLite', 'GetFrontParametrValue')) {
                $value = CLite::GetFrontParametrValue($paramName);
                return $value ?: $default;
            }
            // Затем TSolution (другие версии Aspro)
            if (class_exists('TSolution') && method_exists('TSolution', 'GetFrontParametrValue')) {
                $value = TSolution::GetFrontParametrValue($paramName);
                return $value ?: $default;
            }
            return $default;
        };
        
        // Стандартные параметры с учетом Aspro
        $defaultParams = [
            // Источники данных
            "IBLOCK_TYPE" => "catalog",
            "IBLOCK_ID" => 44, // ПРАВИЛЬНЫЙ ID инфоблока
            "SECTION_ID" => 0,
            "SECTION_CODE" => "",
            "ELEMENT_CODE" => "",
            
            // Настройки отображения
            "ELEMENT_COUNT" => 20,
            "LINE_ELEMENT_COUNT" => 3,
            "PROPERTY_CODE" => [],
            "FIELD_CODE" => ["NAME", "PREVIEW_TEXT", "DETAIL_TEXT", "PREVIEW_PICTURE", "DETAIL_PICTURE"],
            
            // Цены через Aspro
            "PRICE_CODE" => $getAsproParam('PRICES_TYPE') ? 
                explode(',', $getAsproParam('PRICES_TYPE')) : ["BASE"],
            "USE_PRICE_COUNT" => "N",
            "SHOW_PRICE_COUNT" => 1,
            "PRICE_VAT_INCLUDE" => $getAsproParam('PRICE_VAT_INCLUDE', 'Y'),
            "CONVERT_CURRENCY" => $getAsproParam('CONVERT_CURRENCY', 'N'),
            "CURRENCY_ID" => $getAsproParam('CURRENCY_ID', 'RUB'),
            
            // Отображение через Aspro
            "SHOW_DISCOUNT_PERCENT" => $getAsproParam('SHOW_DISCOUNT_PERCENT', 'Y'),
            "SHOW_OLD_PRICE" => $getAsproParam('SHOW_OLD_PRICE', 'Y'),
            "SHOW_DISCOUNT_TIME" => $getAsproParam('SHOW_DISCOUNT_TIME', 'Y'),
            "SHOW_ONE_CLICK_BUY" => $getAsproParam('SHOW_ONE_CLICK_BUY', 'N'),
            "HIDE_NOT_AVAILABLE" => $getAsproParam('HIDE_NOT_AVAILABLE', 'N'),
            "USE_COMPARE" => $getAsproParam('CATALOG_COMPARE') == 'Y' ? 'Y' : 'N',
            
            // ЧПУ
            "SEF_MODE" => "Y",
            "SEF_FOLDER" => "/tariffs/",
            "SEF_URL_TEMPLATES" => [
                "list" => "index.php",
                "detail" => "#ELEMENT_CODE#/"
            ],
            
            // SEO
            "SET_TITLE" => "Y",
            "SET_LAST_MODIFIED" => "Y",
            "SET_META_KEYWORDS" => "Y",
            "SET_META_DESCRIPTION" => "Y",
            "SET_BROWSER_TITLE" => "Y",
            "ADD_SECTIONS_CHAIN" => "Y",
            "SET_STATUS_404" => "Y",
            
            // Кеширование
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => 3600,
            "CACHE_FILTER" => "Y",
            "CACHE_GROUPS" => "Y",
            
            // Системные параметры
            "ACTION_VARIABLE" => "action",
            "PRODUCT_ID_VARIABLE" => "id",
        ];

        // КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ: правильное объединение параметров
        $params = array_merge($defaultParams, $params);

        // Обработка SEF параметров
        if ($params["SEF_MODE"] == "Y") {
            $params["SEF_URL_TEMPLATES"] = array_merge([
                "list" => "index.php",
                "detail" => "#ELEMENT_CODE#/"
            ], (array)($params["SEF_URL_TEMPLATES"] ?? []));
        }

        // Приведение к массивам
        $params["PROPERTY_CODE"] = (array)($params["PROPERTY_CODE"] ?? []);
        $params["FIELD_CODE"] = (array)($params["FIELD_CODE"] ?? ["NAME", "PREVIEW_TEXT", "DETAIL_TEXT", "PREVIEW_PICTURE", "DETAIL_PICTURE"]);
        $params["PRICE_CODE"] = (array)($params["PRICE_CODE"] ?? ["BASE"]);

        return $params;
    }

    /**
     * Основная логика компонента
     */
    public function executeComponent()
    {
        try {
            global $APPLICATION;

            // Определяем страницу компонента
            $this->componentPage = $this->determineComponentPage();
            
            // Устанавливаем результат по умолчанию
            $this->arResult = [
                'ITEMS' => [],
                'ITEM' => null,
                'NAV_STRING' => '',
                'NAV_CACHED_DATA' => null,
                'NAV_RESULT' => null,
                'SECTION' => null,
                'ELEMENTS_COUNT' => 0,
                'SEF_FOLDER' => $this->arParams['SEF_FOLDER'],
                'URL_TEMPLATES' => $this->arParams['SEF_URL_TEMPLATES'],
                'VARIABLES' => []
            ];

            // Обрабатываем в зависимости от страницы
            if ($this->componentPage === 'detail') {
                $this->processDetailPage();
            } else {
                $this->processListPage();
            }

            // КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ: стандартное подключение шаблона  
            $this->includeComponentTemplate($this->componentPage);
            
        } catch (SystemException $e) {
            ShowError($e->getMessage());
        } catch (Exception $e) {
            ShowError('Ошибка выполнения компонента: ' . $e->getMessage());
        }
    }

    /**
     * Определение текущей страницы компонента
     */
    private function determineComponentPage()
    {
        if ($this->arParams["SEF_MODE"] == "Y") {
            $arDefaultUrlTemplates404 = [
                "list" => "index.php",
                "detail" => "#ELEMENT_CODE#/"
            ];

            $arDefaultVariableAliases404 = [];
            $arDefaultVariableAliases = [];
            $arComponentVariables = ["ELEMENT_CODE"];

            $arUrlTemplates = CComponentEngine::makeComponentUrlTemplates(
                $arDefaultUrlTemplates404, 
                $this->arParams["SEF_URL_TEMPLATES"]
            );

            $arVariableAliases = CComponentEngine::makeComponentVariableAliases(
                $arDefaultVariableAliases404, 
                $this->arParams["VARIABLE_ALIASES"] ?? []
            );

            $componentPage = CComponentEngine::parseComponentPath(
                $this->arParams["SEF_FOLDER"],
                $arUrlTemplates,
                $arVariables
            );

            if (!$componentPage) {
                $componentPage = "list";
            }

            $this->arResult['VARIABLES'] = $arVariables;
            $this->arResult['ALIASES'] = $arVariableAliases;
            
            CComponentEngine::initComponentVariables(
                $componentPage, 
                $arComponentVariables, 
                $arVariableAliases, 
                $arVariables
            );

            return $componentPage;
        }

        return "list";
    }

    /**
     * Обработка списка тарифов
     */
    private function processListPage()
    {
        global $APPLICATION;
        
        // SEO
        if ($this->arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle('Тарифы мониторинга');
        }

        if ($this->arParams['ADD_SECTIONS_CHAIN'] === 'Y') {
            $APPLICATION->AddChainItem('Тарифы');
        }

        if ($this->arParams['SET_META_KEYWORDS'] === 'Y') {
            $APPLICATION->SetPageProperty("keywords", "тарифы мониторинга, услуги слежения");
        }

        if ($this->arParams['SET_META_DESCRIPTION'] === 'Y') {
            $APPLICATION->SetPageProperty("description", "Выберите подходящий тариф для мониторинга транспорта");
        }

        // Получаем элементы
        $this->arResult['ITEMS'] = $this->getElements();
        $this->arResult['ELEMENTS_COUNT'] = count($this->arResult['ITEMS']);
    }

    /**
     * Обработка детальной страницы
     */
    private function processDetailPage()
    {
        global $APPLICATION;
        
        $elementCode = $this->arResult['VARIABLES']['ELEMENT_CODE'] ?? '';
        
        if (!$elementCode) {
            $this->set404();
            return;
        }

        $this->arResult['ITEM'] = $this->getElement($elementCode);
        
        if (!$this->arResult['ITEM']) {
            $this->set404();
            return;
        }

        // SEO для детальной страницы
        if ($this->arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle($this->arResult['ITEM']['NAME']);
        }
        
        if ($this->arParams['ADD_SECTIONS_CHAIN'] === 'Y') {
            $APPLICATION->AddChainItem('Тарифы', $this->arParams['SEF_FOLDER']);
            $APPLICATION->AddChainItem($this->arResult['ITEM']['NAME']);
        }

        if ($this->arParams['SET_META_KEYWORDS'] === 'Y') {
            $APPLICATION->SetPageProperty("keywords", $this->arResult['ITEM']['NAME'] . ", тариф мониторинга");
        }

        if ($this->arParams['SET_META_DESCRIPTION'] === 'Y') {
            $APPLICATION->SetPageProperty("description", strip_tags($this->arResult['ITEM']['PREVIEW_TEXT']));
        }

        if ($this->arParams['SET_BROWSER_TITLE'] === 'Y' && !empty($this->arResult['ITEM']['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'])) {
            $APPLICATION->SetPageProperty("title", $this->arResult['ITEM']['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']);
        }
    }

    /**
     * Установка 404 ошибки
     */
    private function set404()
    {
        if ($this->arParams['SET_STATUS_404'] === 'Y') {
            @define('ERROR_404', 'Y');
            CHTTP::SetStatus('404 Not Found');
        }
        
        // Возвращаемся к списку
        $this->processListPage();
        $this->componentPage = 'list';
    }

    /**
     * Получение списка элементов
     */
    private function getElements()
    {
        $arFilter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => 'Y',
        ];

        if ($this->arParams['HIDE_NOT_AVAILABLE'] === 'Y') {
            $arFilter['CATALOG_AVAILABLE'] = 'Y';
        }

        // Безопасное формирование arSelect
        $arSelect = [
            'ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT',
            'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DATE_CREATE', 'TIMESTAMP_X',
            'CATALOG_QUANTITY', 'CATALOG_AVAILABLE'
        ];

        // Добавляем FIELD_CODE
        if (is_array($this->arParams['FIELD_CODE']) && !empty($this->arParams['FIELD_CODE'])) {
            $arSelect = array_merge($arSelect, $this->arParams['FIELD_CODE']);
        }

        // Добавляем кастомные свойства
        $arSelect = array_merge($arSelect, [
            'PROPERTY_CONNECTION_TYPE', 'PROPERTY_EQUIPMENT_TYPE', 'PROPERTY_TRACKER_FEATURES',
            'PROPERTY_HAS_EQUIPMENT', 'PROPERTY_HAS_CAN', 'PROPERTY_HAS_ENGINE_BLOCK',
            'PROPERTY_HAS_INSTALLATION', 'PROPERTY_EQUIPMENT_COST', 'PROPERTY_INSTALLATION_COST',
            'PROPERTY_SUBSCRIPTION_COST', 'PROPERTY_TOTAL_COST_PER_UNIT', 'PROPERTY_VEHICLE_COUNT',
            'PROPERTY_HAS_CONSULTATION', 'PROPERTY_PACKAGE_DESCRIPTION'
        ]);

        // Убираем дубликаты
        $arSelect = array_unique($arSelect);

        $arNavParams = false;
        if ((int)$this->arParams['ELEMENT_COUNT'] > 0) {
            $arNavParams = [
                'nPageSize' => (int)$this->arParams['ELEMENT_COUNT'],
                'bDescPageNumbering' => false,
                'bShowAll' => false
            ];
        }

        $arSort = ['SORT' => 'ASC', 'NAME' => 'ASC'];

        $res = CIBlockElement::GetList($arSort, $arFilter, false, $arNavParams, $arSelect);
        
        // Сохраняем объект навигации
        $this->arResult['NAV_RESULT'] = $res;
        if (is_object($res) && method_exists($res, 'GetPageNavStringEx')) {
            $this->arResult['NAV_STRING'] = $res->GetPageNavStringEx(
                $navComponentObject,
                'Тарифы',
                'main'
            );
        }

        $arItems = [];
        while ($arItem = $res->GetNext()) {
            $arItems[] = $this->formatElement($arItem);
        }

        return $arItems;
    }

    /**
     * Получение одного элемента
     */
    private function getElement($elementCode)
    {
        $arFilter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'CODE' => $elementCode,
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => 'Y',
        ];

        // Безопасное формирование arSelect
        $arSelect = [
            'ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT',
            'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DATE_CREATE', 'TIMESTAMP_X',
            'CATALOG_QUANTITY', 'CATALOG_AVAILABLE'
        ];

        // Добавляем FIELD_CODE
        if (is_array($this->arParams['FIELD_CODE']) && !empty($this->arParams['FIELD_CODE'])) {
            $arSelect = array_merge($arSelect, $this->arParams['FIELD_CODE']);
        }

        // Добавляем кастомные свойства
        $arSelect = array_merge($arSelect, [
            'PROPERTY_CONNECTION_TYPE', 'PROPERTY_EQUIPMENT_TYPE', 'PROPERTY_TRACKER_FEATURES',
            'PROPERTY_HAS_EQUIPMENT', 'PROPERTY_HAS_CAN', 'PROPERTY_HAS_ENGINE_BLOCK',
            'PROPERTY_HAS_INSTALLATION', 'PROPERTY_EQUIPMENT_COST', 'PROPERTY_INSTALLATION_COST',
            'PROPERTY_SUBSCRIPTION_COST', 'PROPERTY_TOTAL_COST_PER_UNIT', 'PROPERTY_VEHICLE_COUNT',
            'PROPERTY_HAS_CONSULTATION', 'PROPERTY_PACKAGE_DESCRIPTION'
        ]);

        // Убираем дубликаты
        $arSelect = array_unique($arSelect);

        $res = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        
        if ($arItem = $res->GetNext()) {
            return $this->formatElement($arItem);
        }
        
        return null;
    }

    /**
     * Форматирование элемента
     */
    private function formatElement($arFields)
    {
        // Получаем цены
        $arPrices = [];
        $price = 0;
        $priceFormatted = '';
        
        if (!empty($this->arParams['PRICE_CODE']) && is_array($this->arParams['PRICE_CODE'])) {
            foreach ($this->arParams['PRICE_CODE'] as $priceCode) {
                $arPrice = CPrice::GetList(
                    [],
                    [
                        'PRODUCT_ID' => $arFields['ID'],
                        'CATALOG_GROUP_ID' => $priceCode,
                        'CAN_BUY' => 'Y'
                    ]
                )->Fetch();
                
                if ($arPrice) {
                    $arPrices[$priceCode] = $arPrice;
                    if (!$price) {
                        $price = (float)$arPrice['PRICE'];
                        $priceFormatted = CurrencyFormat($price, $arPrice['CURRENCY']);
                    }
                }
            }
        }

        // Обработка изображений
        $arImages = [];
        if ($arFields['PREVIEW_PICTURE']) {
            $arImages['PREVIEW'] = CFile::GetFileArray($arFields['PREVIEW_PICTURE']);
        }
        if ($arFields['DETAIL_PICTURE']) {
            $arImages['DETAIL'] = CFile::GetFileArray($arFields['DETAIL_PICTURE']);
        }

        // Обработка кастомных свойств
        $connectionType = $arFields['PROPERTY_CONNECTION_TYPE_VALUE'] ?? '';
        $equipmentType = $arFields['PROPERTY_EQUIPMENT_TYPE_VALUE'] ?? '';
        
        $trackerFeatures = [];
        if (!empty($arFields['PROPERTY_TRACKER_FEATURES_VALUE'])) {
            $trackerFeatures = is_array($arFields['PROPERTY_TRACKER_FEATURES_VALUE']) 
                ? $arFields['PROPERTY_TRACKER_FEATURES_VALUE'] 
                : [$arFields['PROPERTY_TRACKER_FEATURES_VALUE']];
        }

        // Обработка HTML-свойства
        $packageDescription = '';
        if (!empty($arFields['PROPERTY_PACKAGE_DESCRIPTION_VALUE'])) {
            if (is_array($arFields['PROPERTY_PACKAGE_DESCRIPTION_VALUE'])) {
                $packageDescription = $arFields['PROPERTY_PACKAGE_DESCRIPTION_VALUE']['TEXT'] ?? '';
            } else {
                $packageDescription = $arFields['PROPERTY_PACKAGE_DESCRIPTION_VALUE'];
            }
        }

        // Формируем элемент
        $arElement = [
            'ID' => (int)$arFields['ID'],
            'IBLOCK_ID' => (int)$arFields['IBLOCK_ID'],
            'NAME' => $arFields['NAME'],
            'CODE' => $arFields['CODE'],
            'PREVIEW_TEXT' => $arFields['PREVIEW_TEXT'],
            'DETAIL_TEXT' => $arFields['DETAIL_TEXT'],
            'DATE_CREATE' => $arFields['DATE_CREATE'],
            'TIMESTAMP_X' => $arFields['TIMESTAMP_X'],
            
            // Изображения
            'PREVIEW_PICTURE' => $arFields['PREVIEW_PICTURE'],
            'DETAIL_PICTURE' => $arFields['DETAIL_PICTURE'],
            'IMAGES' => $arImages,
            'PREVIEW_PIC' => $arImages['PREVIEW']['SRC'] ?? '',
            'DETAIL_PIC' => $arImages['DETAIL']['SRC'] ?? '',
            
            // Цены
            'PRICES' => $arPrices,
            'PRICE' => $price,
            'PRICE_FORMATTED' => $priceFormatted,
            
            // Каталог
            'CATALOG_QUANTITY' => (float)($arFields['CATALOG_QUANTITY'] ?? 0),
            'CATALOG_AVAILABLE' => $arFields['CATALOG_AVAILABLE'] ?? 'Y',
            'CAN_BUY' => ($arFields['CATALOG_AVAILABLE'] ?? 'Y') === 'Y',
            
            // URL
            'DETAIL_PAGE_URL' => $this->arParams['SEF_FOLDER'] . $arFields['CODE'] . '/',
            
            // Кастомные свойства (как в оригинале)
            'CONNECTION_TYPE' => $connectionType,
            'HAS_EQUIPMENT' => ($arFields['PROPERTY_HAS_EQUIPMENT_VALUE'] === 'Да'),
            'EQUIPMENT_TYPE' => $equipmentType,
            'TRACKER_FEATURES' => $trackerFeatures,
            'HAS_CAN' => ($arFields['PROPERTY_HAS_CAN_VALUE'] === 'Да'),
            'HAS_ENGINE_BLOCK' => ($arFields['PROPERTY_HAS_ENGINE_BLOCK_VALUE'] === 'Да'),
            'HAS_INSTALLATION' => ($arFields['PROPERTY_HAS_INSTALLATION_VALUE'] === 'Да'),
            'EQUIPMENT_COST' => (float)($arFields['PROPERTY_EQUIPMENT_COST_VALUE'] ?? 0),
            'INSTALLATION_COST' => (float)($arFields['PROPERTY_INSTALLATION_COST_VALUE'] ?? 0),
            'SUBSCRIPTION_COST' => (float)($arFields['PROPERTY_SUBSCRIPTION_COST_VALUE'] ?? 0),
            'TOTAL_COST_PER_UNIT' => (float)($arFields['PROPERTY_TOTAL_COST_PER_UNIT_VALUE'] ?? 0),
            'VEHICLE_COUNT' => (int)($arFields['PROPERTY_VEHICLE_COUNT_VALUE'] ?? 1),
            'HAS_CONSULTATION' => ($arFields['PROPERTY_HAS_CONSULTATION_VALUE'] === 'Да'),
            'PACKAGE_DESCRIPTION' => $packageDescription,
            
            // Свойства в стандартном формате
            'PROPERTIES' => [
                'CONNECTION_TYPE' => ['VALUE' => $connectionType],
                'EQUIPMENT_TYPE' => ['VALUE' => $equipmentType],
                'TRACKER_FEATURES' => ['VALUE' => $trackerFeatures],
                'HAS_EQUIPMENT' => ['VALUE' => $arFields['PROPERTY_HAS_EQUIPMENT_VALUE'] ?? ''],
                'HAS_CAN' => ['VALUE' => $arFields['PROPERTY_HAS_CAN_VALUE'] ?? ''],
                'HAS_ENGINE_BLOCK' => ['VALUE' => $arFields['PROPERTY_HAS_ENGINE_BLOCK_VALUE'] ?? ''],
                'HAS_INSTALLATION' => ['VALUE' => $arFields['PROPERTY_HAS_INSTALLATION_VALUE'] ?? ''],
                'EQUIPMENT_COST' => ['VALUE' => $arFields['PROPERTY_EQUIPMENT_COST_VALUE'] ?? 0],
                'INSTALLATION_COST' => ['VALUE' => $arFields['PROPERTY_INSTALLATION_COST_VALUE'] ?? 0],
                'SUBSCRIPTION_COST' => ['VALUE' => $arFields['PROPERTY_SUBSCRIPTION_COST_VALUE'] ?? 0],
                'TOTAL_COST_PER_UNIT' => ['VALUE' => $arFields['PROPERTY_TOTAL_COST_PER_UNIT_VALUE'] ?? 0],
                'VEHICLE_COUNT' => ['VALUE' => $arFields['PROPERTY_VEHICLE_COUNT_VALUE'] ?? 1],
                'HAS_CONSULTATION' => ['VALUE' => $arFields['PROPERTY_HAS_CONSULTATION_VALUE'] ?? ''],
                'PACKAGE_DESCRIPTION' => ['VALUE' => $packageDescription],
            ],
        ];

        // Безопасная интеграция с Aspro
        $this->integrateWithAspro($arElement);

        return $arElement;
    }

    /**
     * Безопасная интеграция с Aspro
     */
    private function integrateWithAspro(&$arElement)
    {
        // Пробуем CLite (Aspro Lite)
        if (class_exists('CLite') && method_exists('CLite', 'getDataItem')) {
            try {
                $arElement['ITEM_DATA'] = CLite::getDataItem($arElement);
            } catch (Exception $e) {
                $arElement['ITEM_DATA'] = $arElement;
            }
        }
        // Пробуем TSolution (другие версии Aspro)
        elseif (class_exists('TSolution') && method_exists('TSolution', 'getDataItem')) {
            try {
                $arElement['ITEM_DATA'] = TSolution::getDataItem($arElement);
            } catch (Exception $e) {
                $arElement['ITEM_DATA'] = $arElement;
            }
        }
        else {
            $arElement['ITEM_DATA'] = $arElement;
        }
        
        // Обработка свойств через Aspro
        if (class_exists('CLite') && method_exists('CLite', 'PrepareItemProps')) {
            try {
                $arElement['DISPLAY_PROPERTIES'] = CLite::PrepareItemProps($arElement['PROPERTIES']);
            } catch (Exception $e) {
                $arElement['DISPLAY_PROPERTIES'] = $arElement['PROPERTIES'];
            }
        }
        elseif (class_exists('TSolution') && method_exists('TSolution', 'PrepareItemProps')) {
            try {
                $arElement['DISPLAY_PROPERTIES'] = TSolution::PrepareItemProps($arElement['PROPERTIES']);
            } catch (Exception $e) {
                $arElement['DISPLAY_PROPERTIES'] = $arElement['PROPERTIES'];
            }
        }
        else {
            $arElement['DISPLAY_PROPERTIES'] = $arElement['PROPERTIES'];
        }
    }
}

// КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ: правильное создание экземпляра компонента
$obComponent = new MonitoringTariffsComponent();
$obComponent->arParams = $arParams;
$obComponent->executeComponent();