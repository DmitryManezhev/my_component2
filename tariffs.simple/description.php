<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('ASPRO_TARIFFS_NAME'),
    'DESCRIPTION' => Loc::getMessage('ASPRO_TARIFFS_DESCRIPTION'),
    'ICON' => '/images/icon.gif',
    'SORT' => 500,
    'PATH' => [
        'ID' => 'aspro_lite_content',
        'CHILD' => [
            'ID' => 'catalog',
            'NAME' => Loc::getMessage('ASPRO_TARIFFS_PATH_CATALOG'),
            'SORT' => 300,
        ]
    ],
    'CACHE_PATH' => 'Y',
    'COMPLEX' => 'Y',
];