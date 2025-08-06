<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Context;
use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Basket;
use Bitrix\Main\Web\Json;

class AsproTariffsSimple extends CBitrixComponent
{
    protected $request;
    protected $componentPage;

    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 0);
        $arParams['PRICE_CODE'] = (array)($arParams['PRICE_CODE'] ?? []);
        $arParams['PROPERTY_CODE'] = array_filter((array)($arParams['PROPERTY_CODE'] ?? []));
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);
        $arParams['SEF_MODE'] = $arParams['SEF_MODE'] ?? 'Y';
        $arParams['SEF_FOLDER'] = rtrim($arParams['SEF_FOLDER'] ?? '/tariffs/', '/') . '/';
        $arParams['SET_TITLE'] = $arParams['SET_TITLE'] ?? 'Y';
        $arParams['ADD_SECTIONS_CHAIN'] = $arParams['ADD_SECTIONS_CHAIN'] ?? 'Y';
        $arParams['ELEMENT_COUNT'] = (int)($arParams['ELEMENT_COUNT'] ?? 10);
        return $arParams;
    }

    public function executeComponent()
    {
        global $APPLICATION;

        $this->request = Context::getCurrent()->getRequest();

        if (!Loader::includeModule("iblock") || !Loader::includeModule("catalog") || !Loader::includeModule("sale")) {
            ShowError("Необходимые модули не подключены.");
            return;
        }

        if ((int)$this->arParams['IBLOCK_ID'] <= 0) {
            ShowError("Не указан инфоблок.");
            return;
        }

        if ($this->request->get('action') === 'ADD_TO_BASKET') {
            if ($this->request->isAjaxRequest()) {
                try {
                    $this->addToBasket();
                    echo json_encode(['status' => 'success']);
                } catch (\Throwable $e) {
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                }
                die();
            } else {
                $this->addToBasket();
            }
        }

        $arUrlTemplates = [
            'list' => '',
            'detail' => '#ELEMENT_CODE#/',
        ];

        $arVariables = [];
        $this->componentPage = \CComponentEngine::ParseComponentPath(
            $this->arParams['SEF_FOLDER'],
            $arUrlTemplates,
            $arVariables
        );

        if (empty($this->componentPage)) {
            $this->componentPage = 'list';
        }

        $this->arResult['VARIABLES'] = $arVariables;

        if ($this->componentPage === 'detail') {
            $this->arResult['ITEM'] = $this->getItem($arVariables['ELEMENT_CODE']);
            if (!$this->arResult['ITEM']) {
                @define("ERROR_404", "Y");
                \CHTTP::SetStatus("404 Not Found");
                return;
            }
            $this->processDetailTitleAndChain();
        } else {
            $this->arResult['ITEMS'] = $this->getItems();
            $this->processListTitleAndChain();
        }

        $this->includeComponentTemplate($this->componentPage);
    }

    private function getItems()
    {
        $select = ['ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'];
        $select = array_merge($select, $this->getPropertiesList());

        $items = [];
        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ACTIVE' => 'Y'],
            false,
            ['nPageSize' => $this->arParams['ELEMENT_COUNT']],
            $select
        );

        while ($ob = $res->GetNextElement()) {
            $element = $ob->GetFields();
            $props = $ob->GetProperties();

            $element['PROPERTIES'] = $props;
            $element['PREVIEW_PIC'] = \CFile::GetPath($element['PREVIEW_PICTURE']);
            $element['DETAIL_PAGE_URL'] = $this->arParams['SEF_FOLDER'] . $element['CODE'] . '/';
            $element['PRICE'] = $this->getPrice($element['ID']);
            $element['CATALOG_QUANTITY'] = \CCatalogProduct::GetByID($element['ID'])['QUANTITY'];

            $items[] = $element;
        }

        return $items;
    }

    private function getItem($code)
    {
        $select = ['ID', 'NAME', 'CODE', 'DETAIL_TEXT', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'];
        $select = array_merge($select, $this->getPropertiesList());

        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'CODE' => $code, 'ACTIVE' => 'Y'],
            false,
            false,
            $select
        );

        if ($ob = $res->GetNextElement()) {
            $element = $ob->GetFields();
            $props = $ob->GetProperties();
            
            $element['PROPERTIES'] = $props;
            $element['PREVIEW_PIC'] = \CFile::GetPath($element['PREVIEW_PICTURE']);
            $element['DETAIL_PIC'] = \CFile::GetPath($element['DETAIL_PICTURE']);
            $element['DETAIL_PAGE_URL'] = $this->arParams['SEF_FOLDER'] . $element['CODE'] . '/';
            $element['PRICE'] = $this->getPrice($element['ID']);
            $element['CATALOG_QUANTITY'] = \CCatalogProduct::GetByID($element['ID'])['QUANTITY'];

            return $element;
        }

        return false;
    }

    private function getPropertiesList(): array
    {
        if (empty($this->arParams['PROPERTY_CODE'])) {
            return [];
        }

        return array_map(function ($code) {
            return 'PROPERTY_' . $code;
        }, $this->arParams['PROPERTY_CODE']);
    }

    private function getPrice($productId)
    {
        $priceCode = $this->arParams['PRICE_CODE'][0] ?? 'BASE';
        $group = GroupTable::getList([
            'filter' => ['=NAME' => $priceCode],
            'select' => ['ID']
        ])->fetch();

        if (!$group) {
            return 0;
        }

        $res = PriceTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productId,
                'CATALOG_GROUP_ID' => $group['ID']
            ],
            'select' => ['PRICE']
        ]);

        if ($priceRow = $res->fetch()) {
            return $priceRow['PRICE'];
        }

        return 0;
    }

    private function processListTitleAndChain()
    {
        global $APPLICATION;
        if ($this->arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle('Тарифы мониторинга');
        }
        if ($this->arParams['ADD_SECTIONS_CHAIN'] === 'Y') {
            $APPLICATION->AddChainItem('Тарифы');
        }
    }

    private function processDetailTitleAndChain()
    {
        global $APPLICATION;
        if ($this->arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle($this->arResult['ITEM']['NAME']);
        }
        if ($this->arParams['ADD_SECTIONS_CHAIN'] === 'Y') {
            $APPLICATION->AddChainItem('Тарифы', $this->arParams['SEF_FOLDER']);
            $APPLICATION->AddChainItem($this->arResult['ITEM']['NAME']);
        }
    }

    private function addToBasket()
    {
        $productId = (int)$this->request->get('PRODUCT_ID');
        $quantity = (int)$this->request->get('QUANTITY');

        if ($productId <= 0 || $quantity <= 0) {
            throw new SystemException('Неверные параметры');
        }

        $fuserId = Fuser::getId();
        $basket = Basket::loadItemsForFUser($fuserId, SITE_ID);
        $item = $basket->getExistsItem('catalog', $productId);

        if ($item) {
            $item->setField('QUANTITY', $item->getQuantity() + $quantity);
        } else {
            $item = $basket->createItem('catalog', $productId);
            $item->setFields([
                'QUANTITY' => $quantity,
                'CURRENCY' => 'RUB',
                'LID' => SITE_ID,
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
            ]);
        }

        $basket->save();
    }
}
