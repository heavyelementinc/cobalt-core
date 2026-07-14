<?php

namespace Cobalt\Model\Types\Abstracts;

use Cobalt\Model\Types\MixedType;

abstract class GeoCommon extends MixedType {
    
    const DISTANCE_UNIT_MILES = "MILES";
    const DISTANCE_UNIT_METERS = "METERS";
    const DISTANCE_UNIT_KILOMETERS = "K";
    const DISTANCE_UNIT_NAUTICAL_MILES = "N";
    const FACTOR_MILES_TO_METERS = 1_609.344;
    const FACTOR_MILES_TO_NAUTICAL_MILES = 0.8684;

    static function compute_distance(int|float $lat1, int|float $lon1, int|float $lat2, int|float $lon2, string $unit = self::DISTANCE_UNIT_MILES) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        switch ($unit) {
            case self::DISTANCE_UNIT_KILOMETERS:
                return ($miles * (self::FACTOR_MILES_TO_METERS * .001)); // 1.609344);
            case self::DISTANCE_UNIT_METERS:
                return ($miles * self::FACTOR_MILES_TO_METERS);
            case self::DISTANCE_UNIT_NAUTICAL_MILES:
                return ($miles * self::FACTOR_MILES_TO_NAUTICAL_MILES); // 0.8684);
            default:
                return $miles;
        }
    }
}