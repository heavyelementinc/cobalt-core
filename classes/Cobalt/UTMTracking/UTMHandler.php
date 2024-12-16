<?php

namespace Cobalt\UTMTracker;

use Cobalt\UTMTracking\UTMDetails;

class UTMHandler {
    const SESSION_NAME = "__UTM_SESSION";

    function __construct() {
        $this->hydrate();
    }

    function hydrate() {
        global $UTMDetails;
        if(!$_SESSION[self::SESSION_NAME]) {
            $UTMDetails = null;
            return;
        }
        $UTMDetails = $this->parseUTM(json_decode($_SESSION[self::SESSION_NAME]));
        if($UTMDetails) $this->storeUTM($UTMDetails);
    }

    function parseUTM(array $params):?UTMDetails {
        $source   = key_exists("utm_source",$params);
        $medium   = key_exists("utm_medium",$params);
        $campaign = key_exists("utm_campaign",$params);
        if($source & $medium & $campaign) return new UTMDetails($params);
        return null;
    }

    function storeUTM(UTMDetails $details) {
        $_SESSION[self::SESSION_NAME] = json_encode($details);
    }

    static function read():UTMDetails {
        global $UTMDetails;
        return $UTMDetails;
    }
}