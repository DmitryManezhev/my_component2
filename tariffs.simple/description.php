<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME'        => GetMessage('MONITORING_TARIFFS_NAME'),
    'DESCRIPTION' => GetMessage('MONITORING_TARIFFS_DESCRIPTION'),
    'SORT'        => 500,
    'PATH'        => [
        'ID'   => 'monitoring',
        'NAME' => GetMessage('MONITORING_TARIFFS_PATH_NAME'),
    ],
];