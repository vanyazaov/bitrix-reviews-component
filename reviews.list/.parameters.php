<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "COUNT" => [
            "PARENT" => "BASE",
            "NAME" => "Количество отзывов",
            "TYPE" => "STRING",
            "DEFAULT" => "10",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 300,
        ],
    ],
];
