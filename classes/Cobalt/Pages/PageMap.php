<?php

namespace Cobalt\Pages;

use Cobalt\Maps\GenericMap;
use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\BlockResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use Controllers\Traits\Indexable;
use Validation\Exceptions\ValidationIssue;

class PageMap extends PersistanceMap {
    const VIEW_TYPE = [
        'default' => '/pages/landing/views/default.html',
        'landing' => '/pages/landing/views/landing.html',
    ];
    const VISIBILITY_PRIVATE = 1;
    const VISIBILITY_DRAFT   = 2;
    const VISIBILITY_UNLISTED = 128;
    const VISIBILITY_PUBLIC  = 256;

    const SPLASH_POSITION_SPLIT  = 0b00001;
    const SPLASH_POSITION_FADE   = 0b00010;
    const SPLASH_POSITION_FLOAT  = 0b00100;
    const SPLASH_POSITION_TWO_UP = 0b01000;
    const SPLASH_POSITION_CENTER = 0b10000;

    const FLAGS_REQUIRES_ACCOUNT      = 0b00000001;
    const FLAGS_EXCLUDE_FROM_SITEMAP  = 0b00000010;
    const FLAGS_EXCLUDE_RELATED_PAGES = 0b00000100;

    const ASIDE_SIDEBAR_NATURAL      = 0b000001;
    const ASIDE_SIDEBAR_REVERSE      = 0b000010;
    const ASIDE_SIDEBAR_FOOTER       = 0b000100;
    const ASIDE_STICKY               = 0b001000;
    const ASIDE_INCLUDE_TOC_INDEX    = 0b010000;
    const ASIDE_INDEX_BEFORE_CONTENT = 0b100000;

    const BIO_AVATAR_RADIUS_ROUNDED  = 0b0001;
    const BIO_AVATAR_RADIUS_CIRCULAR = 0b0010;

    public function __get_schema(): array {
        $this->__set_index_checkbox_state(true);
        return [
            "url_slug" => [
                new StringResult,
                'required' => true,
                'filter' => function ($val) {
                    $matches = [];
                    $filter = preg_match("/[\s]/", $val, $matches);
                    if($filter) throw new ValidationIssue("The url_slug must not contain invalid characters");
                    return $val;
                },
                'filter' => function ($val) {
                    if(!$val) throw new ValidationIssue("The URL Slug cannot be empty");
                    if($val[0] === "/") throw new ValidationIssue("The URL must not start with a slash.");
                    if(str_contains($val, " ")) throw new ValidationIssue("The URL must not contain spaces.");
                    update('#url_slug', ['href' => "/$val"]);
                    return $val;
                },
                'get_path' => function ($val) {
                    if($this instanceof PostMap) return __APP_SETTINGS__['Posts']['public_post'] . $val;
                    return __APP_SETTINGS__['LandingPage_route_prefix'] . $val;

                }
            ],
            // "h1" => [
            //     new StringResult,
            //     'display' => fn ($val) => "<h1>$val</h1>",
                
            // ],
            "title" => [
                new StringResult,
                'required' => true,
                'display' => fn ($val) => $val,
                'index' => [
                    'title' => 'Title',
                    'order' => 1,
                    'sort' => 1,
                ],
                'filter' => function ($val) {
                    update('#title', ['innerHTML' => $val]);
                    return $val;
                }
            ],
            'visibility' => [
                new EnumResult,
                'default' => self::VISIBILITY_PRIVATE,
                'valid' => [
                    self::VISIBILITY_PRIVATE => "Private",
                    self::VISIBILITY_DRAFT  => "Draft",
                    self::VISIBILITY_UNLISTED => "Unlisted",
                    self::VISIBILITY_PUBLIC => "Public",
                ],
                'filter' => function ($val) {
                    return (int)$val;
                },
                'index' => [
                    'title' => 'Visibility',
                    'order' => 2,
                    'view' => function () {
                        return match($this->visibility->getValue()) {
                            self::VISIBILITY_PRIVATE => "Private",
                            self::VISIBILITY_DRAFT  => "Draft",
                            self::VISIBILITY_UNLISTED => "Unlisted",
                            self::VISIBILITY_PUBLIC => "Public",
                            default => "Private"
                        };
                    }
                ],
            ],
            'live_date' => [
                new DateResult,
                'required' => true,
                'index' => [
                    'title' => "Live Date",
                    'order' => 3,
                ]
            ],
            
            "splash_image" => [
                new UploadImageResult,
                'alt' => function () {
                    return $this->title;
                }
            ],
            "splash_image_alignment" => [
                new ArrayResult,
                // 'default' => ['center'],
                'valid' => [
                    'center' => 'Center',
                    'top' => 'Top',
                    'right' => 'Right',
                    'bottom' => 'Bottom',
                    'left' => 'Left',
                ],
                'filter' => function ($val) {
                    $hasLeft = in_array('left', $val);
                    $hasRight = in_array('right', $val);
                    $hasTop = in_array('top', $val);
                    $hasBottom = in_array('bottom', $val);
                    $hasHorizontal = ($hasLeft || $hasRight);
                    $hasVertical = ($hasTop || $hasBottom);
                    if($hasLeft && $hasRight) throw new ValidationIssue("You cannot have 'left' and 'right' selected at the same time");
                    if($hasTop && $hasBottom) throw new ValidationIssue("You cannot have 'top' and 'bottom' selected at the same time");
                    if(in_array('center', $val) && $hasHorizontal && $hasVertical) {
                        throw new ValidationIssue("You cannot have 'center' selected while left/right AND top/bottom are also selected");
                    }
                    return $val;
                },
                'display' => function ($val) {
                    return "style=\"--primary-image--positioning: ".join(" ",$val ?? [])."\"";
                }
            ],
            "splash_type" => [
                new EnumResult,
                'default' => self::SPLASH_POSITION_FADE,
                'valid' => [
                    self::SPLASH_POSITION_FADE => "Fade (full width image, text over top)",
                    self::SPLASH_POSITION_CENTER => "Centered text over image",
                    self::SPLASH_POSITION_SPLIT => "Split (image on one half)",
                    self::SPLASH_POSITION_FLOAT => "Float (image is 25% of width of screen)",
                    self::SPLASH_POSITION_TWO_UP => "Two Up (fills normal content width)",
                ],
                'filter' => function ($val) {
                    $val = (int)$val;
                    if($val === self::SPLASH_POSITION_CENTER) update("[name='splash_reverse']", ['disabled' => true]);
                    else update("[name='splash_reverse']", ['disabled' => false]);
                    return $val;
                }
            ],
            "splash_reverse" => [
                new EnumResult,
                'default' => 'normal',
                'valid' => [
                    'normal' => 'Text on Right (bottom on mobile)',
                    'row-reverse' => 'Text on Left (top on mobile)',
                ]
            ],
            "subtitle" => [
                new MarkdownResult,
                'required' => true,
            ],
            "summary" => [
                new StringResult,
                'required' => true,
                'display' => function ($val) {
                    if(!$val) $val = $this->subtitle->getValue();
                    // if(!$val) {
                    //     foreach($this->body->getRaw()->blocks as $block) {
                    //         if($block->type !== "paragraph") continue;
                    //         return $block->data->text;
                    //     }
                    // }
                    return $val;
                },
                'filter' => function ($val) {
                    // if(!$val) {
                    //     foreach($this->body->getRaw()->blocks as $block) {
                    //         if($block->type !== "paragraph") continue;
                    //         return $block->data->text;
                    //     }
                    // }
                    if($val != strip_tags($val)) throw new ValidationIssue("The summary field must not contain HTML tags");
                    return $val;
                }
            ],
            "body" => [
                new BlockResult,
            ],

            "cta" => [
                new StringResult,
            ],
            "cta_href" => [
                new StringResult,
            ],

            /** ASIDE CONTENT */
            'include_aside' => [
                new BooleanResult,
            ],
            'aside_positioning' => [
                new BinaryResult,
                'default' => self::ASIDE_SIDEBAR_NATURAL + (__APP_SETTINGS__['LandingPage_table_of_contents_by_default']) ? self::ASIDE_INCLUDE_TOC_INDEX : 0,
                'valid' => [
                    self::ASIDE_SIDEBAR_NATURAL => 'Sidebar Left',
                    self::ASIDE_SIDEBAR_REVERSE => 'Sidebar Right',
                    self::ASIDE_SIDEBAR_FOOTER => 'Aside as Footer',
                    self::ASIDE_STICKY => 'Sticky',
                    self::ASIDE_INCLUDE_TOC_INDEX => 'Include Table of Contents',
                    self::ASIDE_INDEX_BEFORE_CONTENT => 'TOC Before Content',
                ]
            ],
            'aside' => [
                new BlockResult,
            ],


            /** RELATED CONTENT SETTINGS */
            'max_related' => [
                new NumberResult,
                'default' => 3,
            ],
            'related_title' => new StringResult,



            /** META CONTENT SETTINGS */
            "show_main_nav" => [
                new BooleanResult,
                'default' => false
            ],
            'opengraph_title' => [
                new StringResult,
                // 'fallback' => 
            ],
            "tags" => [
                new ArrayResult,
                'allow_custom' => true,
                'valid' => function () {
                    $man = new PageManager();
                    $results = $man->distinct("tags");
                    $array = [];
                    
                    foreach($results as $value) {
                        $array[$value] = $value;
                    }
                    return $array;
                },
                'nullable' => true
            ],
            'view' => [
                new EnumResult,
                'default' => 'default',
                'valid' => [
                    'default' => 'Default',
                    'landing' => 'Landing (Excludes Main Navigation)',
                ]
            ],            
            "style" => [
                new MarkdownResult,
            ],
            'flags' => [
                new BinaryResult,
                'valid' => [
                    self::FLAGS_REQUIRES_ACCOUNT => 'Access Exclusive to Users',
                    self::FLAGS_EXCLUDE_FROM_SITEMAP => "Exclude Page from Sitemap",
                    self::FLAGS_EXCLUDE_RELATED_PAGES => "Do Not Show Related Pages",
                ]
            ],
            'preview_key' => [
                new StringResult,
                'display' => function ($val) {
                    $name = server_name();
                    return $name.__APP_SETTINGS__['LandingPage_route_prefix'].$this->url_slug."?pkey=".$val;
                }
            ],

            /** BIOGRAPHY FIELDS */
            'author' => [
                new UserIdResult,
                'required' => true,
                'nullable' => true,
                'permission' => 'Pages_allowed_author',
                'default' => session('_id'),
            ],
            "include_bio" => [
                new BooleanResult,
                'default' => __APP_SETTINGS__['LandingPage_bio_by_default']
            ],
            'bio_headline' => [
                new StringResult,
            ],
            "bio" => [
                new BlockResult,
                'default' => function ($val) {
                    if(!$this->author) return "";
                    $user = $this->author->getValue();
                    return $user->biography;
                }
            ],
            "bio_cta" => [
                new StringResult,
                // "default" => function ($val) {
                //     if(!$val) return (string)$this->cta->getValue();
                //     return $val;
                // }
            ],
            "bio_flags" => [
                new BinaryResult,
                'valid' => [
                    self::BIO_AVATAR_RADIUS_ROUNDED => "Avatar Rounded",
                    self::BIO_AVATAR_RADIUS_CIRCULAR => "Avatar Circular*",
                ]
            ],
            
            /** ROUTE GROUP SETTINGS */
            'include_in_route_group' => [
                new BooleanResult,
                'default' => false,
            ],
            'route_group' => [
                new EnumResult,
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
            'route_link_label' => [
                new StringResult,
                // 'default' => fn ($val) => ($val) ? $val : (string)$this->title
            ],
            'route_order' => [
                new NumberResult,
                'default' => 999
            ],
            // 'opengraph_image' => [
            //     new UploadImageResult
            // ]
        ];
    }
}