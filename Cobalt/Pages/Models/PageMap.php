<?php

namespace Cobalt\Pages\Models;

use Cobalt\Maps\GenericMap;
use Cobalt\Maps\PersistanceMap;
use Cobalt\Pages\Classes\PageManager;
use Cobalt\Pages\Classes\PostManager;
use Cobalt\Renderer\Exceptions\TemplateException;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\BlockResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\ImageResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use Controllers\Traits\Indexable;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use Drivers\Database;
use Exception;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\UTCDateTime;
use Traversable;
// use Webmention\Server;
use Webmention\WebmentionDocument;
use Webmention\WebmentionHandler;

class PageMap extends PersistanceMap implements WebmentionDocument {
    const VIEW_TYPE = [
        'default' => '/Cobalt/Pages/templates/views/default.html',
        'landing' => '/Cobalt/Pages/templates/views/landing.html',
    ];
    
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

    const FLAGS_REQUIRES_ACCOUNT       = 0b00000001;
    const FLAGS_EXCLUDE_FROM_SITEMAP   = 0b00000010;
    const FLAGS_EXCLUDE_RELATED_PAGES  = 0b00000100;
    const FLAGS_HIDE_VIEW_COUNT        = 0b00001000;
    const FLAGS_READ_TIME_MANUALLY_SET = 0b00010000;
    const FLAGS_INCLUDE_PERMALINK      = 0b00100000;
    const FLAGS_HIDE_WEBMENTIONS       = 0b01000000;

    const ASIDE_SIDEBAR_NATURAL      = 0b0000001;
    const ASIDE_SIDEBAR_REVERSE      = 0b0000010;
    const ASIDE_SIDEBAR_FOOTER       = 0b0000100;
    const ASIDE_STICKY               = 0b0001000;
    const ASIDE_INCLUDE_TOC_INDEX    = 0b0010000;
    const ASIDE_INDEX_BEFORE_CONTENT = 0b0100000;
    const ASIDE_INCLUDE_SOCIAL_SHARE = 0b1000000;

    const BIO_AVATAR_RADIUS_ROUNDED  = 0b0001;
    const BIO_AVATAR_RADIUS_CIRCULAR = 0b0010;

    const METADATA_FEDIVERSE_CREDIT_PUBLICATION = 0b0001;
    const METADATA_INCLUDE_FOOTER               = 0b0010;

    const WEBMENTION_UPDATE_TIMEOUT = 60 * 2; // Two minute lock

    private ?array $mentions = null;

    public function __get_schema(): array {
        $this->__set_index_checkbox_state(true);
        $schema = [
            "url_slug" => [
                new StringResult,
                'required' => true,
                // 'filter' => function ($val) {
                //     $matches = [];
                //     $filter = preg_match("/[\s]/", $val, $matches);
                //     if($filter) throw new ValidationIssue("The url_slug must not contain invalid characters");
                //     return $val;
                // },
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
                    'searchable' => true,
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
                    self::VISIBILITY_HIDDEN => "Hidden",
                    self::VISIBILITY_UNLISTED => "Unlisted",
                    self::VISIBILITY_PUBLIC => "Public",
                ],
                'filter' => function ($val) {
                    switch($val) {
                        case self::VISIBILITY_UNLISTED:
                        case self::VISIBILITY_PUBLIC:
                            if(!has_permission("Posts_publish_posts", null, session(), false)) {
                                throw new ValidationIssue("Your account doesn't have permission to make a Public or Unlisted post");
                                break;
                            }
                    }
                    return (int)$val;
                },
                'index' => [
                    'title' => 'Visibility',
                    'order' => 2,
                    'view' => function ($val, $document) {
                        return match((int)$this->visibility->getValue()) {
                            self::VISIBILITY_PRIVATE => "Private",
                            self::VISIBILITY_DRAFT  => "Draft",
                            self::VISIBILITY_UNLISTED => "Unlisted",
                            self::VISIBILITY_PUBLIC => "Public",
                            default => "Unknown"
                        };
                    },
                    'filterable' => true,
                ],
            ],
            'live_date' => [
                new DateResult,
                'required' => true,
                'index' => [
                    'title' => "Live Date",
                    'order' => 3,
                    'sort' => -1,
                ]
            ],
            'views' => [
                new NumberResult,
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
                new NumberResult,
                'default' => 0,
                'index' => [
                    'title' => 'Bots',
                    'order' => 4
                ]
            ],
            "splash_image" => [
                new ImageResult,
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
                'filter' => function ($val) {
                    if($this->flags->and(self::FLAGS_READ_TIME_MANUALLY_SET)) return $val;
                    $block = new BlockResult();
                    // This is a shitty hack.
                    $block->setValue(json_decode(json_encode($val)));
                    $this->time_to_read = $block->timeToRead();
                    update('input[name="time_to_read"]',['value' => $this->time_to_read]);
                    return $val;
                }
            ],
            "time_to_read" => [
                new StringResult,
                'display' => function ($val) {
                    if(!$val) {
                        return $this->body->timeToRead();
                    }
                }
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
                new BinaryResult,
                'valid' => [
                    self::METADATA_FEDIVERSE_CREDIT_PUBLICATION => "Credit Publication on Fediverse",
                    self::METADATA_INCLUDE_FOOTER => "Include tag links in post footer",
                ],
                'default' => (__APP_SETTINGS__['LandingPages_include_footer_by_default']) ? self::METADATA_INCLUDE_FOOTER : 0,
            ],
            "token" => new StringResult,
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
                    self::FLAGS_HIDE_VIEW_COUNT => "Hide View Count",
                    self::FLAGS_READ_TIME_MANUALLY_SET => "Read Time Manually Set",
                    self::FLAGS_INCLUDE_PERMALINK => "Include Permalink in URL",
                    self::FLAGS_HIDE_WEBMENTIONS => "Hide Webmention Interactions",
                ],
                'default' => (__APP_SETTINGS__['LandingPages_show_related']) ? self::FLAGS_EXCLUDE_RELATED_PAGES : 0
            ],
            'preview_key' => [
                new StringResult,
                'display' => function ($val) {
                    $name = server_name();
                    return $name.$this->url_slug->get_path()."?pkey=".$val;
                }
            ],

            /** BIOGRAPHY FIELDS */
            'author' => [
                new UserIdResult,
                'required' => true,
                'nullable' => true,
                'permission' => 'Pages_allowed_author',
                'default' => session('_id'),
                'index' => [
                    'title' => 'Author',
                    'order' => 9,
                    'view' => function () {
                        return $this->author->get_name("full");
                    },
                    'filterable' => true,
                ]
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
                new ArrayResult,
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
        try {
            if($GLOBALS['auth'] && !has_permission("Posts_enable_privileged_fields", null, null, false)) {
                unset($schema['bot_hits']['index']);
                $schema['bot_hits']['readonly'] = true;
            }
        } catch (\Error $e) {
            return $schema;
        }
        return $schema;
    }

    static function __get_version(): string {
        return "1.1";
    }

    function get_tags() {
        $man = $this->__get_manager();
        $results = $man->distinct("tags", [], ['limit' => 1000]);
        $array = [];
        foreach($results as $value) {
            $array[$value] = $value;
        }
        $predefined_tags = __APP_SETTINGS__['PageMap_predefined_tags'];
        if($this instanceof PostMap) $predefined_tags = __APP_SETTINGS__['PostMap_predefined_tags'];
        return array_merge($array, $predefined_tags ?? []);
    }

    #[Prototype]
    protected function get_follow_link() {
        $follow_link = "";
        if($this instanceof PostMap == false) return $follow_link;
        if(__APP_SETTINGS__['Posts_enable_rss_feed']) {
            $follow_link = " &middot; <action-menu class=\"rss-feed-link button\" title=\"Follow\" icon=\"rss\">";
            $follow_link.= "&nbsp;Follow";
            $follow_link.= "<option onclick=\"copyToClipboard('".server_name().route("\\Cobalt\\Pages\\Controllers\\Posts@rss_feed")."', 'Copied the link to your clipboard. Now paste this into your favorite RSS reader!')\" target=\"_blank\" icon=\"rss\">RSS Feed<br><small style=\"font-weight: normal;display: block;white-space: pre-wrap;\">This will copy our RSS feed link to your clipboard. You can then paste the link into your favorite RSS reader!</small></option>";

            $socials = ["SocialMedia_email","SocialMedia_mastodon","SocialMedia_facebook","SocialMedia_instagram","SocialMedia_twitter"];
            foreach($socials as $platform) {
                if(!__APP_SETTINGS__[$platform]) continue;
                $platformName = str_replace("SocialMedia_", "", $platform);
                $follow_link.= "<option href=\"".__APP_SETTINGS__[$platform]."\" target=\"_blank\" icon=\"$platformName\">".ucwords($platformName)."</option>";
            }
            $follow_link.= "</action-menu>";
        }
        return $follow_link;
    }

    #[Prototype]
    protected function get_byline_meta($linked_date = false, $mentions = true) {
        if($this instanceof PostMap == false) return '';
        $mentionCount = 0;
        if($mentions) {
            try {
                $mentionCount = $this->get_webmention_details()['repostCount'];
            } catch (Exception $e) {
                $mentionCount = "";
            }
        }

        $html = "<div class=\"post-details\">";
        $ttr = $this->time_to_read->getValue();
        $html .= ($ttr) ? "$ttr read &middot; " : "";
        $html .= ($this->flags->and(self::FLAGS_HIDE_VIEW_COUNT)) ? "" : pretty_rounding($this->views->getValue() + 1) . " view".plural($this->views->getValue() + 1)." &middot; ";
        $html .= ($mentionCount) ? "<span title=\"$mentionCount share".plural($mentionCount)." across the web\"><i name=\"repeat-variant\"></i> $mentionCount &middot; </span>" : "";
        $html .= "<date>";
        if($linked_date) $html .= "<a href=\"".$this->url_slug->get_path()."\">".$this->live_date->relative("datetime")."</a>";
        else $html .= $this->live_date->relative("datetime");
        $html .= "</date>";
        return $html . "</div>";
    }

    #[Prototype]
    protected function get_author_meta_tags() {
        
    }

    /**
     * 
     * @return array{replyCount:int,reply:string,likeCount:int,like:string,repostCount:int,repost:string}
     * @throws Exception 
     * @throws NotFound 
     * @throws TemplateException 
     */
    public function get_webmention_details() {
        $empty = [
            'replyCount' => 0,
            'reply' => "",
            'likeCount' => 0,
            'like' => "",
            'repostCount' => 0,
            'repost' => "",
        ];
        if($this->flags->and(self::FLAGS_HIDE_WEBMENTIONS) === self::FLAGS_HIDE_WEBMENTIONS) return $empty;
        if($this->mentions !== null) return $this->mentions;
        $mentionManger = new WebmentionHandler();
        $path = parse_url($this->webmention_get_canonincal_url(), PHP_URL_PATH);
        
        $mentions = $mentionManger->findBySlug($path, ['limit' => 100]);
        $this->mentions = $empty;
        foreach($mentions as $mention) {
            if($mention->isReply())  {
                $this->mentions['reply'] .= view('/parts/webmentions/reply.html',['mention' => $mention]);
                $this->mentions['replyCount'] += 1;
            }
            if($mention->isLike())   {
                $this->mentions['like'] .= view('/parts/webmentions/like.html',['mention' => $mention]);
                $this->mentions['likeCount'] += 1;
            }
            if($mention->isRepost()) {
                $this->mentions['repost'] .= view('/parts/webmentions/repost.html',['mention' => $mention]);
                $this->mentions['repostCount'] += 1;
            }
        }
        return $this->mentions;
    }

    function __set_manager(?Database $manager = null):?Database {
        return new PageManager();
    }

    private function get_linkback_urls(&$urlsToLinkback, $item) {
        if($item->type === "paragraph") {
            if(is_iterable($item->data->links)) array_push($urlsToLinkback, ...$item->data->links);
        } else if($item->type === "linktool") {
            array_push($urlsToLinkback, $item->data->link);
        }
    }

    public function webmention_get_urls_to_notify(): array {
        $urlsToLinkback = [];
        foreach($this->body->getValue()->blocks as $key => $item) {
            $this->get_linkback_urls($urlsToLinkback, $item);
        }

        foreach($this->aside->getValue()->blocks as $item) {
            $this->get_linkback_urls($urlsToLinkback, $item);
        }

        foreach($this->bio->getValue()->blocks as $item) {
            $this->get_linkback_urls($urlsToLinkback, $item);
        }
        return $urlsToLinkback;
    }

    // public function webmention_get_completed_urls(): array {
    //     return $this->completedUrls->getArrayCopy();
    // }

    // public function webmention_set_completed_urls(array $completed): void {
    //     $this->__manager->updateOne(['_id' => $this->id], ['$set' => ['completedUrls' => $completed]]);
    // }

    public function webmention_get_canonincal_url(): string {
        $host = server_name();
        if($this->__manager instanceof PostManager) {
            return $host . route("\\Cobalt\\Pages\\Controllers\\Posts@page", [$this->url_slug->get_path()]);
        }
        return $host . route("\\Cobalt\\Pages\\Controllers\\LandingPages@page", [$this->url_slug->get_path()]);
    }

    function webmention_lock():void {
        $this->__manager->updateOne(['_id' => $this->id],['$set' => ['__webmentionLock' => new UTCDateTime()]]);
    }

    function webmention_unlock():void {
        $this->__manager->updateOne(['_id' => $this->id],['$set' => ['__webmentionLock' => false]]);
    }

    function webmention_is_locked():bool {
        if($this->visibility->getValue() <= self::VISIBILITY_DRAFT) return true;
        $val = $this->__webmentionLock;
        // If we're simply a boolean result (normally false)
        if($val instanceof BooleanResult) return $val->getValue();
        // If we've got this far and we're not a date result, we'll assume we're
        // not locked.
        if($val instanceof DateResult === false) return false;
        $val = $val->getSeconds();
        $now = time();
        // We want to accomodate slow responses, so we will time out locking
        // this entry at (2 min). If elapsed time since lock is greater than or
        // equal to timeout, return false
        if($now - $val >= self::WEBMENTION_UPDATE_TIMEOUT) return false;
        return true;
    }
}
