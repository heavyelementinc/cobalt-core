<?php

namespace Components\ServiceAreas\Models;

use Cobalt\Commands\Exceptions\CommandError;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\BooleanType;
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
            'name' => [
                new StringType,
                'index' => []
            ],
            'slug' => new StringType,
            'href' => new StringType,
            'location' => new StringType,
            'img' => new ImageType,
            'credit' => new StringType,
            'blurb' => new MarkdownType,
            'include' => new BooleanType,
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

    public function getValidCounties():array {
        $name = $this->distinct('name');
        $filled = array_combine($name, $name);
        return $filled;
    }

    public function getCollectionName($string = null): string
    {
        return "counties";
    }

    public function __initializeDataset(int &$count):Generator
    {
        $inserted = 0;
        $data = include __DIR__ . "/../Controllers/countyData.php";
        foreach($data as $key => $data) {
            $mutant = $data;
            $mutant['name'] = $key;
            $mutant['slug'] = strtolower($key);
            $doc = new $this();
            if($mutant['img']) {
                $file_name = str_replace("/core-content", "", $mutant['img']);
                $image = __APP_ROOT__."/public/res/$file_name";
                if(!file_exists($image)) $image = __ENV_ROOT__."/shared/$file_name";
                if(!file_exists($image)) throw new CommandError("Failed to find $file_name");
            }
            if($image) {
                $id = $doc->img->__store($image, pathinfo($mutant['img'], PATHINFO_BASENAME));
                if(!$id) throw new Exception("Failed to upload image");
                $mutant['img'] = $id;
            }
            unset($mutant['dark']);
            $doc->bsonUnserialize($mutant);
            yield $doc;
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