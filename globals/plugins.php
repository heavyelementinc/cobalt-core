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
    $GLOBALS['ACTIVE_PLUGINS'] = $plugin_manager->instantiate_active_plugins();

    $TEMPLATE_PATHS = [
        __APP_ROOT__ . "/templates/",
        __APP_ROOT__ . "/private/templates/",
    ];

    $PERMISSIONS = [];
    $SHARED_CONTENT = [];
    $PACKAGES = ['js' => [], 'css' => []];

    foreach ($GLOBALS['ACTIVE_PLUGINS'] as $i => $plugin) {
        array_push($TEMPLATE_PATHS, $plugin->register_templates());
        $PERMISSIONS = array_merge($PERMISSIONS, $plugin->register_permissions());
        array_push($GLOBALS['CLASSES_DIR'], $plugin->register_dependencies());
        array_push($SHARED_CONTENT, $plugin->register_shared_content_dir());
        $PACKAGES['js']  = array_merge($PACKAGES['js'],  $plugin->register_packages('js'));
        $PACKAGES['css'] = array_merge($PACKAGES['css'], $plugin->register_packages('css'));

        // Register variables
        add_vars([$i => $plugin->register_variables()]);
    }

    array_push($TEMPLATE_PATHS, __ENV_ROOT__ . "/templates/");
} catch (Exception $e) {
    die("Plugin error: " . $e->getMessage());
    exit;
}
