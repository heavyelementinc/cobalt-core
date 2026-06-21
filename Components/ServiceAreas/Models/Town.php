<?php

namespace Components\Towns\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\GeoPointType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Drivers\DatabaseManagement;
use Generator;
use MongoDB\UpdateResult;

class Town extends Model implements Migration{
    
    public function defineSchema(array $schema = []): array {
        return [
            'state' => new StringType(),
            'name' => new StringType(),
            'href' => new StringType(),
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
                'valid' => [
                    "Androscoggin" => "Androscoggin",
                    "Aroostook"    => "Aroostook",
                    "Cumberland"   => "Cumberland",
                    "Franklin"     => "Franklin",
                    "Hancock"      => "Hancock",
                    "Kennebec"     => "Kennebec",
                    "Knox"         => "Knox",
                    "Lincoln"      => "Lincoln",
                    "Oxford"       => "Oxford",
                    "Penobscot"    => "Penobscot",
                    "Piscataquis"  => "Piscataquis",
                    "Sagadahoc"    => "Sagadahoc",
                    "Somerset"     => "Somerset",
                    "Waldo"        => "Waldo",
                    "Washington"   => "Washington",
                    "York"         => "York",
                ]
            ],
            'pop' => new StringType(),
            'mi2' => new NumberType(),
            'km2' => new NumberType(),
            'inc' => new NumberType(),
            'blurb' => new MarkdownType(),
            'img' => new ImageType(),
            'geo' => new GeoPointType(),
            'nearby' => [
                new NumberType(),
                'description' => "Set the radius for nearby projects for this town page"
            ],
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
        $inserted = 0;
        $data = __DIR__ . "/townData.php";
        foreach($data['munincipalities'] as $key => $data) {
            $mutant = $data;
            $mutant['slug'] = $key;
            $mutant['geo'] = [
                GeoPointType::COORD_KEY => [
                    GeoPointType::LNG_INDEX => $data['geo']['location']['lng'],
                    GeoPointType::LAT_INDEX => $data['geo']['location']['lat']
                ]
            ];
            $doc = new $this();
            $validated = $doc->__filter($mutant);
            yield $validated;
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