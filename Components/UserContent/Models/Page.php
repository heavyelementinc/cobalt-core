<?php

namespace Components\UserContent\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\UserIdType;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Drivers\DatabaseManagement;
use Generator;
use MongoDB\UpdateResult;
use Symfony\Component\String\TruncateMode;

class Page extends Model implements Migration {

    const VISIBILITY_PRIVATE = 1;
    const VISIBILITY_DRAFT   = 2;
    const VISIBILITY_HIDDEN  = 64; // Hidden is publicly accessible for anyone who has the link, but it's not listed on the sitemap, ineligible to be displayed as related content
    const VISIBILITY_UNLISTED = 128; // Unlisted is publicly accessible and is displayed on the site map but not the index
    const VISIBILITY_PUBLIC  = 256; // Public is on the site map, the index

    const SPLASH_POSITION_SPLIT  = 0b000001;
    const SPLASH_POSITION_FADE   = 0b000010;
    const SPLASH_POSITION_FLOAT  = 0b000100;
    const SPLASH_POSITION_TWO_UP = 0b001000;
    const SPLASH_POSITION_CENTER = 0b010000;
    const SPLASH_IMAGE_ONLY      = 0b100000;

    const METADATA_FEDIVERSE_CREDIT_PUBLICATION = 0b0001;
    const METADATA_INCLUDE_FOOTER               = 0b0010;

    const FLAGS_REQUIRES_ACCOUNT       = 0b00000001;
    const FLAGS_EXCLUDE_FROM_SITEMAP   = 0b00000010;
    const FLAGS_EXCLUDE_RELATED_PAGES  = 0b00000100;
    const FLAGS_HIDE_VIEW_COUNT        = 0b00001000;
    const FLAGS_READ_TIME_MANUALLY_SET = 0b00010000;
    const FLAGS_INCLUDE_PERMALINK      = 0b00100000;
    const FLAGS_HIDE_WEBMENTIONS       = 0b01000000;

    const BIO_AVATAR_RADIUS_ROUNDED  = 0b0001;
    const BIO_AVATAR_RADIUS_CIRCULAR = 0b0010;

    public function defineSchema(array $schema = []): array {
        return [
            'url_slug' => [
                new StringType,
                'required' => true,
            ],
            'title' => [
                new StringType,
                'required' => true,
            ],
            'visibility' => [
                new EnumType,
                'valid' => [
                    self::VISIBILITY_PRIVATE => "Private",
                    self::VISIBILITY_DRAFT  => "Draft",
                    self::VISIBILITY_HIDDEN => "Hidden",
                    self::VISIBILITY_UNLISTED => "Unlisted",
                    self::VISIBILITY_PUBLIC => "Public",
                ],
            ],
            'live_date' => [
                new DateType,
                'required' => true,
            ],
            'views' => [
                new NumberType,
                'default' => 0,
            ],
            'bot_hits' => [
                new NumberType,
                'default' => 0,
            ],
            'splash_image' => [
                new ImageType,
            ],
            'splash_image_alignment' => [
                new ArrayType,
                'valid' => [
                    'center' => 'Center',
                    'top' => 'Top',
                    'right' => 'Right',
                    'bottom' => 'Bottom',
                    'left' => 'Left',
                ],
            ],
            'splash_type' => [
                new EnumType,
                'default' => self::SPLASH_POSITION_FADE,
                'valid' => [
                    self::SPLASH_POSITION_FADE => "Fade (full width image, text over top)",
                    self::SPLASH_POSITION_CENTER => "Centered text over image",
                    self::SPLASH_POSITION_SPLIT => "Split (image on one half)",
                    self::SPLASH_POSITION_FLOAT => "Float (image is 25% of width of screen)",
                    self::SPLASH_POSITION_TWO_UP => "Two Up (fills normal content width)",
                    self::SPLASH_IMAGE_ONLY => "Image Only",
                ],
            ],
            'splash_reverse' => [
                new EnumType,
                'default' => 'normal',
                'valid' => [
                    'normal' => 'Text on Right (bottom on mobile)',
                    'row-reverse' => 'Text on Left (top on mobile)',
                ]
            ],
            'subtitle' => [
                new MarkdownType,
                'required' => true,
            ],
            'summary' => [
                new StringType,
                'required' => true
            ],
            'body' => [
                new BlockType,
            ],
            'time_to_read' => [
                new StringType
            ],
            'cta' => [
                new StringType,
            ],
            'cta_href' => [
                new StringType
            ],
            // 'include_aside' => [
            //     new BooleanType
            // ],
            // 'aside_positioning' => [
            //     'default' 
            // ]
            'max_related' => [
                new NumberType,
                'default' => 3,
            ],
            'related_title' => [
                new StringType
            ],
            'show_main_nav' => [
                new BooleanType,
                'default' => false
            ],
            'opengraph_title' => [
                new StringType
            ],
            'tags' => [
                new ArrayType,
                'allow_custom' => true,
                'valid' => function () {

                }
            ],
            'metadata_flags' => [
                new BinaryType,
                'valid' => [
                    self::METADATA_FEDIVERSE_CREDIT_PUBLICATION => "Credit Publication on Fediverse",
                    self::METADATA_INCLUDE_FOOTER => "Include tag links in post footer",
                ],
                'default' => (__APP_SETTINGS__['LandingPages_include_footer_by_default']) ? self::METADATA_INCLUDE_FOOTER : 0,
            ],
            'token' => new StringType,
            'view' => [
                new EnumType,
                'default' => 'default',
                'valid' => [
                    'default' => 'Default',
                    'landing' => 'Landing (Excludes Main Navigation)'
                ]
            ],
            'style' => [
                new MarkdownResult,
            ],
            'flags' => [
                new BinaryType,
                'valid' => [
                    self::FLAGS_REQUIRES_ACCOUNT => 'Access Exclusive to Users',
                    self::FLAGS_EXCLUDE_FROM_SITEMAP => "Exclude Page from Sitemap",
                    self::FLAGS_EXCLUDE_RELATED_PAGES => "Do Not Show Related Pages",
                    self::FLAGS_HIDE_VIEW_COUNT => "Hide View Count",
                    self::FLAGS_READ_TIME_MANUALLY_SET => "Read Time Manually Set",
                    self::FLAGS_INCLUDE_PERMALINK => "Include Permalink in URL",
                    self::FLAGS_HIDE_WEBMENTIONS => "Hide Webmention Interactions",
                ],
                'default' => (__APP_SETTINGS__['LandingPages_show_related']) ? self::FLAGS_EXCLUDE_RELATED_PAGES : 0
            ],
            'preview_key' => [
                new StringType,
                'display' => function ($val) {
                    $name = server_name();
                    return $name.$this->url_slug->get_path()."?pkey=".$val;
                }
            ],
            'author' => [
                new UserIdType,
                'required' => true,
                'nullable' => true,
                'permission' => 'Pages_allowed_author',
                'index' => [
                    'title' => 'Author',
                    'order' => 9,
                    'view' => function () {
                        return $this->author->getName();
                    }
                ]
            ],
            'include_bio' => [
                new BooleanType,
                'default' => __APP_SETTINGS__['LandingPage_bio_by_default']
            ],
            'bio_headline' => [
                new StringType
            ],
            'bio' => [
                new BlockType,
            ],
            'bio_cta' => [
                new StringType,
            ],
            'bio_flags' => [
                new BinaryType,
                'valid' => [
                    self::BIO_AVATAR_RADIUS_ROUNDED => "Avatar Rounded",
                    self::BIO_AVATAR_RADIUS_CIRCULAR => "Avatar Circular*",
                ]
            ],
            'include_in_route_group' => [
                new BooleanType,
                'default' => false
            ],
            'route_group' => [
                new ArrayType,
                'allow_custom' => true,
                'valid' => function () {
                    $arr = [];
                    // $route_data = array_keys(getRouteGroups());
                    // foreach($route_data as $key) {
                    //     $arr[$key] = $key;
                    // }
                    // Let's unwind the contexts
                    foreach(getRouteGroups() as $context => $route_data) {
                        if(in_array($context, $arr)) continue;
                        // $arr = [];//array_fill_keys($route_data, $route_data);
                        // Unwind the route data
                        foreach($route_data as $rt => $val) {
                            $nav = $val['navigation'];
                            if($val['context'] !== "web") continue;
                            // And finally loop through the route groups
                            foreach($nav as $group_index => $value) {
                                if(is_array($value) && !in_array($group_index, $arr) && $group_index) {
                                    $arr[$group_index] = $group_index;
                                } else if (is_string($value) && !in_array($value, $arr) && $value) {
                                    $arr[$value] = $value;
                                }
                            }
                        }
                    }
                    ksort($arr, SORT_ASC);
                    return ['' => '-- SELECT --', ...$arr];
                }
            ],
            'route_link_label' => new StringType,
            'route_order' => [
                new NumberType,
                'default' => 999
            ]
        ];
    }

    public function defineController(): ModelController {
        throw new \Exception('Not implemented');
    }

    public static function __getVersion(): string {
        return "2.0";
    }

    public function getCollectionName($string = null): string {
        return "userContentPages";
    }

    public function __initializeDataset(int &$count):Generator
    {
        throw new \Exception('Not implemented');
    }

    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void
    {
        throw new \Exception('Not implemented');
    }

    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void
    {
        throw new \Exception('Not implemented');
    }

    function get_tags() {
        $results = $this->distinct("tags", [], ['limit' => 1000]);
        $array = [];
        foreach($results as $value) {
            $array[$value] = $value;
        }
        $predefined_tags = __APP_SETTINGS__['PageMap_predefined_tags'];
        // if($this instanceof PostMap) $predefined_tags = __APP_SETTINGS__['PostMap_predefined_tags'];
        return array_merge($array, $predefined_tags ?? []);
    }
}