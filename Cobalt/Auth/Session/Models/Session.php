<?php

namespace Cobalt\Auth\Session\Models;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayOfUsersType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use DateTime;
use MongoDB\BSON\UTCDateTime;

class Session extends Model {
    const SESSION_COOKIE_KEY = "cobalt_token";
    public function defineSchema(array $schema = []): array {
        return [
            'token_session' => new StringType,
            'ip_address'    => new StringType,
            'details'       => [
                new ModelType,
                'scheme' => [
                    'browser'  => new ModelType,
                    'platform' => new ModelType
                ]
            ],
            'expires'       => new DateType,
            'persist'       => new BooleanType,
            // 'refresh'       => new DateType,
            'represents'    => new ArrayOfUsersType,
            'current_index' => new NumberType
        ];
    }

    public function defineController(): ModelController {
        throw new \Exception('Not implemented');
    }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function getCollectionName($string = null): string {
        return "CobaltSessions";
    }

    public function logInUser(User $user) {
        $this->represents->push($user);
        $this->current_index = $this->represents->length() - 1;
        $this->updateOne(['_id' => $this->_id], ['$set' => $this]);
    }

    public function logOutUser(User $user) {
        $this->represents->slice($user);
        $this->current_index = 0;
        $this->updateOne(['_id' => $this->_id], ['$set' => $this]);
    }

    static function newSession(User $user):Session {
        $raw = [
            'token_session' => $_COOKIE[Session::SESSION_COOKIE_KEY],
            'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
            'details' => self::getBrowserDetails(),
            'expires' => (new DateTime("+30 days"))->format(DATETIME_LOCAL_FORMAT),
            'persist' => true,
            // 'refresh' => 
            'represents' => [$user->_id],
            'current_index' => 0
        ];

        $session = new Session($raw);
        $filtered = $session->__filter($raw);
        $session->insertOne($filtered);
        return $session;
    }

    static function getBrowserDetails() {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        return [
            'browser' => self::getBowser($ua),
            'platform'  => self::getPlatform($ua),
        ];
    }

    static function getBowser($agent) {
        $browser = "Unknown";
        if (preg_match('/Chrome[\/\s](\d+\.\d+)/', $agent, $match) ) $browser = "Chrome";
        else if (preg_match('/Edge\/\d+/', $agent, $match) ) $browser = "Edge";
        else if (preg_match('/Firefox[\/\s](\d+\.\d+)/', $agent, $match) ) $browser = "Firefox";
        else if (preg_match('/Safari[\/\s](\d+\.\d+)/', $agent, $match) ) $browser = "Safari";
        else if (preg_match('/OPR[\/\s](\d+\.\d+)/', $agent, $match) ) $browser = "Opera";

        return [
            'build'   => $browser,
            'version' => $match[1]
        ];
    }

    static function getPlatform($agent) {
        $os = "Unknown";
        if(preg_match('/Android[\/\s](\d{1,2})/',$agent,$match)) $os = 'Android';
        elseif(preg_match('/Windows NT[\/\s](\d{1,2})/',$agent,$match)) $os = 'Windows';
        elseif(preg_match('/iPhone[\/\s]OS[\/\s](\d{1,2})|iPad[\/\s]OS[\/\s](\d{1,2})/',$agent,$match)) $os = 'iOS';
        elseif(preg_match('/CrOS[\/\s]\w*[\/\s](\d*.\d*.\d*)/',$agent,$match)) $os = 'ChromeOS';
        elseif(preg_match('/Mac[\/\s]OS[\/\s]X?[\/\s](\d{1,2})/',$agent,$match)) $os = 'Mac OS';
        elseif(preg_match('/Linux[\/\s](\w*)/',$agent,$match)) $os = 'Linux';

        return [
            'build' => $os,
            'version' => $match[1]
        ];
    }
}