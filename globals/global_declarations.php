<?php

global $TEMPLATE_PATHS;
$TEMPLATE_PATHS = [
    __APP_ROOT__ . "/private/templates/",
];

global $TEMPLATE_CACHE;
$TEMPLATE_CACHE = [];

global $ROUTE_LOOKUP_CACHE;
$ROUTE_LOOKUP_CACHE = [];

global $PUBLIC_SETTINGS;
$PUBLIC_SETTINGS = [];

global $ROOT_STYLE;
$ROOT_STYLE = "";

/** @global TIME_TO_UPDATE determines if we need to rebuild our cached assets */
global $TIME_TO_UPDATE;
$TIME_TO_UPDATE = false;

global $CLASSES_DIR;
$CLASSES_DIR = [
    __APP_ROOT__ . "/classes",
    __APP_ROOT__ . "/private/classes/",
    __ENV_ROOT__ . "/classes/"
];

/** @global ?UserSchema */
global $session;
$session = null;

global $WEB_PROCESSOR_VARS;
$WEB_PROCESSOR_VARS = [];
