<?php
// require_once __ENV_ROOT__ . "/config/default_settings.php";
global $TEMPLATE_PATHS;
$TEMPLATE_PATHS = [
    __APP_ROOT__,
    __ENV_ROOT__,
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

global $ADDITIONAL_USER_FIELDS;
$ADDITIONAL_USER_FIELDS = [];

global $ROOT_STYLE;
$ROOT_STYLE = "";

/** @global TIME_TO_UPDATE determines if we need to rebuild our cached assets */
global $TIME_TO_UPDATE;
$TIME_TO_UPDATE = false;

$env_class_root = __ENV_ROOT__ . "/classes/";
global $CLASSES_DIR;
$CLASSES_DIR = [
    __APP_ROOT__ . "/Cobalt/Components/",
    __APP_ROOT__ . "/Components/",
    __APP_ROOT__ . "/classes",
    __APP_ROOT__ . "/private/classes/",
    __ENV_ROOT__ . "/Cobalt/Components/",
    __ENV_ROOT__ . "/Components/",
    $env_class_root,
];

$CRUDABLE_CONFIG_TRACKER = [];

/** @global ?UserSchema */
// global $session;
// $session = null;

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

global $EXPORTED_PUBLIC_VARS;
$EXPORTED_PUBLIC_VARS = [];

global $TEMPLATE_BINDINGS;
$TEMPLATE_BINDINGS = [
    "html_head_binding", "noscript_binding_after", "header_binding_before",
    "header_binding_middle", "header_binding_after", "main_content_binding_before",
    "main_content_binding_after", "footer_binding_before", "footer_binding_after"
];

global $USER_BAR_DETAILS;
$USER_BAR_DETAILS = [];
global $USER_BAR_CUSTOMS;
$USER_BAR_CUSTOMS = [];

/**
 * Append a value to a particular template binding
 * 
 * Valid bindings: html_head_binding, noscript_binding_after, header_binding_before, 
 * header_binding_middle, header_binding_after, main_content_binding_before, 
 * main_content_binding_after, footer_binding_before, footer_binding_after
 * 
 * @param string $binding_name the name of the binding
 * @param string $value the value to be bound
 * @return void
 */
function bind($binding_name, $value) {


    if (!in_array($binding_name, $GLOBALS['TEMPLATE_BINDINGS'])) throw new Exception("Invalid binding");

    if (!isset($GLOBALS['WEB_PROCESSOR_VARS'][$binding_name]))
        $GLOBALS['WEB_PROCESSOR_VARS'][$binding_name] = $value;
    else $GLOBALS['WEB_PROCESSOR_VARS'][$binding_name] .= $value;
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
const QUERY_PARAM_FILTER_NAME = "filter_name";
const QUERY_PARAM_FILTER_VALUE = "filter_value";
const QUERY_PARAM_SEARCH_FIELD_TOKEN = "@";
const QUERY_PARAM_SEARCH_VALUE_TOKEN = ":";
const QUERY_PARAM_SEARCH_DELIMITER_TOKEN = ",";
const QUERY_TYPE_CAST_LOOKUP = 0;
const QUERY_TYPE_CAST_OPTION = 1;
const QUERY_PARAM_SEARCH_CASE_SENSITVE = "case_sensitive";
const QUERY_PARAM_COMPARISON_STRENGTH = "strength";
const QUERY_PARAM_ARCHIVED_DISPLAY = "archived";
const QUERY_SEARCH_MATCH_SCORE_FIELD = "__score";

const MODEL_RESERVERED_FIELD__FIELDNAME = "fieldName";

const REGEXP_CASE_INSENSITIVE = "i";
const REGEXP_MULTILINE_START_END = "m";
const REGEXP_EXTENDED_IGNORE_WHITESPACE = "x";
const REGEXP_MATCH_NEW_LINE_SPACE_CHAR = "s";
const REGEXP_UNICODE_SUPPORT = "u";

const CRUDABLE_ARCHIVED_FIELD = '__archived';
const CRUDABLE_CONFIG_APIV1 = 0b0001;
const CRUDABLE_CONFIG_ADMIN = 0b0010;
const CRUDABLE_DELETEABLE = 0b00001;
const CRUDABLE_MULTIDESTROY_FIELD = "_ids";
// const CRUDABLE_

const CUSTOMIZATION_TYPE_TEXT = 'text';
const CUSTOMIZATION_TYPE_MARKDOWN = 'markdown';
const CUSTOMIZATION_TYPE_IMAGE = 'image';
const CUSTOMIZATION_TYPE_HREF = 'href';
const CUSTOMIZATION_TYPE_VIDEO = 'video';
const CUSTOMIZATION_TYPE_AUDIO = 'audio';
const CUSTOMIZATION_TYPE_COLOR = 'color';
const CUSTOMIZATION_TYPE_SERIES = 'series';

$DECLARED_CUSTOMIZATIONS = [];

const COBALT_PAGES_DEFAULT_COLLECTION = "CobaltPages";

// Authentication and Login Stuff
const SESSION_STAGE_STATE = "__auth_stage";
const SESSION_USER_ID = "__user_id";
const SESSION_STAY_LOGGED_IN = "__stay_logged_in";
const SESSION_TFA_STATE = "__tfa_state";
const SESSION_RESUME_PARAM = "resume";
const TFA_STATE_DISABLED = 0;
const TFA_STATE_ENABLED = 1;

const AUTH_STAGE_0_USER_ACCOUNT_VERIFIED = 99;
const AUTH_STAGE_0_USER_ACCOUNT_DISCOVERY = 0;
const AUTH_STAGE_1_USER_AUTHENTICATION = 1;
const AUTH_STAGE_2_USER_SECOND_STAGE_VERIFY = 2;

const DATETIME_LOCAL_FORMAT = "Y-m-d\TH:i";