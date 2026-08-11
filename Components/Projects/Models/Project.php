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
        $format = __APP_SETTINGS__['app_name'];
        $nap_reminder = <<<HTML
        <li>Make sure that if you use your company name that it's spelled and 
            formatted exactly the same way you normally do. Don't abbreviate or 
            shorten your company name. "<strong>$format</strong>"
        </li>
        HTML;

        $image_seo = <<<HTML
            <details>
            <summary>SEO Suggestions</summary>
            <ol>
                <li>Make each image unique.</li>
                <li>Right click (or two-finger click, or long-press) to open
                    the metadata dialog.
                </li>
                <li>In the metadata dialog make sure that you:
                    <ol>
                        <li>Rename each image so it has relevant keywords.<br>
                            <small>e.g. <em>remodeled-kitchen-and-restored-countertop</em></small>
                        </li>
                        <li>Give each image descriptive text. This is absolutely
                            critical for having your project page appear in search
                            results.<br>
                            <small>e.g. <em>Kitchen remodel and countertop 
                                restoration in [town name] by {{app.app_name}}
                            </em></small>
                        </li>
                        $nap_reminder
                    </ol>
                </li>
            </ol>
            </details>
        HTML;
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
                'label' => 'Project name',
                'description' => <<<HTML
                <details>
                    <summary>SEO Suggestions</summary>
                    <ol>
                        <li>Avoid using customer names.</li>
                        <li>Don't be too poetic. Don't be too robotic.</li>
                        <li>Use a min of 18 characters and a max of 60 characters</li>
                        <li>Make sure you title this project with the words your 
                            customer will be searching for when looking 
                            for your services!<br>
                            <small style="font-style: italic">e.g. Wallpaper Removal at a Colonial-style Northport Home</small>
                        </li>
                        <li>The name should be <em>(but doesn't have to be)</em>
                            unique to your projects.</li>
                        <li>When in doubt, follow this formula:<br>
                            <small><code>[Core Service] + [Location Modifier] + [Customer Detail OR Unique Aspect of Project]</code></small>
                        </li>
                    </ol>
                </details>
                HTML,
                'required' => true,
                'min' => 10,
                'max' => 100,
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
                'description' => <<<HTML
                <details>
                    <summary>SEO Suggestions</summary>
                    <ol>
                        <li><strong>Tell the story</strong>: Absolute minimum of 40 words
                            <ol>
                                <li>Don't just talk about the functional aspect of your project.
                                    Describe the <em>quality of your work!</em></li>
                                <li>Explain the "how" and the "why."</li>
                                <li>Talk about your customers needs &amp; concerns! How you addressed them.</li>
                                <li>Start to finish: address the beginning, middle, and end!</li>
                            </ol>
                        </li>
                        <li><strong>Locality:</strong> ensure you mention the city, town, or neighborhood (if relevant).</li>
                        <li><strong>Backlinks:</strong> Include links to relevant service pages.</li>
                        <li><strong>Keep in mind:</strong> this project <strong>may be the first time
                            a potential customer interacts with your brand!</strong>
                            Make a strong first impression!
                        </li>
                        <li><strong>Bonus:</strong> Include a quote from the customer as a <code>Quote</code>
                            element.
                        </li>
                        $nap_reminder
                    </ol>
                </details>
                HTML,
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
                },
                'description' => $image_seo
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
                }),
                'obscure_filename' => false,
                'description' => $image_seo,
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
            ],
            // 'seo_checklist' => [
            //     new ModelType(),
            //     'schema' => [
            //         'title_private' => [
            //             new CheckboxType,
            //             'default' => false,
            //             'label' => "Avoid using customer names.",
            //         ],
            //         'title_describe' => [
            //             new CheckboxType,
            //             'default' => false,
            //             'label' => "Don't be too poetic. Don't be too robotic.",
            //         ],
            //         'title_length' => [
            //             new CheckboxType,
            //             'default' => false,
            //             'label' => "Use a min of 18 characters and a max of 60 characters",
            //         ],
            //         'title_keywords' => [
            //             new CheckboxType,
            //             'default' => false,
            //             'label' => "Make sure you title this project with the words your customer will be searching for when looking for your services!<br><small style=\"font-style: italic\">e.g. Wallpaper Removal at a Colonial-style Northport Home</small>",
            //         ],
            //         'title_unique' => [
            //             new CheckboxType,
            //             'default' => false,
            //             'label' => "The name should be <em>(but doesn't have to be)</em> unique to your projects.",
            //         ],
            //         'title_formula' => [
            //             new CheckboxType,
            //             'default' => false,
            //             'label' => "When in doubt, follow this formula:<br><small><code>[Core Service] + [Location Modifier] + [Customer Detail OR Unique Aspect of Project]</code></small>",
            //         ],
            //     ]
            // ]
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