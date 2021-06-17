<?php

/**
 * Database UTCDateTime - Wraps calls to Mongo's DateTime
 * 
 * Meant to provide a way for other developers to build out an SQL backend for 
 * Cobalt Engine since SQL sucks and we currently only support Mongo.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Drivers;

class DateFailed extends \Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}
