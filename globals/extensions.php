<?php
use Cobalt\Extensions\Extensions;
try {
    $EXTENSION_MANAGER = new Extensions();
    $EXTENSION_MANAGER->initialize_active_extensions();

    Extensions::invoke("register_templates_dir", $TEMPLATE_PATHS);
    $TEMPLATE_PATHS[] = __ENV_ROOT__ . "/templates/";
    
    Extensions::invoke("register_classes_dir", $CLASSES_DIR);
    $key = array_search($env_class_root, $CLASSES_DIR);
    $CLASSES_DIR[] = $env_class_root;
    unset($CLASSES_DIR[$key]);

    Extensions::invoke("register_permissions", $PERMISSIONS);

    Extensions::invoke("register_shared_content_dir", $SHARED_CONTENT);
    // $EXTENSION_MANAGER::extension_call("register_classes", $TEMPLATE_PATHS);
} catch (Exception $e) {
    die("EXTENSION ERROR: " . $e->getMessage());
}

function extensions():\Cobalt\Extensions\Extensions {
    global $EXTENSION_MANAGER;
    return $EXTENSION_MANAGER;
}
