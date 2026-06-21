<?php

namespace Components\Towns\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\StringType;
use Drivers\DatabaseManagement;
use Exception;
use Generator;
use MongoDB\UpdateResult;

class County extends Model implements Migration {
    public function defineSchema(array $schema = []): array
    {
        return [
            'name' => new StringType,
            'href' => new StringType,
            'location' => new StringType,
            'img' => new ImageType,
            'credit' => new StringType,
            'blurb' => new MarkdownType
        ];
    }

    public function defineController(): ModelController
    {
        throw new \Exception('Not implemented');
    }

    public static function __getVersion(): string
    {
        return "1.0";
    }

    public function getCollectionName($string = null): string
    {
        return "counties";
    }

    public function __initializeDataset(int &$count):Generator
    {
        $inserted = 0;
        $data = json_decode(file_get_contents(__APP_ROOT__ . "/Components/Towns/Controllers/maine-towns.json"), true);
        foreach($data['counties'] as $key => $data) {
            $mutant = $data;
            $mutant['name'] = $key;
            $doc = new $this();
            $id = $doc->img->__store(__APP_ROOT__."/public/".$mutant['img'], pathinfo($mutant['img'], PATHINFO_BASENAME));
            if(!$id) throw new Exception("Failed to upload image");
            $mutant['img'] = $id;
            $validated = $doc->__filter($mutant);
            yield $validated;
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