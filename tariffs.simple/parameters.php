<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arIBlocks = array();
if (CModule::IncludeModule('iblock'))
{
    $res = CIBlock::GetList(array('NAME' => 'ASC'), array('ACTIVE' => 'Y'));
    while ($ib = $res->Fetch())
    {
        $arIBlocks[$ib['ID']] = '[' . $ib['ID'] . '] ' . $ib['NAME'];
    }
}

$arPrices = array();
if (CModule::IncludeModule('catalog'))
{
    $res = CCatalogGroup::GetList(array('SORT' => 'ASC'));
    while ($p = $res->Fetch())
    {
        $arPrices[$p['ID']] = '[' . $p['ID'] . '] ' . $p['NAME'];
    }
}

$arComponentParameters = array(
    'PARAMETERS' => array(
        'IBLOCK_ID' => array(
            'NAME' => 'Инфоблок тарифов',
            'TYPE' => 'LIST',
            'VALUES' => $arIBlocks,
            'DEFAULT' => '44',
            'REFRESH' => 'Y',
        ),
        'PRICE_CODE' => array(
            'NAME' => 'Тип цены',
            'TYPE' => 'LIST',
            'VALUES' => $arPrices,
            'DEFAULT' => '1',
        ),
        'SEF_MODE' => array(
            'NAME' => 'Включить ЧПУ',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ),
        'SEF_FOLDER' => array(
            'NAME' => 'Каталог ЧПУ',
            'TYPE' => 'STRING',
            'DEFAULT' => '/tariffs/',
        ),
        'SET_TITLE' => array(
            'NAME' => 'Устанавливать заголовок страницы',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ),
        'ADD_SECTIONS_CHAIN' => array(
            'NAME' => 'Включать раздел в цепочку навигации',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ),
        'CACHE_TIME' => array(
            'NAME' => 'Время кеширования (сек.)',
            'TYPE' => 'STRING',
            'DEFAULT' => '3600',
        ),
    ),
);