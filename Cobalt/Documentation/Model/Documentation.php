<?php

namespace Cobalt\Documentation\Model;

use Cobalt\Controllers\ModelController;
use Cobalt\Database\Classes\CobaltCursor;
use Cobalt\Documentation\Controllers\Documentation as ControllersDocumentation;
use Cobalt\Model\Directives\IndexableDirective;
use Cobalt\Model\Directives\SearchableDirective;
use Cobalt\Model\Enums\SearchableTypes;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayOfPermissionsType;
use Cobalt\Model\Types\ArrayOfRoutesType;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\HexColorType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\StringType;
use DateTime;
use DOMDocument;
use Override;
use Drivers\DatabaseManagement;
use Exception;
use Generator;
use MongoDB\UpdateResult;

/**
 * @property StringType $title
 * @property EnumType $status
 * @property ArrayOfRoutesType $route
 * @property ArrayOfPermissionsType $privileged
 * @property BlockType $body
 * @package Cobalt\Documentation\Model
 */
class Documentation extends Model implements Migration {
    const STATUS_PUBLIC       = "0";
    const STATUS_DRAFT        = "1";
    const STATUS_PRIVELEGED   = "2";
    const STATUS_PRIVATE      = "3";

    public function defineController(): ModelController {
        return new ControllersDocumentation();
    }

    public static function __getVersion(): string {
        return '1.0';
    }

    function defineSchema(array $schema = []): array {
        return [
            'title' => [
                new StringType,
                'index' => []
            ],
            'status' => [
                new EnumType,
                'valid' => [
                    self::STATUS_PUBLIC     => 'Public',
                    self::STATUS_DRAFT      => 'Draft',
                    self::STATUS_PRIVELEGED => 'Priveleged',
                    self::STATUS_PRIVATE    => 'Private',
                ],
                'index' => []
            ],
            'route' => [
                new ArrayOfRoutesType,
                // 'allow_custom' => true
            ],
            'privileged' => new ArrayOfPermissionsType,
            'body' => [
                new BlockType,
                // 'index' => [],
                // 'searchable' => new SearchableDirective(true, SearchableTypes::TEXT)
            ],
        ];
    }

    public function getCollectionName($string = null): string {
        return "CobaltDocumentation";
    }

    public function modelView($document): string {
        return "";
    }

    public function importStaticDocument(DOMDocument $document) {

    }

    public function renderButton():string {
        return "<button 
            id=\"documentation-dialog-button\" action=\"/documentation/index/\">
            <i name=\"help-circle\"></i>
            <span class='documentation-count' data-value='".get("documentation_count")."'>".get("documentation_count")."</span>
        </button>";
    }

    public function renderDialog():string {
        return "<dialog id=\"documentationDialog\"></dialog>";
    }

    function findDocsByControllerName(string $controllerName, array $options = []):?CobaltCursor {
        $options = array_merge(['limit' => 120], $options);
        return $this->find(['route' => $controllerName], $options);
    }

    function countDocsByControllerName(string $controllerName):?int {
        return $this->count(['route' => $controllerName]);
    }

    static function declare(string $title, array $controller, string $file, string $status = self::STATUS_PUBLIC, array $permissions = []) {
        if(!is_array($GLOBALS['built_ins'])) $GLOBALS['built_ins'] = [];
        $content = file_get_contents($file);
        if(!$content) throw new Exception("Failed to load $file");
        $doc = new self();
        $doc->bsonUnserialize([
            'title' => $title,
            'status' => $status,
            'route' => $controller,
            'privileged' => $permissions,
            'body' => BlockType::from_markdown($content, true, new DateTime()),
        ]);
        $GLOBALS['built_ins'][] = "";
    }

    #[Override]
    public function __initializeDataset(int &$count):Generator
    {
        include __DIR__ . "/../builtins.php";
        foreach($GLOBALS['built_ins'] as $doc) {
            yield $doc;
        }
    }

    #[Override]
    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void
    {
        throw new \Exception('Not implemented');
    }

}