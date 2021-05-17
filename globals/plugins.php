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

$plugin_manager = new Plugins\Manager();
$ACTIVE_PLUGINS = $plugin_manager->get_active();
