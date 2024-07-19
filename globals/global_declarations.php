<?php

global $TEMPLATE_PATHS;
$TEMPLATE_PATHS = [
    __APP_ROOT__ . "/templates/",
    __APP_ROOT__ . "/private/templates/",
];

global $TEMPLATE_CACHE;
$TEMPLATE_CACHE = [
    // __APP_ROOT__ . "/templates/",
    // __APP_ROOT__ . "/private/templates/",
    // __ENV_ROOT__ . "/templates/",
];

global $PERMISSIONS;
$PERMISSIONS = [];

global $SHARED_CONTENT;
$SHARED_CONTENT = [];

global $PACKAGES;
$PACKAGES = ['js' => [], 'css' => []];

global $ROUTE_LOOKUP_CACHE;
$ROUTE_LOOKUP_CACHE = [];

global $PUBLIC_SETTINGS;
$PUBLIC_SETTINGS = [];

global $ROOT_STYLE;
$ROOT_STYLE = "";

/** @global TIME_TO_UPDATE determines if we need to rebuild our cached assets */
global $TIME_TO_UPDATE;
$TIME_TO_UPDATE = false;

$env_class_root = __ENV_ROOT__ . "/classes/";
global $CLASSES_DIR;
$CLASSES_DIR = [
    __APP_ROOT__ . "/classes",
    __APP_ROOT__ . "/private/classes/",
    $env_class_root,
];

$CRUDABLE_CONFIG_TRACKER = [];

/** @global ?UserSchema */
global $session;
$session = null;

global $WEB_PROCESSOR_VARS;
$WEB_PROCESSOR_VARS = [];

function define_public_js_setting($name, $value) {
    global $PUBLIC_SETTINGS;
    $PUBLIC_SETTINGS[$name] = $value;
}

$ROUTE_GROUPS = [];

function getRouteGroups() {
    global $ROUTE_GROUPS;
    return $ROUTE_GROUPS;
}

const REQUEST_ENCODE_JSON = 1;
const REQUEST_ENCODE_FORM = 2;
const REQUEST_ENCODE_XML = 4;
const REQUEST_ENCODE_MULTIPART_FORM = 8;
const REQUEST_ENCODE_OCTET = 16;
const REQUEST_ENCODE_PLAINTEXT = 32;

const QUERY_PARAM_SORT_NAME = 'sort_field';
const QUERY_PARAM_SORT_DIR  = 'direction';
const QUERY_PARAM_LIMIT = 'limit';
const QUERY_PARAM_PAGE_NUM = 'page';
const QUERY_PARAM_SEARCH = 'query';

const CRUDABLE_CONFIG_APIV1 = 0b0001;
const CRUDABLE_CONFIG_ADMIN = 0b0010;

const CRUDABLE_DELETEABLE = 0b00001;

const CRUDABLE_MULTIDESTROY_FIELD = "_ids";
// const CRUDABLE_