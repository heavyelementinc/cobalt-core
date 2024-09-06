<?php
use Cobalt\Extensions\Extensions;
try {
    define("EXTENSION_MANAGER", new Extensions());
} catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    kill("No database connection available. Check your config.php file.");
}
try {
    EXTENSION_MANAGER->initialize_active_extensions();

    Extensions::invoke("register_templates_dir", $TEMPLATE_PATHS);
    $TEMPLATE_PATHS[] = __ENV_ROOT__ . "/templates/";
    
    Extensions::invoke("register_classes_dir", $CLASSES_DIR);
    $key = array_search($env_class_root, $CLASSES_DIR);
    $CLASSES_DIR[] = $env_class_root;
    unset($CLASSES_DIR[$key]);

    Extensions::invoke("register_permissions", $PERMISSIONS);

    Extensions::invoke("register_shared_dir", $SHARED_CONTENT);

    Extensions::invoke("register_user_fields", $ADDITIONAL_USER_FIELDS);
} catch (Exception $e) {
    kill("EXTENSION ERROR: " . $e->getMessage());
}

/**
 * Returns the global extension manager which can be accessed:
 *   extensions()::invoke("some_method", $SOME_VALUE);
 * @return Extensions 
 */
function extensions():\Cobalt\Extensions\Extensions {
    // if(!$EXTENSION_MANAGER) return new Extensions();
    return EXTENSION_MANAGER;
}

function invoke(string $className, string $methodName):mixed {
    $extMan = extensions();
    return $extMan::invoke_one($className, $methodName, ...func_get_args());
}