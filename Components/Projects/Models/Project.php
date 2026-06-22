<?php

namespace Components\Projects\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Directives\FilterDirective;
use Cobalt\Model\Directives\GetDirective;
use Cobalt\Model\Directives\SearchableDirective;
use Cobalt\Model\Directives\SetDirective;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\FileType;
use Cobalt\Model\Types\ImageArrayType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\GeoPointType;
use Cobalt\Model\Types\WeakEnumType;
use Cobalt\Models\Directives\MutateDirective;
use Components\Projects\Controllers\ClientProjects;
use Components\ServiceAreas\Controllers\Areas;
use Generator;
use Drivers\DatabaseManagement;
use MongoDB\BSON\ObjectId;
use Override;
use MongoDB\UpdateResult;
use Validation\Exceptions\ValidationIssue;
use Validation\Normalize;

/** @package Projects */
class Project extends Model implements Migration {
    protected bool $__schema_allow_undefined_fields = false;

    public function defineController(): ModelController {
        return new ClientProjects();
    }
    
    public function getCollectionName($string = null): string {
        return "projects";
    }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function defineSchema(array $schema = []): array {
        $this->__set_index_checkbox_state(true);
        return [
            'order' => [
                new NumberType,
                'index' => [
                    'title' => 'Order',
                ]
            ],
            'name' => [
                new StringType, // string
                'index' => [
                    'title' => 'Name',
                    'searchable' => new SearchableDirective(true),
                ],
                // 'mutate' => new MutateDirective(function (&$val):void {
                //     if($this->)
                // })
            ],
            'url' => [
                new StringType,
                'required' => true,
                'filter' => function (&$val) {
                    if(!$val) $val = $this->__get('name')->value;
                    $val = ($val) ? url_fragment_sanitize($val) : url_fragment_sanitize($this->name);
                    return $val;
                },
            ],
            'teaser' => [
                new MarkdownType,
                'label' => 'Teaser Text',
                'description' => 'Teaser text should offer the visitor some enticing flavor text. Give visitors a reason to want to click on this project! Make sure you use relevant keywords! This should be short and sweet.',
                'index' => [
                    'title' => 'Teaser'
                ],
                'max' => 300,
            ],
            'body' => [
                new BlockType,
                'label' => 'Body Copy',
                'description' => "Here's where you can describe this project in as much detail as you'd like. There are no limits to what you can say here. Just make sure you use keywords for your business and mention your service area!",
                'index' => [
                    'title' => 'Body',
                    'searchable' => new SearchableDirective(true),
                ]
            ],
            'blurb' => [
                new MarkdownType
            ],
            'tags' => [
                new ArrayType,
                'valid' => [
                    'advocacy' => 'Design Advocacy',
                    'renders'  => '3D Rendering',
                    'room'     => 'One Room',
                    'home'     => 'Whole Home',
                    'shop'     => 'Shop'
                ],
                'index' => [
                    'title' => 'Tags',
                    'searchable' => new SearchableDirective(true),
                ]
            ],
            'primary' => new StringType,
            'cover_image' => [
                new ImageType,
                'set' => function (&$value) {
                    $url = "url('".get_image_url($value)."')";
                    update(".cover-placement-preview", [
                        'style' => ['--_background' => $url]
                    ]);
                }
            ],
            'cover_placement_desktop_x' => [
                new NumberType,
                'input_attrs' => ['type' => 'range'],
                'default' => 50,
                'min' => 0,
                'max' => 100
            ],
            'cover_placement_desktop_y' => [
                new NumberType,
                'input_attrs' => ['type' => 'range'],
                'default' => 50,
                'min' => 0,
                'max' => 100
            ],
            'cover_scale_desktop' =>[
                new NumberType,
                'input_attrs' => ['type' => 'range'],
                'default' => 100,
                'min' => 25,
                'max' => 200,
            ],
            'cover_placement_mobile_x' => [
                new NumberType,
                'input_attrs' => ['type' => 'range'],
                'default' => 50,
                'min' => 0,
                'max' => 100
            ],
            'cover_placement_mobile_y' => [
                new NumberType,
                'input_attrs' => ['type' => 'range'],
                'default' => 50,
                'min' => 0,
                'max' => 100
            ],
            'cover_scale_mobile' =>[
                new NumberType,
                'input_attrs' => ['type' => 'range'],
                'default' => 100,
                'min' => 25,
                'max' => 200,
            ],
            'images' => [
                new ImageArrayType,
                'label' => 'Image Gallery',
                'index' => [
                    'title' => 'Images',
                    'view' => fn ($url) => $this->images->length()
                ],
                'set' => new SetDirective(function (&$value):void {
                    if(is_null($this->cover_image->getValue()) && !isset($_POST['cover_image'])) {
                        $this->__modify('cover_image', $value[0], false);
                    }
                })
            ],
            'image_count' => [
                new NumberType
            ],
            'published' => [
                new BooleanType,
                'index' => [
                    'title' => 'Published',
                    'view' => fn ($url) => ($this->published->value) ? "Published" : "Unpublished"
                ]
            ],
            'shop' => new BooleanType(),
            'date' => [
                new DateType,
                'index' => [
                    'title' => 'Date',
                    'view' => fn ($date) => $this->date->format('datetime-local')
                ]
            ],
            'header_color' => [
                new EnumType,
                'valid' => [
                    '#000' => "Black",
                    '#fff' => "White",
                ]
            ],
            'darken_header' => [
                new EnumType,
                'valid' => [
                    '' => 'No darkening',
                    'd100' => '10% darkening',
                    'd200' => '20% darkening',
                    'd300' => '30% darkening',
                    'd400' => '40% darkening',
                    'w100' => '10% lightening',
                    'w200' => '20% lightening',
                    'w300' => '30% lightening',
                    'w400' => '40% lightening',
                ]
            ],
            'town' => [
                new WeakEnumType,
                'valid' => fn () => Areas::getValidTowns(),
                'required' => true,
                'filter' => function ($val) {
                    if(!$val) return null;
                    $this->__modify('county', Areas::getCountyOfTown($val), false);
                    $this->__modify('geo', ['type' => 'Point', 'coordinates' => Areas::getGeoCoordsForTown($val)], false);
                    return $val;
                },
                'display' => function ($val) {
                    $town = Areas::getValidTowns();
                    return $town[$val]['name'];
                },
                'index' => [
                    'filterable' => true,
                ],
            ],
            'county' => [
                new EnumType,
                'valid' => fn () => Areas::getValidCounties(),
            ],
            'geo' => [
                new GeoPointType
            ]
        ];
    }

    function getIndexEntry() {
        return view("Components/Projects/templates/parts/index-listing.php", ['doc' => $this]);
    }

    function getGallery() {
        $string = '';//'<a href="#1"><img src="/res/img/work/Bishop Great Room.jpeg"></a>';
        foreach($this->images as $index => $image){
            $string .= "<a href='#$index'><img src='$image[thumb]' data-main='$image[image]'></a>";
        }

        return $string;
    }

    
    #[Override]
    public function __initializeDataset(int &$count): Generator {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void {
        
    }

    #[Override]
    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void {
        
    }
}