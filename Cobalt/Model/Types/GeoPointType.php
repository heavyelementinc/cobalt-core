<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Types\Traits\GeoCommon;
use Validation\Exceptions\ValidationIssue;

class GeoPointType extends MixedType {
    use GeoCommon;
    const LNG_INDEX = 0;
    const LAT_INDEX = 1;
    const COORD_KEY = 'coordinates';
    const INDEX_LOOKUP = [self::LNG_INDEX => 'longitude', self::LAT_INDEX => 'latitude'];
    const TYPE = "Point";
    

    function __get($name) {
        switch($name) {
            case 'lng':
            case 'long':
            case 'longitude':
            case self::LNG_INDEX:
                return $this->raw[self::COORD_KEY][self::LNG_INDEX];
            case 'lat':
            case 'latitude':
            case self::LAT_INDEX:
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
            case self::LNG_INDEX:
                $this->raw[self::COORD_KEY][self::LNG_INDEX] = $value;
                break;
            case 'lat':
            case 'latitude':
            case self::LAT_INDEX:
                $this->raw[self::COORD_KEY][self::LAT_INDEX] = $value;
                break;
            default:
                parent::__set($name, $name);
        }
    }

    function __isset($property){
        switch($property) {
            case 'lng':
            case 'long':
            case 'longitude':
            case self::LNG_INDEX:
                return isset($this->raw[self::COORD_KEY][self::LNG_INDEX]);
            case 'lat':
            case 'latitude':
            case self::LAT_INDEX:
                return isset($this->raw[self::COORD_KEY][self::LAT_INDEX]);
            default:
                return parent::__isset($property);
                break;
        }
    }

    function serialize() {
        return [
            'type' => self::TYPE,
            self::COORD_KEY => $this->raw[self::COORD_KEY]
        ];
    }

    const LNG_MIN = -180;
    const LNG_MAX = 180;
    const LAT_MIN = -90;
    const LAT_MAX = 90;

    /**
     * Must be passed a GeoJSON-formatted array!
     * https://www.mongodb.com/docs/manual/reference/geojson/#std-label-geospatial-indexes-store-geojson
     * @param mixed $value 
     * @return mixed 
     */
    function filter($value) {
        if($value['type'] !== "Point") throw new ValidationIssue("This field requires GeoJSON type of 'Point'");
        if($value[self::COORD_KEY][self::LNG_INDEX] < self::LNG_MIN) throw new ValidationIssue("Longitude must not be less than ". self::LNG_MIN . "&deg;");
        if($value[self::COORD_KEY][self::LNG_INDEX] > self::LNG_MAX) throw new ValidationIssue("Longitude must not be greater than ". self::LNG_MIN . "&deg;");
        if($value[self::COORD_KEY][self::LAT_INDEX] < self::LAT_MIN) throw new ValidationIssue("Latitude must not be less than ". self::LAT_MIN . "&deg;");
        if($value[self::COORD_KEY][self::LAT_INDEX] > self::LAT_MAX) throw new ValidationIssue("Latitude must not be greater than ". self::LAT_MIN . "&deg;");
        return parent::filter($value);
    }

    function field(string $class = "", array $misc = [], ?string $tag = null): string {
        return parent::field($class, $misc, $tag);
    }
}