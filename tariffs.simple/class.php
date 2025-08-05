<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Data\Cache;
use Bitrix\Catalog\PriceTable;
use Bitrix\Main\SystemException;

class MonitoringTariffsSimple extends CBitrixComponent
{
    /**
     * Обработка параметров компонента
     */
    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 0);
        $arParams['PRICE_CODE'] = (int)($arParams['PRICE_CODE'] ?? 1);
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);
        $arParams['SEF_MODE'] = ($arParams['SEF_MODE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
        $arParams['SEF_FOLDER'] = rtrim($arParams['SEF_FOLDER'] ?? '/tariffs/', '/') . '/';
        $arParams['SET_TITLE'] = ($arParams['SET_TITLE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
        $arParams['ADD_SECTIONS_CHAIN'] = ($arParams['ADD_SECTIONS_CHAIN'] ?? 'Y') === 'Y' ? 'Y' : 'N';
        $arParams['SET_STATUS_404'] = ($arParams['SET_STATUS_404'] ?? 'N') === 'Y' ? 'Y' : 'N';
        
        return $arParams;
    }

    /**
     * Проверка необходимых модулей
     */
    private function checkModules()
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException('Модуль iblock не установлен');
        }
        
        if (!Loader::includeModule('catalog')) {
            throw new SystemException('Модуль catalog не установлен');
        }
        
        return true;
    }

    /**
     * Основная логика компонента
     */
    public function executeComponent()
    {
        global $APPLICATION;
        
        try {
            // Проверяем модули
            $this->checkModules();
            
            // Проверяем параметры
            if ($this->arParams['IBLOCK_ID'] <= 0) {
                throw new SystemException('Не указан инфоблок');
            }

            $page = 'list';
            $arVariables = [];
            
            // Определяем текущую страницу
            if ($this->arParams['SEF_MODE'] === 'Y') {
                $engine = new CComponentEngine($this);
                
                $arUrlTemplates = [
                    'list' => '',
                    'detail' => '#ELEMENT_CODE#/',
                ];
                
                $page = $engine->guessComponentPath(
                    $this->arParams['SEF_FOLDER'],
                    $arUrlTemplates,
                    $arVariables
                );
                
                if ($page === 'detail' && !empty($arVariables['ELEMENT_CODE'])) {
                    $this->arResult['VARIABLES'] = $arVariables;
                } else {
                    $page = 'list';
                }
            }

            // Обработка страниц
            if ($page === 'detail') {
                $this->processDetailPage($arVariables['ELEMENT_CODE'] ?? '');
            } else {
                $this->processListPage();
            }

            // Подключаем шаблон
            $this->includeComponentTemplate($page);
            
        } catch (SystemException $e) {
            ShowError($e->getMessage());
        } catch (Exception $e) {
            ShowError('Ошибка выполнения компонента: ' . $e->getMessage());
        }
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

        // Получаем данные
        $this->arResult['ITEMS'] = $this->getItems();
        $this->arResult['ITEM'] = null;
    }

    /**
     * Обработка детальной страницы
     */
    private function processDetailPage($elementCode)
    {
        global $APPLICATION;
        
        $this->arResult['ITEMS'] = [];
        $this->arResult['ITEM'] = $this->getItem($elementCode);
        
        if (!$this->arResult['ITEM']) {
            if ($this->arParams['SET_STATUS_404'] === 'Y') {
                @define('ERROR_404', 'Y');
                CHTTP::SetStatus('404 Not Found');
            }
            
            // Показываем список
            $this->processListPage();
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
    }

    /**
     * Получение списка тарифов
     */
    private function getItems()
    {
        $cache = Cache::createInstance();
        $cacheId = 'monitoring_tariffs_list_' . $this->arParams['IBLOCK_ID'] . '_' . $this->arParams['PRICE_CODE'] . '_' . SITE_ID;
        
        if ($cache->initCache($this->arParams['CACHE_TIME'], $cacheId)) {
            return $cache->getVars();
        }

        $arSelect = [
            'ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 
            'DETAIL_TEXT', 'DETAIL_PICTURE', 'CATALOG_QUANTITY', 'ACTIVE',
            'PROPERTY_CONNECTION_TYPE', 'PROPERTY_HAS_EQUIPMENT', 'PROPERTY_EQUIPMENT_TYPE',
            'PROPERTY_TRACKER_FEATURES', 'PROPERTY_HAS_CAN', 'PROPERTY_HAS_ENGINE_BLOCK', 
            'PROPERTY_HAS_INSTALLATION', 'PROPERTY_EQUIPMENT_COST', 'PROPERTY_INSTALLATION_COST', 
            'PROPERTY_SUBSCRIPTION_COST', 'PROPERTY_TOTAL_COST_PER_UNIT', 'PROPERTY_VEHICLE_COUNT', 
            'PROPERTY_HAS_CONSULTATION', 'PROPERTY_PACKAGE_DESCRIPTION'
        ];
        
        $arFilter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 
            'ACTIVE' => 'Y'
        ];
        
        $items = [];
        $res = CIBlockElement::GetList(['SORT' => 'ASC', 'NAME' => 'ASC'], $arFilter, false, false, $arSelect);

        $cache->startDataCache();

        while ($ob = $res->GetNextElement()) {
            $f = $ob->GetFields();
            $p = $ob->GetProperties();

            $item = $this->formatItem($f, $p);
            if ($item) {
                $items[] = $item;
            }
        }

        $cache->endDataCache($items);
        return $items;
    }

    /**
     * Получение одного тарифа по коду
     */
    private function getItem($code)
    {
        $code = trim($code);
        if ($code === '') return null;

        $arFilter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 
            'ACTIVE' => 'Y', 
            '=CODE' => $code
        ];
        
        $arSelect = [
            'ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 
            'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'CATALOG_QUANTITY', 'ACTIVE',
            'PROPERTY_CONNECTION_TYPE', 'PROPERTY_HAS_EQUIPMENT', 'PROPERTY_EQUIPMENT_TYPE',
            'PROPERTY_TRACKER_FEATURES', 'PROPERTY_HAS_CAN', 'PROPERTY_HAS_ENGINE_BLOCK', 
            'PROPERTY_HAS_INSTALLATION', 'PROPERTY_EQUIPMENT_COST', 'PROPERTY_INSTALLATION_COST', 
            'PROPERTY_SUBSCRIPTION_COST', 'PROPERTY_TOTAL_COST_PER_UNIT', 'PROPERTY_VEHICLE_COUNT', 
            'PROPERTY_HAS_CONSULTATION', 'PROPERTY_PACKAGE_DESCRIPTION'
        ];

        $res = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        if ($ob = $res->GetNextElement()) {
            $f = $ob->GetFields();
            $p = $ob->GetProperties();
            return $this->formatItem($f, $p);
        }
        
        return null;
    }

    /**
     * Форматирование элемента
     */
    private function formatItem($fields, $properties)
    {
        // Получаем цену
        $price = 0;
        if ($this->arParams['PRICE_CODE']) {
            $priceRow = PriceTable::getList([
                'filter' => ['PRODUCT_ID' => $fields['ID'], 'CATALOG_GROUP_ID' => $this->arParams['PRICE_CODE']],
                'select' => ['PRICE']
            ])->fetch();
            if ($priceRow) {
                $price = (float)$priceRow['PRICE'];
            }
        }

        // Обработка свойств
        $connectionType = is_array($properties['CONNECTION_TYPE']['VALUE']) 
            ? $properties['CONNECTION_TYPE']['VALUE'][0] 
            : ($properties['CONNECTION_TYPE']['VALUE'] ?? '');

        $equipmentType = is_array($properties['EQUIPMENT_TYPE']['VALUE']) 
            ? $properties['EQUIPMENT_TYPE']['VALUE'][0] 
            : ($properties['EQUIPMENT_TYPE']['VALUE'] ?? '');

        $trackerFeatures = [];
        if (!empty($properties['TRACKER_FEATURES']['VALUE'])) {
            $trackerFeatures = is_array($properties['TRACKER_FEATURES']['VALUE']) 
                ? $properties['TRACKER_FEATURES']['VALUE'] 
                : [$properties['TRACKER_FEATURES']['VALUE']];
        }

        // Обработка HTML-текста пакета
        $packageDescription = '';
        if (!empty($properties['PACKAGE_DESCRIPTION']['~VALUE'])) {
            if (is_array($properties['PACKAGE_DESCRIPTION']['~VALUE'])) {
                $packageDescription = $properties['PACKAGE_DESCRIPTION']['~VALUE']['TEXT'] ?? '';
            } else {
                $packageDescription = $properties['PACKAGE_DESCRIPTION']['~VALUE'];
            }
        } elseif (!empty($properties['PACKAGE_DESCRIPTION']['VALUE'])) {
            $packageDescription = $properties['PACKAGE_DESCRIPTION']['VALUE'];
        }

        return [
            'ID' => (int)$fields['ID'],
            'IBLOCK_ID' => (int)$fields['IBLOCK_ID'],
            'NAME' => $fields['NAME'],
            'CODE' => $fields['CODE'],
            'PREVIEW_TEXT' => $fields['PREVIEW_TEXT'],
            'DETAIL_TEXT' => $fields['DETAIL_TEXT'],
            'PREVIEW_PIC' => CFile::GetPath($fields['PREVIEW_PICTURE']),
            'DETAIL_PIC' => CFile::GetPath($fields['DETAIL_PICTURE']),
            'PRICE' => $price,
            'CATALOG_QUANTITY' => (float)($fields['CATALOG_QUANTITY'] ?? 0),
            'DETAIL_PAGE_URL' => $this->arParams['SEF_FOLDER'] . $fields['CODE'] . '/',
            'CONNECTION_TYPE' => $connectionType,
            'HAS_EQUIPMENT' => ($properties['HAS_EQUIPMENT']['VALUE_XML_ID'] === 'yes'),
            'EQUIPMENT_TYPE' => $equipmentType,
            'TRACKER_FEATURES' => $trackerFeatures,
            'HAS_CAN' => ($properties['HAS_CAN']['VALUE_XML_ID'] === 'yes'),
            'HAS_ENGINE_BLOCK' => ($properties['HAS_ENGINE_BLOCK']['VALUE_XML_ID'] === 'yes'),
            'HAS_INSTALLATION' => ($properties['HAS_INSTALLATION']['VALUE_XML_ID'] === 'yes'),
            'EQUIPMENT_COST' => (float)($properties['EQUIPMENT_COST']['VALUE'] ?? 0),
            'INSTALLATION_COST' => (float)($properties['INSTALLATION_COST']['VALUE'] ?? 0),
            'SUBSCRIPTION_COST' => (float)($properties['SUBSCRIPTION_COST']['VALUE'] ?? 0),
            'TOTAL_COST_PER_UNIT' => (float)($properties['TOTAL_COST_PER_UNIT']['VALUE'] ?? 0),
            'VEHICLE_COUNT' => (int)($properties['VEHICLE_COUNT']['VALUE'] ?? 1),
            'HAS_CONSULTATION' => ($properties['HAS_CONSULTATION']['VALUE_XML_ID'] === 'yes'),
            'PACKAGE_DESCRIPTION' => $packageDescription,
        ];
    }
}