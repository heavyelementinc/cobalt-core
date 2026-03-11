<?php

namespace Cobalt\Documentation\Model;

use Cobalt\Controllers\ModelController;
use Cobalt\Documentation\Controllers\Documentation as ControllersDocumentation;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayOfPermissionsType;
use Cobalt\Model\Types\ArrayOfRoutesType;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\HexColorType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\StringType;
use DOMDocument;

class Documentation extends Model {

    public StringType $headline;
    public BlockType $body;
    public ArrayType $tags;
    public ArrayType $includedRoutes;
    public ArrayType $excludedRoutes;
    public HexColorType $color;
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
                ]
            ],
            'route' => [
                new ArrayOfRoutesType,
                'allow_custom' => true
            ],
            'privileged' => new ArrayOfPermissionsType,
            'body' => new BlockType,
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
            id=\"documentation-dialog-button\"
            onclick=\"documentationDialog.open ? documentationDialog.close() : documentationDialog.show()\">
            <i name=\"help-circle\"></i>
        </button>";
    }

    public function renderDialog():string {
        return "<dialog id=\"documentationDialog\"></dialog>";
    }

}