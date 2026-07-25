<?php
namespace Cobalt\Pages\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayOfObjectsType;
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
use Cobalt\Pages\Controllers\LandingPages;
use Cobalt\Pages\Models\PostMap;
use Validation\Exceptions\ValidationIssue;

class PageMap extends Model {
    const FLAGS_INCLUDE_PERMALINK      = 0b00100000;
    const FLAGS_READ_TIME_MANUALLY_SET = 0b00010000;

    const FLAGS_REQUIRES_ACCOUNT       = 0b00000001;
    const FLAGS_EXCLUDE_FROM_SITEMAP   = 0b00000010;
    const FLAGS_EXCLUDE_RELATED_PAGES  = 0b00000100;
    const FLAGS_HIDE_VIEW_COUNT        = 0b00001000;
    const FLAGS_HIDE_WEBMENTIONS       = 0b01000000;

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

    const ASIDE_SIDEBAR_NATURAL      = 0b0000001;
    const ASIDE_SIDEBAR_REVERSE      = 0b0000010;
    const ASIDE_SIDEBAR_FOOTER       = 0b0000100;
    const ASIDE_STICKY               = 0b0001000;
    const ASIDE_INCLUDE_TOC_INDEX    = 0b0010000;
    const ASIDE_INDEX_BEFORE_CONTENT = 0b0100000;
    const ASIDE_INCLUDE_SOCIAL_SHARE = 0b1000000;

    const METADATA_FEDIVERSE_CREDIT_PUBLICATION = 0b0001;
    const METADATA_INCLUDE_FOOTER               = 0b0010;

    const BIO_AVATAR_RADIUS_ROUNDED  = 0b0001;
    const BIO_AVATAR_RADIUS_CIRCULAR = 0b0010;

    public function defineSchema(array $schema = []): array {
        /** VISIBLE CONTENT */
        $fields = [
            'url_slug' => [
                new StringType,
                'required' => true,
                'filter' => function ($val) {
                    if(!$val) throw new ValidationIssue("The URL Slug cannot be empty");
                    if($val[0] === "/") throw new ValidationIssue("The URL must not start with a slash.");
                    if(str_contains($val, " ")) throw new ValidationIssue("The URL must not contain spaces.");
                    update('#url_slug', ['href' => "/$val"]);
                    return $val;
                },
                'get_path' => function ($val) {
                    $permalink = ($this->flags->getValue() & self::FLAGS_INCLUDE_PERMALINK) ? "/$this->_id" : "";
                    if($this instanceof PostMap) return __APP_SETTINGS__['Posts_public_post'] . "$val" . $permalink;
                    return __APP_SETTINGS__['LandingPage_route_prefix'] . "$val" . $permalink;
                }
            ],
            'title' => [
                new StringType,
                'required' => true,
                'display' => fn($val) => $val,
                'index' => [
                    'title' => 'Title',
                    'order' => 1,
                    'searchable' => true
                ],
                'filter' => function ($val) {
                    update('#title', ['innerHTML' => $val]);
                    return $val;
                }
            ],
            'visibility' => [
                new EnumType,
                'default' => self::VISIBILITY_PRIVATE,
                'valid' => [
                    self::VISIBILITY_PRIVATE  => "Private",
                    self::VISIBILITY_DRAFT    => "Draft",
                    self::VISIBILITY_HIDDEN   => "Hidden",
                    self::VISIBILITY_UNLISTED => "Unlisted",
                    self::VISIBILITY_PUBLIC   => "Public",
                ],
                'filter' => function ($val) {
                    switch($val) {
                        case self::VISIBILITY_UNLISTED:
                        case self::VISIBILITY_PUBLIC:
                        case self::VISIBILITY_HIDDEN:
                            if(!has_permission("Posts_publish_posts", null, session(), false)) {
                                throw new ValidationIssue("Your account doesn't have permission to make a Public, Unlisted, or Hidden post");
                                break;
                            }
                    }
                    return (int)$val;
                },
                'index' => [
                    'title' => 'Visibility',
                    'order' => 2,
                    'view' => function ($val, $document) {
                        return match((int)$this->visibility->value) {
                            self::VISIBILITY_PRIVATE  => "Private",
                            self::VISIBILITY_DRAFT    => "Draft",
                            self::VISIBILITY_HIDDEN   => "Hidden",
                            self::VISIBILITY_UNLISTED => "Unlisted",
                            self::VISIBILITY_PUBLIC   => "Public",
                            default => "Unknown"
                        };
                    },
                    'filterable' => true,
                ],
            ],
            'live_date' => [
                new DateType,
                'required' => true,
                'index' => [
                    'title' => "Live Date",
                    'order' => 3,
                    'sort' => -1,
                ]
            ],
        ];

        /** MAIN CONTENT */
        $fields += [
            "splash_image" => [
                new ImageType,
                'alt' => function () {
                    return $this->title;
                }
            ],
            "splash_image_alignment" => [
                new ArrayType,
                'default' => ['center'],
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
                'filter' => function ($val) {
                    $val = (int)$val;
                    if($val === self::SPLASH_POSITION_CENTER) update("[name='splash_reverse']", ['disabled' => true]);
                    else update("[name='splash_reverse']", ['disabled' => false]);
                    return $val;
                }
            ],
            "splash_reverse" => [
                new EnumType,
                'default' => 'normal',
                'valid' => [
                    'normal' => 'Text on Right (bottom on mobile)',
                    'row-reverse' => 'Text on Left (top on mobile)',
                ]
            ],
            "subtitle" => [
                new MarkdownType,
                'required' => true,
            ],
            "summary" => [
                new StringType,
                'required' => true,
                'display' => function ($val) {
                    if(!$val) $val = $this->subtitle->value;
                    return $val;
                },
                'filter' => function ($val) {
                    if($val != strip_tags($val)) throw new ValidationIssue("The summary field must not contain HTML tags");
                    return $val;
                }
            ],
            "body" => [
                new BlockType,
                'filter' => function ($val) {
                    if($this->flags->and(self::FLAGS_READ_TIME_MANUALLY_SET)) return $val;
                    $block = new BlockType();
                    // This is a shitty hack.
                    $block->setValue(json_decode(json_encode($val)));
                    $this->time_to_read = $block->timeToRead();
                    update('input[name="time_to_read"]',['value' => $this->time_to_read]);
                    return $val;
                }
            ],
            "time_to_read" => [
                new StringType,
                'display' => function ($val) {
                    if(!$val) {
                        return $this->body->timeToRead();
                    }
                }
            ],
            "cta" => [
                new StringType,
            ],
            "cta_href" => [
                new StringType
            ],
        ];

        /** ASIDE CONTENT */
        $fields += [
            'include_aside' => [
                new BooleanType,
            ],
            'aside_positioning' => [
                new BinaryType,
                'default' => self::ASIDE_SIDEBAR_NATURAL + (__APP_SETTINGS__['LandingPage_table_of_contents_by_default']) ? self::ASIDE_INCLUDE_TOC_INDEX : 0,
                'valid' => [
                    self::ASIDE_SIDEBAR_NATURAL => 'Sidebar Left',
                    self::ASIDE_SIDEBAR_REVERSE => 'Sidebar Right',
                    self::ASIDE_SIDEBAR_FOOTER  => 'Aside as Footer',
                    self::ASIDE_STICKY          => 'Sticky',
                    self::ASIDE_INCLUDE_TOC_INDEX    => 'Include Table of Contents',
                    self::ASIDE_INDEX_BEFORE_CONTENT => 'TOC Before Content',
                ]
            ],
            'aside' => [
                new BlockType,
            ],
        ];

        /** RELATED CONTENT SETTINGS */
        $fields += [
            'max_related' => [
                new NumberType,
                'default' => 3,
            ],
            'related_title' => new StringType,
            // 'related_posts' => [
            //     new ArrayOfObjectsType,
            // ]
        ];

        /** POST SETTINGS */
        $fields += [
            "show_main_nav" => [
                new BooleanType,
                'default' => false
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
            "style" => [
                new MarkdownType,
            ],
            'view' => [
                new EnumType,
                'default' => 'default',
                'valid' => [
                    'default' => 'Default',
                    'landing' => 'Landing (Excludes Main Navigation)',
                ]
            ],
            /** ROUTE GROUP SETTINGS */
            'include_in_route_group' => [
                new BooleanType,
                'default' => false,
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
            'route_link_label' => [
                new StringType,
                // 'default' => fn ($val) => ($val) ? $val : (string)$this->title
            ],
            'route_order' => [
                new NumberType,
                'default' => 999
            ],
        ];

        /** META CONTENT SETTINGS */
        $fields += [
            'opengraph_title' => [
                new StringType,
                // 'fallback' => 
            ],
            'opengraph_image' => [
                new ImageType
            ],

            "tags" => [
                new ArrayType,
                'allow_custom' => true,
                'valid' => function () {
                    return $this->get_tags();
                },
                'filter' => function ($tags) {
                    $lowercase = [];
                    foreach($tags as $tag) {
                        $lowercase[] = strtolower($tag);
                    }
                    return array_unique($lowercase);
                },
                'nullable' => true,
                'index' => [
                    'title' => 'Tags',
                    'view' => function () {
                        return $this->tags->join(", ");
                    },
                    'searchable' => true
                ]
            ],
            "metadata_flags" => [
                new BinaryType,
                'valid' => [
                    self::METADATA_FEDIVERSE_CREDIT_PUBLICATION => "Credit Publication on Fediverse",
                    self::METADATA_INCLUDE_FOOTER => "Include tag links in post footer",
                ],
                'default' => (__APP_SETTINGS__['LandingPages_include_footer_by_default']) ? self::METADATA_INCLUDE_FOOTER : 0,
            ],
        ];

        /** AUTHOR SETTINGS */
        $fields += [
            /** BIOGRAPHY FIELDS */
            'author' => [
                new UserIdType,
                'required' => true,
                'nullable' => true,
                'permission' => 'Pages_allowed_author',
                'default' => session('_id'),
                'index' => [
                    'title' => 'Author',
                    'order' => 9,
                    'view' => function () {
                        if(!$this->author) return "";
                        return "";
                        // return $this->author->get_name("full");
                    },
                    'filterable' => true,
                ]
            ],
            "include_bio" => [
                new BooleanType,
                'default' => __APP_SETTINGS__['LandingPage_bio_by_default']
            ],
            'bio_headline' => [
                new StringType,
            ],
            "bio" => [
                new BlockType,
                'default' => function ($val) {
                    if(!$this->author) return "";
                    $user = $this->author->getValue();
                    return $user->biography;
                }
            ],
            "bio_cta" => [
                new StringType,
                // "default" => function ($val) {
                //     if(!$val) return (string)$this->cta->getValue();
                //     return $val;
                // }
            ],
            "bio_flags" => [
                new BinaryType,
                'valid' => [
                    self::BIO_AVATAR_RADIUS_ROUNDED => "Avatar Rounded",
                    self::BIO_AVATAR_RADIUS_CIRCULAR => "Avatar Circular*",
                ]
            ],
        ];

        /** SYSTEM FIELDS */
        $fields += [
            'views' => [
                new NumberType,
                'default' => 0,
                'index' => [
                    'title' => 'Views',
                    'order' => 3,
                    'view' => function ($val) {
                        $val = $this->views->getValue();
                        if(has_permission("Posts_enable_privileged_fields", null, null, false)) return "<strong>".$val . "</strong> (". ($val - $this->bot_hits->getValue()) .")";
                        return $val;
                    }
                ]
            ],
            'bot_hits' => [
                new NumberType,
                'default' => 0,
                'index' => [
                    'title' => 'Bots',
                    'order' => 4
                ]
            ],
            "token" => new StringType,
            'preview_key' => [
                new StringType,
                'display' => function ($val) {
                    $name = server_name();
                    return $name.$this->url_slug->get_path()."?pkey=".$val;
                }
            ],
        ];

        return $fields;
    }

    public function defineController(): ModelController {
        return new LandingPages();
    }

    public static function __getVersion(): string {
        return "2.0";
    }

    public function getCollectionName($string = null): string {
        return COBALT_PAGES_DEFAULT_COLLECTION;
    }

}