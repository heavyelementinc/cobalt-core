<?php
/**
 * env.php - The Cobalt Environment Bootstrapper
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * Defines Cobalt's constants as well as loads the settings file for the current
 * project (internally referred to as an APP). The cobalt-core directory can
 * serve many apps at once but will only ever execute a single app while
 * fulfilling a request.
 * 
 * These files will not do *anything* unless invoked from within the context of
 * an APP. Please create a new app using the CLI and configure your webserver to
 * point to the app's /public directory.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @copyright 2023 - Heavy Element, Inc.
 */

use Cobalt\UTMTracker\UTMHandler;

ob_start();
const DB_BENCHMARK = 'db_requests';
const DB_BENCH_READ = 'reads';
const DB_BENCH_WRITE = 'writes';
const DB_BENCH_START = 'start';
const DB_BENCH_END = 'end';
const DB_BENCH_DELTA = 'delta(ms)';
$GLOBALS['BENCHMARK_RESULTS'][DB_BENCHMARK] = [DB_BENCH_READ => 0, DB_BENCH_WRITE => 0];
$GLOBALS['BENCHMARK_RESULTS']['env_invoke'] = [DB_BENCH_START => microtime(true) * 1000];

require_once __DIR__ . "/globals/logs.php";
require_once __DIR__ . "/globals/global_exceptions.php";

/* ENV_ROOT defines the root of the core files (the dir this file resides in) */
define("__ENV_ROOT__", __DIR__);

require_once __ENV_ROOT__ . "/globals/locales/en_us.php";

// Establish our app root
$app_root = "";
// Go up one directory so we're not in the public space
if (!empty($_SERVER['DOCUMENT_ROOT'])) $app_root = $_SERVER['DOCUMENT_ROOT'] . "/../";
// Rely on the Cobalt CLI to mandate the path to our app
else if (key_exists("cli_app_root", $GLOBALS)) $app_root = $GLOBALS['cli_app_root'];
else if (key_exists("unit_test", $GLOBALS)) $app_root = $GLOBALS['unit_test'];
else {
    header(INTERNAL_SERVER_ERROR);
    kill("Cannot establish absolute path to app root"); // Die.
}

define("__APP_ROOT__", realpath($app_root));
$app_locale = __APP_ROOT__ . "/locales/en_us.php";
if(file_exists($app_locale)) require_once $app_locale;
define("__PLG_ROOT__", __APP_ROOT__ . "/plugins");

// Let's make sure our environment is configured properly.
require_once __DIR__ . "/globals/env_probe.php";

define("COBALT_LOG_PATH", __APP_ROOT__ . "/ignored/logs/" . date("Y-m-d-") . "cobalt.log");
define("COBALT_LOG_MESSAGE", 0);
define("COBALT_LOG_NOTICE", 1);
define("COBALT_LOG_WARNING", 2);
define("COBALT_LOG_ERROR", 3);
define("COBALT_LOG_EXCEPTION", 4);

function cobalt_log($source, $string, $level = COBALT_LOG_MESSAGE) {
    if($level < config()['log_level']) return;
    $levels = ['MESSAGE','NOTICE','WARNING','ERROR','EXCEPTION'];
    $date = date("c");
    $log_line = "[$date] [".$levels[$level]."] $source ". str_replace(["\r\n", "\r", "\n", PHP_EOL],"",$string).PHP_EOL;
    if(function_exists("say")) say($log_line);
    $logpath = pathinfo(COBALT_LOG_PATH, PATHINFO_DIRNAME);
    $logfile = COBALT_LOG_PATH;
    if(!is_dir($logpath)) mkdir($logpath, 0777, true);
    $resource = fopen($logfile, "a+");
    if(!$resource) return;
    fwrite($resource,$log_line);
    fclose($resource);
}

// Let's ensure that the ignored config directory exists
$ignored_config_dir = __APP_ROOT__ . "/ignored/config/";
if (!file_exists($ignored_config_dir)) mkdir($ignored_config_dir, 0777, true);

// Define a few values that we will use to handle writing output during an exception
$allowed_to_exit_on_exception = true;
$WRITE_TO_BUFFER_HANDLED = false;

require_once __DIR__ . "/globals/global_declarations.php";
require_once __DIR__ . "/globals/bootstrap.php";
// Let's import our exceptions and our helper functions:
require_once __DIR__ . "/globals/global_functions.php";
require_once __DIR__ . "/globals/global_csrf.php";
require_once __DIR__ . "/globals/global_template.php";

$app_env = __APP_ROOT__ . "/app_env.php";
if(file_exists($app_env)) require_once $app_env;

// $composer is defined in env_probe.php
if (!file_exists($composer)) kill("Dependencies have not been installed. Run `composer install` in the $__dependency_dir directory as your webserver user");

require_once $composer;

// And then define our own autoload function (specified in global_functions.php)
spl_autoload_register("cobalt_autoload", true);

// try {
//     // Load our ACTIVE plugins.
//     require_once __ENV_ROOT__ . "/globals/plugins.php";
// } catch (Exception $e) {
//     kill($e->getMessage());
// }

require_once __ENV_ROOT__ . "/globals/extensions.php";

try {
    //TODO: fix settings cache so it doesn't need to bootstrap every time!
    $application = new \Cobalt\Settings\Settings(COBALT_BOOSTRAP_ALWAYS);//config()['bootstrap_mode'] ?? COBALT_BOOSTRAP_AS_NEEDED);
    /** @global $app How we set up and process our settings */
    $app = $application;
} catch (Exception $e) {
    cobalt_log("Settings", $e->getMessage(), COBALT_LOG_EXCEPTION);
    kill($e->getMessage());
} catch (Error $e) {
    cobalt_log("Settings", $e->getMessage(), COBALT_LOG_ERROR);
    kill($e->getMessage());
}

// Let's find our trusted cobalt domain
$_SERVER['COBALT_TRUSTED_HOST'] = null;
if(in_array($_SERVER['HTTP_HOST'], $app->__settings->API_CORS_allowed_origins->getArrayCopy())) {
    $_SERVER['COBALT_TRUSTED_HOST'] = $_SERVER['HTTP_HOST'];
    $app->__settings->trusted_host = $_SERVER['COBALT_TRUSTED_HOST'];
}

/** @var array DEFAULT_DEFINTIONS */
define("__APP_SETTINGS__", $application->get_settings());
define("VERSION_HASH", substr(md5(__COBALT_VERSION . __APP_SETTINGS__['version']), 0, 12));

if(__APP_SETTINGS__['AI_prohibit_scraping_notice']) {
    header("X-Robots-Tag: noimageai");
    header("X-Robots-Tag: noai");
    header("tdm-reservation: 1");
    add_vars(['ai_scraping' => <<<HTML
    <meta name="CCBot" content="nofollow">
    <meta name="robots" content="noai, noimageai">
    <meta name="tdm-reservation" content="1">
    HTML]);
}

// if(__APP_SETTINGS__['Forbid_AI_webcrawler_access']) {
//     $useragents = get_json(__ENV_ROOT__."/config/robots.json");
//     foreach($useragents as $name => $details) {
//         if(in_array($name, ['facebookexternalhit'])) continue;
//         if(preg_match("/$name/", $_SERVER['HTTP_USER_AGENT']) == false) continue;
//         header("HTTP/1.1 403 Forbidden");
//         header("Content-Type: text/plain");
//         print("Forbidden.\n");
//         exit;
//     }
// }

session_name("COBALTID");
$cobalt_session_started = session_start([
    'cookie_lifetime' => app('Auth_session_days_until_expiration') * 24 * 60 * 60,
    // 'cookie_httponly' => !__APP_SETTINGS__['require_https_login_and_cookie'],
    // 'cookie_secure'   => !__APP_SETTINGS__['require_https_login_and_cookie']
]);
$utm_manager = new UTMHandler();
$utm_details = $utm_manager->parseUTM($_GET);
if($utm_details) {
    $utm_manager->storeUTM($utm_details, true);
    exit;
}
// Let's check to see that we have a CSRF token created
if($_SESSION[CSRF_TOKEN_KEY] === null) csrf_generate_token();
// Ensure we have a fresh CSRF token for this session!
csrf_get_token();

// $_SESSION['timezone'] = apache_request_headers()['X-Timezone'];

@$tz= @timezone_open($_SESSION['timezone'] ?? config()['timezone']);
if($tz) $tz_set_result = date_default_timezone_set($_SESSION['timezone'] ?? config()['timezone']);

if(!key_exists("cli_app_root", $GLOBALS) && $cobalt_session_started === false && app('Auth_logins_enabled')) kill("Something went wrong creating a session. Do you have cookies disabled? They're required for this app.");

$depends = __APP_SETTINGS__['cobalt_version'] ?? __COBALT_VERSION;
if (!version_compare($depends, __COBALT_VERSION, ">=")) kill("This app depends on version $depends of Cobalt Engine. Please upgrade.");

ob_end_clean(); // Prevent any dependencies from polluting our output

/** If we're NOT in a CLI environment, we should import the context processor */
if (!defined("__CLI_ROOT__")) require_once __ENV_ROOT__ . "/globals/context.php";
