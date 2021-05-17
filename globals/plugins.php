<?php

/**
 * Plugins.php
 * 
 * This class handles mapping URL paths to the corresponding functions/methods
 * in the router table.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

function get_plugins() {
    $plugin_db = db_cursor("plugins");
}
