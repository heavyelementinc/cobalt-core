<?php
$TEMPLATE_PATHS = [
    __APP_ROOT__ . "/private/templates/",
];

$TEMPLATE_CACHE = [];

$ROUTE_LOOKUP_CACHE = [];

$PUBLIC_SETTINGS = [];
$ROOT_STYLE = "";


/** @global TIME_TO_UPDATE determines if we need to rebuild our cached assets */
$TIME_TO_UPDATE = false;

$GLOBALS['CLASSES_DIR'] = [
    __APP_ROOT__ . "/classes",
    __APP_ROOT__ . "/private/classes/",
    __ENV_ROOT__ . "/classes/"
];
