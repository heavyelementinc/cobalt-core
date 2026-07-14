<?php

namespace Components\ServiceAreas\Controllers;

use Cobalt\Controllers\Controller;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Components\Portfolio\Model\Client;
use Components\Portfolio\Model\Portfolio;
use Components\Projects\Models\Project;
use Components\ServiceAreas\Models\Town;
use Drivers\DatabaseManagement;
use Exceptions\HTTP\NotFound;
use MongoDB\Model\BSONDocument;
use MongoDB\UpdateResult;
use NumberFormatter;

class Areas extends Controller {
    protected array $countyData;
    protected array $townData;
    // private array $allowedCounties = ['Waldo', 'Hancock', 'Knox', 'Lincoln', 'Penobscot', 'Kennebec'];
    // private int $population_min = 5_000;
    function __construct(?string $name = null) {
        $this->countyData = include __DIR__ . "/countyData.php";
        $this->townData   = include __DIR__ . "/townData.php";
        // return parent::__construct($name);
    }

    public static function getValidTowns():array {
        // $counties = include __DIR__ . ""
        $towns = include __DIR__ . "/townData.php";
        $result = [];
        foreach($towns as $t => $d) {
            $result[$t] = $d['name'];
        }
        return $result;
    }

    public static function getValidCounties(?array $towns = null):array {
        $counties = $towns ?? include __DIR__ . "/countyData.php";
        $result = [];
        foreach($counties as $t => $d) {
            $result[$t] = $t;
        }
        return $result;
    }

    public static function getCountyOfTown(string $town,?array $towns = null):array {
        $towns = $towns ?? include __DIR__ . "/townData.php";
        if(!key_exists($town, $towns)) throw new NotFound("That town does not exist");
        $counties = $counties ?? include __DIR__ . "/countyData.php";
        if(!key_exists($towns[$town]['county'], $counties)) throw new NotFound("That county is invalid");
        return $counties[$towns[$town]['county']];
    }

    public static function getTownsInRegionOfTown(string $town, ?array $towns = null, ?array $counties = null):array {
        $towns = $towns ?? include __DIR__ . "/townData.php";
        $counties = $counties ?? include __DIR__ . "/countyData.php";

        $ct = $towns[$town]['county'];
        $regionName = $counties[$ct]['location'];
        $result = [];
        foreach($towns as $slug => $data) {
            if($counties[$data['county']]['location'] !== $regionName) continue;
            $result[] = $slug;
        }
        return $result;
    }

    public static function getGeoCoordsForTown(string $town, ?array $towns = null) {
        $towns = $towns ?? include __DIR__ . "/townData.php";
        $coords = $towns[$town]['geo']['location'];
        return [$coords['lng'], $coords['lat']];
    }

    public static function defineModel(): Model {
        return new Town();
    }

    public function edit($document): string {
        return "";
    }

    public function destroy(Model|BSONDocument $document): array {
        return [];
    }

    private function render_county(string &$county, array &$details, array &$array) {
        // if(!in_array($county, $this->allowedCounties)) continue;
        $array[$county] = "<div><div class='county-header'><img loading=\"lazy\" src=\"$details[img]\" alt=\"".strip_tags($details['credit'])."\" width='200' height='150'><h3>$county County</h3>".from_markdown($details['blurb'])."</div><ul>";
    }

    private function render_town(string &$key, array &$details, array &$array) {
        if($this->filter_town($details) === null) return;
        $array[$details['county']] .= "<li><a href='".self::BASEPATH."/$key'>$details[name], Maine</a></li>";
    }

    private function filter_town(array &$details):?array {
        if(empty(__APP_SETTINGS__['ServiceAreas_serve_counties'])) return $details;
        if(!in_array($details['county'], __APP_SETTINGS__['ServiceAreas_serve_counties'])) {
            return null;
        }
        return $details;
    }

    const BASEPATH = "/services/area";
    const CRITERIA_FOR_INCLUSION_POP_IS_OR_GREATER = 5_000;
    const CRITERIA_FOR_INCLUSION_INCLUDED_COUNTIES = ['Waldo', 'Knox', 'Lincoln', 'Hancock'];

    static function excludeMunincipality(string $county, string $type, string &$countyName):bool {
        $countyName = $county;
        $doNotInclude = !in_array($county,self::CRITERIA_FOR_INCLUSION_INCLUDED_COUNTIES);
        if($doNotInclude && $type === "city") {
            $countyName = "Other";
            return false;
        }
        if($doNotInclude) return true;
        return false;
    }

    function area(string $key) {
        // $data = json_decode(file_get_contents(__DIR__ . "/maine-towns.json"), true);
        if(!key_exists($key, $this->townData)) {
            throw new NotFound("Page not found");
        }
        if(!$this->filter_town($this->townData[$key])) {
            throw new NotFound("Page not found");
        }
        
        $town = $this->townData[$key];
        $countyName = $this->townData[$key]['county'];

        set("title", "Services in $town[name], ME");

        // Other towns in the area
        // $others = "";
        // foreach($this->townData as $k => $m) {
        //     $belongs = $m['county'];
        //     self::excludeMunincipality($m['county'], $m['type'], $belongs);
        //     if($belongs != $countyName) continue;
        //     $others .= "<li><a href=\"".self::BASEPATH."/$k\">$m[name], Maine</a></li>";
        // }

        // Calculate the distance between the current town and the
        // company's headquarters.
        $headquarters = $this->townData[__APP_SETTINGS__['ServiceAreas_default_location']]['geo']['location'];
        $distance = self::distance(
            $town['geo']['location']['lat'],
            $town['geo']['location']['lng'],
            $headquarters['lat'], 
            $headquarters['lng'], 
        "M");

        $descriptiveDistance = "";
        switch($distance) {
            case is_nan($distance):
                break;
            case $distance < 10:
                $descriptiveDistance = sprintf(__APP_SETTINGS__['ServiceAreas_strings_under_ten_miles'], (new NumberFormatter("en", NumberFormatter::SPELLOUT))->format(round($distance, 0)));
                break;
            case $distance < 30:
                $descriptiveDistance = sprintf(__APP_SETTINGS__['ServiceAreas_strings_between_ten_and_thirty'], round($distance, 0), $town['name']);
                break;
            case $distance < 60:
                $descriptiveDistance = sprintf(__APP_SETTINGS__['ServiceAreas_strings_between_thirty_and_sixty'],round($distance, 0), $town['name']);
                break;
            case $distance > 120:
                $descriptiveDistance = sprintf(__APP_SETTINGS__['ServiceAreas_strings_under_120_miles'], (round($distance / 10) * 10), $town['name']);
                break;
            default:
                $descriptiveDistance = sprintf(__APP_SETTINGS__['ServiceAreas_strings_120_and_over'], round($distance, 0), $town['name']);
                break;
        }

        $portfolioContent = $this->getPortfolioContent($key, $town);

        return view("Components/ServiceAreas/templates/town-page.php",[
            'town' => $town,
            'county' => $this->countyData[$countyName],
            // 'others' => $others,
            'distance' => $descriptiveDistance,
            'portolioItems' => $portfolioContent,
        ]);
    }

    function getPortfolioContent(string $townKey, array|Town $town):string {
        $projects = new Project();
        $projects->createIndex(['geo' => '2dsphere']);
        $portfolioInRegion = $projects->find([
            'published' => true,
            'geo' => [
                '$near' => [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => self::getGeoCoordsForTown($townKey, $this->townData),
                    ],
                    '$minDistance' => 0,
                    '$maxDistance' => 1000 * ($town['nearby'] ?? 300)
                ]
            ]
        ], [
            'limit' => 10
        ]);

        $rendered = "<section class=\"main-section\"><h2 class='section-title'>Our Projects</h2>";
        $rendered .= "<article><p>Here you'll find some of our latest projects in and around $town[name]!</p></article>";
        $rendered .= "<div class=\"project-gallery project-gallery--service-area\">";
        $hasContent = false;
        /** @var Project $portItem */
        foreach($portfolioInRegion as $portItem) {
            $rendered .= $portItem->getIndexEntry();
            $hasContent = true;
        }
        if(!$hasContent) return "";

        return $rendered . "</div></section>";
    }

    static function sitemap() {
        $html = "";
        $data = json_decode(file_get_contents(__DIR__ . "/maine-towns.json"), true);
        $filemtime = date("Y-m-d", filemtime(__DIR__."/maine-towns.json"));
        foreach($data as $key => $value) {
            $html .= view("sitemap/url.xml", [
                'location' => server_name() . self::BASEPATH . "/$key",
                'lastModified' => $filemtime,
                'priority' => 999,
            ]);
        }
        return $html;
    }

    static function geocode() {
        $data = json_decode(file_get_contents(__DIR__ . "/maine-towns.json"), true);
        $token = "";
        $requests = 0;
        foreach($data['munincipalities'] as $key => $town) {
            if(key_exists('geo', $town)) continue;
            $response = geocode_address(str_replace(" ","+","$town[name], Maine, United States"), $token);
            $geo = $response['results'][0]['geometry'];
            $data['munincipalities'][$key]['geo'] = $geo;
            file_put_contents(__DIR__ . "/maine-towns.json", json_encode($data));
            $requests += 1;
            print("Requests: $requests\r");
            // sleep(1);
            // break;
        }
    }

    const EARTH_RADIUS_METERS = 6371000;
    const EARTH_RADIUS_MILES = 3959;
    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    static function haversineGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = self::EARTH_RADIUS_METERS)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}