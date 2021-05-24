<?php

/**
 * Plugins.php
 * 
 * This file loads active plugins into memory.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */
try {
    $plugin_manager = new Plugins\Manager();
    $GLOBALS['ACTIVE_PLUGINS'] = $plugin_manager->get_active();

    $TEMPLATE_PATHS = [
        __APP_ROOT__ . "/private/templates/",
    ];

    $PERMISSIONS = [];

    $i = 0;
    foreach ($GLOBALS['ACTIVE_PLUGINS'] as $i => $plugin) {
        array_push($TEMPLATE_PATHS, $plugin->register_templates());
        $PERMISSIONS = array_merge($PERMISSIONS, $plugin->register_permissions());
        array_push($GLOBALS['CLASSES_DIR'], $plugin->register_dependencies());
    }
    $i += 1;

    $TEMPLATE_PATHS[$i] = __ENV_ROOT__ . "/templates/";
} catch (Exception $e) {
    if (app("debug")) die("Plugin error: " . $e->getMessage());
    else die("Error initializing plugins");
    exit;
}
