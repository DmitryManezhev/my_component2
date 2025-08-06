<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Iblock;

Loader::includeModule("iblock");

// Подключаем модуль "Каталог" для работы с ценами
if (Loader::includeModule("catalog")) {
    $arPrice = [];
    $res = \Bitrix\Catalog\GroupTable::getList([
        'select' => ['ID', 'NAME', 'NAME_LANG' => 'LANG.NAME'],
        'filter' => ['=BASE' => 'N']
    ]);
    while ($row = $res->fetch()) {
        $arPrice[$row['NAME']] = !empty($row['NAME_LANG']) ? $row['NAME_LANG'] : $row['NAME'];
    }

    // Добавляем базовый тип цены отдельно
    $resBase = \Bitrix\Catalog\GroupTable::getList([
        'select' => ['ID', 'NAME', 'NAME_LANG' => 'LANG.NAME'],
        'filter' => ['=BASE' => 'Y']
    ])->fetch();
    if ($resBase) {
        $arPrice[$resBase['NAME']] = !empty($resBase['NAME_LANG']) ? $resBase['NAME_LANG'] : $resBase['NAME'];
    }
} else {
    // Если модуль "Каталог" не подключен, используем базовый тип по умолчанию
    $arPrice = ['BASE' => 'Базовая'];
}


$arIBlockType = CIBlockParameters::GetIBlockTypes();

$arIBlock = [];
$res = CIBlock::GetList(['SORT' => 'ASC'], ['ACTIVE' => 'Y']);
while ($row = $res->Fetch()) {
    $arIBlock[$row["ID"]] = "[" . $row["ID"] . "] " . $row["NAME"];
}

// Получение свойств для выбранного инфоблока
$arProperty = [];
if (isset($arCurrentValues['IBLOCK_ID']) && (int)$arCurrentValues['IBLOCK_ID'] > 0) {
    $res = CIBlockProperty::GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC'],
        ['IBLOCK_ID' => (int)$arCurrentValues['IBLOCK_ID'], 'ACTIVE' => 'Y']
    );
    while ($row = $res->Fetch()) {
        if ($row['PROPERTY_TYPE'] != 'F') { // Исключаем свойства типа "Файл"
            $arProperty[$row['CODE']] = '[' . $row['CODE'] . '] ' . $row['NAME'];
        }
    }
}

$arComponentParameters = [
    "PARAMETERS" => [
        "IBLOCK_TYPE" => [
            "PARENT" => "BASE",
            "NAME" => "Тип инфоблока",
            "TYPE" => "LIST",
            "VALUES" => $arIBlockType,
            "DEFAULT" => "aspro_lite_catalog",
            "REFRESH" => "Y", // Оставляем, чтобы список IBLOCK_ID обновлялся
        ],
        "IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => "Инфоблок",
            "TYPE" => "LIST",
            "VALUES" => $arIBlock,
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y", // От этого поля зависит список свойств
        ],
        "PRICE_CODE" => [
            "PARENT" => "PRICES",
            "NAME" => "Тип цены",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arPrice,
            "DEFAULT" => ["BASE"]
        ],
        "PROPERTY_CODE" => [
            "PARENT" => "BASE",
            "NAME" => "Свойства элементов",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arProperty,
            "ADDITIONAL_VALUES" => "Y"
        ],

    ]
];
?>
