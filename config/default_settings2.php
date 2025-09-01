<?php

use Auth\UserCRUD;
use Cobalt\EventListings\Models\Event;
use Cobalt\Settings\Define\DefineSettings;
use Cobalt\Settings\Define\FieldTypes;
use Cobalt\Settings\Define\ValidatedTypes;
use PHPMailer\PHPMailer\PHPMailer;

const TEMPLATE_DEBUG_SHOW_TYPES   = 0b0001;
const TEMPLATE_DEBUG_RENDER_TYPES = 0b0010;
const GROUP_BASIC = "Basic";
const SUBGROUP_BASIC_GENERAL = "General";
const SUBGROUP_BASIC_DETAILS = "Details";
const GROUP_CACHE_DEBUG = "Cache &amp; Debug";
const GROUP_LOOK_FEEL = "Look &amp; Feel";
const GROUP_CONTACT = "Contact Form";
const GROUP_FEATURES = "Features";
const GROUP_SEO = "Search &amp; SEO";
const SUBGROUP_SEO_ROBOTS = "Search Engine";
const GROUP_SMTP = "Mail";
const SUBGROUP_SMTP_BASIC = "Basic";
const GROUP_PAGES = "Pages";
const SUBGROUP_PAGES_RSS = "RSS Settings";
const GROUP_POSTS = "Posts";
const SUBGROUP_PAGES_POSTS_GENERAL = "General";
const SUBGROUP_PAGES = "Pages";
const GROUP_DEV = "Developer";
const SUBGROUP_DEV_JS_PACKAGE = "JavaScript Packaging";
const SUBGROUP_DEV_CSS_PACKAGE = "CSS Packaging";

const FONT_BACKEND_GOOGLE = 0;
const FONT_BACKEND_FONTSOURCE = 1;

const COBALT_LOGIN_TYPE_LEGACY = 0;
const COBALT_LOGIN_TYPE_STAGES = 1;

const POSTS_INDEX_MODE_GRID = "0";
const POSTS_INDEX_MODE_FEED = "1";
const POSTS_INDEX_MODE_BODY = "2";
const POSTS_INDEX_MODE_LATEST = "3";

const CONTACT_SUCCESS_SYSTEM  = 0b000001;
const CONTACT_SUCCESS_NOTIFY  = 0b000010;
const CONTACT_SUCCESS_PUSH    = 0b000100;
const CONTACT_SUCCESS_EMAIL   = 0b001000;
// const CONTACT_SUCCESS_MESSAGE = 0b010000;

const CONTACT_CLIENT_SUCCESS_REDIRECT = 0b0001;
const CONTACT_CLIENT_SUCCESS_STATUS   = 0b0010;
const CONTACT_CLIENT_SUCCESS_STAGE    = 0b0100;

$settings = [
    DefineSettings::define('domain_name')
        ->set_default("")
        ->set_group(GROUP_BASIC)
        ->set_name("Domain Name")
        ->set_description("The domain name you use to access your Cobalt application.")
        ->set_field(FieldTypes::input)
        ->set_confirm("If you change this value, you may lose access to this page and will need to manually change the value to regain access.")
        ->set_filter(["FILTER_VALIDATE_URL"]),
    DefineSettings::define("canonical_name")
        ->set_default("default")
        ->set_group(GROUP_BASIC)
        ->set_subgroup(SUBGROUP_BASIC_GENERAL)
        ->set_name("Canonical Name")
        ->set_alias("domain_name")
        ->set_public_directive(true)
        ->set_description( "This is the host name that will be provided by the server_name() function. If it's not set, it will fall back to the `domain_name` value.")
        ->set_type("input")
        ->set_filter(["FILTER_VALIDATE_URL" => []]),
    DefineSettings::define("cobalt_base_path")
        ->set_default("default"),
    DefineSettings::define("app_name")
];