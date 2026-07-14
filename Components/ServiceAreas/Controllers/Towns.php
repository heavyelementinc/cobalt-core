<?php

namespace Components\ServiceAreas\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\GeoPointType;
use Components\Projects\Models\Project;
use Components\ServiceAreas\Models\County;
use Components\ServiceAreas\Models\Town;
use Exceptions\HTTP\NotFound;
use Override;
use MongoDB\Model\BSONDocument;
use NumberFormatter;

class Towns extends ModelController {
    #[Override]
    public static function defineModel(): Model {
        return new Town();
    }

    #[Override]
    public function edit($document): string {
        register_user_bar_items([
            'edit' => sprintf("<a href='%s'><i name='eye'></i> View</a>", 
                static::get_route_href('townListing', [$document->slug])
            )
        ]);
        return view("Components/ServiceAreas/templates/admin/town-editor.php");
    }

    #[Override]
    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Delete $document->town?",
            'okay' => "Okay",
            'post' => $_POST
        ];
    }

    public function townListing($slug):string {
        $town = $this->model->findOne([
            'slug' => $slug,
            // 'county' => __APP_SETTINGS__['ServiceAreas_serve_counties'],
        ]);
        if(!$town) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        set('title', "$town[name], $town[state]");

        register_user_bar_items([
            'edit' => sprintf("<a href='%s'><i name='pencil'></i> Edit</a>", static::get_route_href('__edit', [$town->_id], ['context'=> 'admin']))
        ]);

        $county = (new County())->findOne(['name' => $town->county->value]);

        $descriptiveDistance = "";
        $headquarters = $this->model->findOne(['slug' => __APP_SETTINGS__['ServiceAreas_default_location']]);
        if($headquarters) {
            $distance = $headquarters->geo->distance($town->geo);
            switch($distance) {
                case is_nan($distance):
                    break;
                case $distance < 10:
                    $descriptiveDistance = $this->getDescriptiveDistanceString(__APP_SETTINGS__['ServiceAreas_strings_under_ten_miles'], (new NumberFormatter("en", NumberFormatter::SPELLOUT))->format(round($distance, 0)), $town, $headquarters);
                    break;
                case $distance < 30:
                    $descriptiveDistance = $this->getDescriptiveDistanceString(__APP_SETTINGS__['ServiceAreas_strings_between_ten_and_thirty'], round($distance, 0), $town, $headquarters);
                    break;
                case $distance < 60:
                    $descriptiveDistance = $this->getDescriptiveDistanceString(__APP_SETTINGS__['ServiceAreas_strings_between_thirty_and_sixty'],round($distance, 0), $town, $headquarters);
                    break;
                case $distance > 120:
                    $descriptiveDistance = $this->getDescriptiveDistanceString(__APP_SETTINGS__['ServiceAreas_strings_under_120_miles'], (round($distance / 10) * 10), $town, $headquarters);
                    break;
                default:
                    $descriptiveDistance = $this->getDescriptiveDistanceString(__APP_SETTINGS__['ServiceAreas_strings_120_and_over'], round($distance, 0), $town, $headquarters);
                    break;
            }
        }

        $portfolioContent = $this->getPortfolioContent($town);
        return view("Components/ServiceAreas/templates/town-page.php",[
            'town' => $town,
            'county' => $county,
            // 'others' => $others,
            'distance' => $descriptiveDistance,
            'portolioItems' => $portfolioContent,
        ]);
    }

    const DESCRIPTIVE_TEXT_ITEMS = [
        '%distance%',
        '%town_name%',
        '%county_name%',
        '%state_name%',
        '%state_abbreviated%',
        '%hq_name%',
        '%hq_county%',
        '%hq_state%',
        '%hq_state_abbreviated%',
    ];

    private function getDescriptiveDistanceString(string $string, mixed $distance, Town $currentTown, Town $headquarters):string {
        return str_replace(
            self::DESCRIPTIVE_TEXT_ITEMS,
            [
                $distance,                       // '%distance%',
                $currentTown->name->value,       // '%town_name%',
                $currentTown->county->value,     // '%county_name%',
                $currentTown->state->display(),  // '%state_name%',
                $currentTown->state->value,      // '%state_abbreviated%',
                $headquarters->name->value,      // '%hq_name%',
                $headquarters->county->value,    // '%hq_county%',
                $headquarters->state->display(), // '%hq_state%',
                $headquarters->state->value,     // '%hq_state_abbreviated%',
            ],
            $string
        );
    }

    
    function getPortfolioContent(Town $town):string {
        $projects = new Project();
        $projects->createIndex(['geo' => '2dsphere']);
        $portfolioInRegion = $projects->find([
            'published' => true,
            'geo' => [
                '$near' => [
                    '$geometry' => $town->geo->serialize(),
                    '$minDistance' => 0,
                    '$maxDistance' => 1000 * ($town['nearby']->value ?? 10)
                ]
            ]
        ], [
            'limit' => 10,
            'sort' => ['date' => -1],
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
    
    const BASEPATH = "/services/area";

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
}