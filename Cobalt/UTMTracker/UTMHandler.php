<?php

namespace Cobalt\UTMTracker;

use Cobalt\Model\Traits\Accessible;
use Cobalt\UTMTracker\UTMDetails;

class UTMHandler {
    use Accessible;

    const SESSION_NAME = "__UTM_SESSION";

    function __construct() {
        $this->hydrate();
    }

    function getCollectionName($string = null):string {
        return "CobaltAnalytics";
    }

    function hydrate() {
        global $UTMDetails;
        if(!$_SESSION[self::SESSION_NAME]) {
            $UTMDetails = null;
            return;
        }
        $_SESSION[self::SESSION_NAME] = new UTMDetails(json_decode($_SESSION[self::SESSION_NAME], true));
        // $UTMDetails = $this->parseUTM(json_decode($_SESSION[self::SESSION_NAME]));
        // if($UTMDetails) $this->storeUTM($UTMDetails);
    }

    function parseUTM(array $params):?UTMDetails {
        $source   = key_exists("utm_source",$params);
        $medium   = key_exists("utm_medium",$params);
        $campaign = key_exists("utm_campaign",$params);
        if($source || $medium || $campaign) return new UTMDetails($params);
        return null;
    }

    function storeUTM(UTMDetails $details, bool $redirect = false) {
        $_SESSION[self::SESSION_NAME] = json_encode($details);
        if(__APP_SETTINGS__['UTM_tracking_enabled']) {
            $this->updateOne(
                [
                    'source'   => $details->source(),
                    'medium'   => $details->medium(),
                    'campaign' => $details->campaign(),
                    'term'     => $details->term(),
                    'content'  => $details->content(),
                ],
                ['$inc' => ['count' => 1]],
                ['upsert' => true]
            );
        }
        if(!$redirect) return;
        // Do we want to be redirecting to clear UTM params from the current route?
        if(!__APP_SETTINGS__['UTM_redirect_enabled']) return;
        $cleanGet = $this->clearUTMParams($_GET);
        $this->redirect($cleanGet);
    }
    
    function clearUTMParams(array $params):array {
        unset($params["utm_source"],
            $params["utm_medium"],
            $params["utm_campaign"],
            $params['utm_term'],
            $params['utm_content']);
        return $params;
    }

    function redirect($params):never {
        $url = parse_url($_SERVER['REQUEST_URI']);
        $query = http_build_query($params);
        if($query) "?$query";
        header("Location: $url[path]".$query);
        exit;
    }

    static function read():?UTMDetails {
        return $_SESSION[self::SESSION_NAME];
        // global $UTMDetails;
        // return $UTMDetails;
    }
}