<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Types\Traits\GeoCommon;

class GeoPointType extends MixedType {
    use GeoCommon;
    const LNG_INDEX = 0;
    const LAT_INDEX = 1;
    const COORD_KEY = 'coordinates';

    function __get($name) {
        switch($name) {
            case 'lng':
            case 'long':
            case 'longitude':
                return $this->raw[self::COORD_KEY][self::LNG_INDEX];
            case 'lat':
            case 'latitude':
                return $this->raw[self::COORD_KEY][self::LAT_INDEX];
            default:
                return parent::__get($name);
        }
    }

    function __set($name, $value) {
        switch($name) {
            case 'lng':
            case 'long':
            case 'longitude':
                $this->raw[self::COORD_KEY][self::LNG_INDEX] = $value;
                break;
            case 'lat':
            case 'latitude':
                $this->raw[self::COORD_KEY][self::LAT_INDEX] = $value;
                break;
            default:
                parent::__set($name, $name);
                break;
        }
    }

    function __isset($property){
        switch($property) {
            case 'lng':
            case 'long':
            case 'longitude':
                return isset($this->raw[self::COORD_KEY][self::LNG_INDEX]);
            case 'lat':
            case 'latitude':
                return isset($this->raw[self::COORD_KEY][self::LAT_INDEX]);
            default:
                return parent::__isset($property);
                break;
        }
    }
}