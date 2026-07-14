<?php

namespace Components\ServiceAreas\Models;

use Cobalt\Commands\Exceptions\CommandError;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Directives\SearchableDirective;
use Cobalt\Model\Enums\SearchableTypes;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\GeoPointType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\URLType;
use Drivers\DatabaseManagement;
use Exception;
use Generator;
use MongoDB\UpdateResult;

/**
 * @property StringType $state
 * @property StringType $name
 * @property URLType $href
 * @property EnumType $type
 * @property BooleanType $seat
 * @property EnumType $county
 * @property NumberType $pop
 * @property NumberType $mi2
 * @property NumberType $km2
 * @property NumberType $inc
 * @property MarkdownType $blurb
 * @property ImageType $img
 * @property GeoPointType $geo
 * @property StringType $slug
 * @property StringType $credit
 * @property NumberType $nearby
 * @property BooleanType $include
 * @package Components\ServiceAreas\Models
 */
class Town extends Model implements Migration {
    
    public function defineSchema(array $schema = []): array {
        return [
            'state' => [
                new StringType(),
                'valid' => [
                    'AL'=>'Alabama',
                    'AK'=>'Alaska',
                    'AS'=>'American Samoa',
                    'AZ'=>'Arizona',
                    'AR'=>'Arkansas',
                    'CA'=>'California',
                    'CO'=>'Colorado',
                    'CT'=>'Connecticut',
                    'DE'=>'Delaware',
                    'DC'=>'District of Columbia',
                    'FM'=>'Federated States of Micronesia',
                    'FL'=>'Florida',
                    'GA'=>'Georgia',
                    'GU'=>'Guam Gu',
                    'HI'=>'Hawaii',
                    'ID'=>'Idaho',
                    'IL'=>'Illinois',
                    'IN'=>'Indiana',
                    'IA'=>'Iowa',
                    'KS'=>'Kansas',
                    'KY'=>'Kentucky',
                    'LA'=>'Louisiana',
                    'ME'=>'Maine',
                    'MH'=>'Marshall Islands',
                    'MD'=>'Maryland',
                    'MA'=>'Massachusetts',
                    'MI'=>'Michigan',
                    'MN'=>'Minnesota',
                    'MS'=>'Mississippi',
                    'MO'=>'Missouri',
                    'MT'=>'Montana',
                    'NE'=>'Nebraska',
                    'NV'=>'Nevada',
                    'NH'=>'New Hampshire',
                    'NJ'=>'New Jersey',
                    'NM'=>'New Mexico',
                    'NY'=>'New York',
                    'NC'=>'North Carolina',
                    'ND'=>'North Dakota',
                    'MP'=>'Northern Mariana Islands',
                    'OH'=>'Ohio',
                    'OK'=>'Oklahoma',
                    'OR'=>'Oregon',
                    'PW'=>'Palau',
                    'PA'=>'Pennsylvania',
                    'PR'=>'Puerto Rico',
                    'RI'=>'Rhode Island',
                    'SC'=>'South Carolina',
                    'SD'=>'South Dakota',
                    'TN'=>'Tennessee',
                    'TX'=>'Texas',
                    'UT'=>'Utah',
                    'VT'=>'Vermont',
                    'VI'=>'Virgin islands',
                    'VA'=>'Virginia',
                    'WA'=>'Washington',
                    'WV'=>'West Virginia',
                    'WI'=>'Wisconsin',
                    'WY'=>'Wyoming',
                    'AE'=>'Armed Forces Africa \ Canada \ Europe \ Middle East',
                    'AA'=>'Armed Forces America (except Canada)',
                    'AP'=>'Armed Forces Pacific'
                ],
            ],
            'name' => [
                new StringType(),
                'index' => [
                    'searchable' => new SearchableDirective(true)
                ]
            ],
            'href' => new URLType(),
            'type' => [
                new EnumType(),
                'valid' => [
                    'city' => 'City',
                    'town' => 'Town',
                    'plantation' => 'Plantation',
                ]
            ],
            'seat' => new BooleanType(),
            'county' => [
                new EnumType(),
                'valid' => function () {
                    return (new County())->getValidCounties();
                },
                'index' => []
            ],
            'pop' => new NumberType(),
            'mi2' => new NumberType(),
            'km2' => new NumberType(),
            'inc' => new NumberType(),
            'blurb' => new MarkdownType(),
            'img' => new ImageType(),
            'geo' => new GeoPointType(),
            // 'geo_bound' => 
            'slug' => new StringType(),
            'credit' => new StringType(),
            'nearby' => [
                new NumberType(),
                'description' => "Set the radius for nearby projects for this town page",
                'index' => [],
            ],
            'include' => new BooleanType(),
        ];
    }

    public function defineController(): ModelController {
        throw new \Exception('Not implemented');
    }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function getCollectionName($string = null): string {
        return "serviceAreas";
    }

    public function __initializeDataset(int &$count):Generator {
        $county = new County();
        $inserted = 0;
        // foreach($county->__initializeDataset($count) as $item) {
        //     yield $item;
        //     $inserted += 1;
        // }
        
        $data = include __DIR__ . "/../Controllers/townData.php";
        foreach($data as $key => $data) {
            $mutant = $data;
            $mutant['slug'] = $key;
            $mutant['geo'] = [
                GeoPointType::COORD_KEY => [
                    GeoPointType::LNG_INDEX => $data['geo']['location']['lng'],
                    GeoPointType::LAT_INDEX => $data['geo']['location']['lat']
                ]
            ];
            unset($mutant['show_websites']);
            unset($mutant['dark']);
            $doc = new $this();
            if($mutant['img']) {
                $file_name = str_replace("/core-content", "", $mutant['img']);
                $image = __APP_ROOT__."/public/res/$file_name";
                if(!file_exists($image)) $image = __ENV_ROOT__."/shared/$file_name";
                if(!file_exists($image)) throw new CommandError("Failed to find $file_name");
            }
            if($image) {
                $id = $doc->img->__store($image, pathinfo($image, PATHINFO_BASENAME));
                if(!$id) throw new Exception("Failed to upload image");
                $mutant['img'] = $id;
            }
            $image = null;
            $doc->bsonUnserialize($mutant);
            yield $doc;
            $inserted += 1;
        }
    }

    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void
    {
        throw new \Exception('Not implemented');
    }

    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void
    {
        throw new \Exception('Not implemented');
    }
}