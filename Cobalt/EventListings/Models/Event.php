<?php

namespace Cobalt\EventListings\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\EventListings\Classes\CalendarEvent;
use Cobalt\EventListings\Controllers\Events;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\HexColorType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Event extends Model {

    protected $allowed_event_types = [
        'banner' => [
            'value' => "Banner",
            'exclude' => "[name='body']",
        ],
        'modal'  => [
            'value' => "Modal pop-up",
            'exclude' => "",
        ],
    ];
    protected $allowed_session_policies = [
        '24_hours'     => [
            'value' => 'After 24+ hours',
            'exclude' => "[name='session_policy_hours']",
        ],
        '12_hours'     => [
            'value' => 'After 12+ hours',
            'exclude' => "[name='session_policy_hours']",
        ],
        'hours' => 'After [n]+ hours',
        'with_session' => [
            'value' => 'After closing tab (session)',
            'exclude' => "[name='session_policy_hours']",
        ],
        'half_date'    => [
            'value' => 'Half time between close and event end',
            'exclude' => "[name='session_policy_hours']",
        ],
        'nag'          => [
            'value' => 'On every page (not recommended)',
            'exclude' => "[name='session_policy_hours']",
        ],
        'never'        => [
            'value' => 'Never show event again',
            'exclude' => "[name='session_policy_hours']",
        ],
    ];

    const POPUP_TXT_JUSTIFICATION = [
        "space-between" => "<i name='format-align-left'></i> Left Justified<br><small>Text content will be justified to the left of the pop-up</small>",
        "center" => "<i name='format-align-center'></i> Center Justified<br><small>Text content will be justified to the center of the pop-up</small>",
        "flex-end" => "<i name='format-align-right'></i> Right Justified<br><small>Text content will be justified to the right of the pop-up</small>"
    ];

    const INDEX_UNLISTED = 'false';
    const INDEX_IFPUBLIC = 'true';
    const INDEX_ALWAYS =   'always';

    public function defineController(): ModelController {
        return new Events();
    }

    public function defineSchema(array $schema = []): array {
        $this->__showCheckboxes(true);
        return [
            'event_name' => [
                new StringType,
                'required' => true,
                'index' => [
                    'title' => 'Event Name'
                ]
            ],
            'container_id' => [
                new StringType,
                'filter' => function ($val) {
                    if (!$val) $val = $this->__to_validate['name'];
                    return strtolower(preg_replace("/([\W\s_])/", "-", $val));
                }
            ],
            'headline' => [
                new StringType,
                'required' => true
            ],
            'body' => [
                new MarkdownType,
            ],
            'location' => [
                new StringType
            ],
            'type' => [
                new EnumType,
                'valid' => fn () => $this->allowed_event_types,
                'default' => 'banner',
                'index' => [
                    'title' => 'Type',
                    'filterable' => true
                ]
            ],
            'session_policy' => [
                new EnumType,
                'valid' => fn () => $this->allowed_session_policies,
                'default' => '24_hours'
            ],
            'session_policy_hours' => [
                new NumberType,
                'default' => 12
            ],
            'call_to_action_prompt' => new StringType,
            'call_to_action_href' => new StringType,
            'bgColor' => [
                new HexColorType,
                'label' => 'Background Color',
                'default' => __APP_SETTINGS__["color_neutral"]
            ],
            'txtColor' => [
                new HexColorType,
                'label' => 'Text Color',
                'default' => __APP_SETTINGS__["vars-web.events-banner-text"]
            ],
            'txtJustification' => [
                new EnumType,
                'default' => __APP_SETTINGS__["CobaltEvents_default_h1_alignment"],
                'valid' => static::POPUP_TXT_JUSTIFICATION
            ],
            'btnColor' => [
                new HexColorType,
                'label' => 'Button Background Color',
                'default' => __APP_SETTINGS__["color_primary"],
            ],
            'btnTextColor' => [
                new HexColorType,
                'label' => 'Button Text Color',
                'get' => fn ($val, $ref) => ($ref->__reference->btnColor) ? $ref->__reference->btnColor->getContrastColor() : "#000000"
            ],
            'valid_paths' => new StringType,
            'published' => new BooleanType,
            'popup_date' => [
                new DateType,
                'label' => 'Pop-up Start Date',
            ],
            'start_date' => [
                new DateType,
                'label' => 'Event Start Date',
                'required' => true
            ],
            'end_date' => [
                new DateType,
                'label' => 'Event End Date',
                'required' => true
            ],
            'advanced' => [
                new ModelType,
                'schema' => [
                    'included_paths' => [
                        new ArrayType,
                        'allow_custom' => true
                    ],
                    'excluded_paths' => [
                        new ArrayType,
                        'allow_custom' => true
                    ],
                    'exclusive' => new BooleanType,
                    'delay' => new NumberType,
                ]
            ],
            'changes_override' => [
                new BooleanType,
                'default' => true
            ],
            'public_index' => [
                new EnumType,
                'valid' => [
                    static::INDEX_UNLISTED  => 'Unlisted (default)',
                    static::INDEX_IFPUBLIC  => 'Displayed, if also marked as "Public"',
                    static::INDEX_ALWAYS     => 'Displayed, regardless of "Public" status',
                ],
                'default' => __APP_SETTINGS__["CobaltEvents_default_public_listing_status"]
            ],
            'public_head' => [
                new StringType,
            ],
            'public_body' => new BlockType,
            'public_image' => new ImageType,
        ];
    }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function getCollectionName($string = null): string {
        return "EventListings";
    }
    const PUBLIC_LISTING_OR_QUERY = [
        [
            'public_index' => Event::INDEX_IFPUBLIC,
            'published' => true,
        ],
        [
            'public_index' => Event::INDEX_ALWAYS
        ]
    ];

    public function getPublicListing() {
        $now = new UTCDateTime();
        return $this->find(
            // '_id' => new ObjectId('68798de5215856ee830134b2')
            // 'start_time' => ['$lte' => $now],
            // 'end_time' => ['$gte' => $now],
            // '$or' => [
            //     ['advanced.public_index' => 'true', 'published' => true],
            //     ['advanced.public_index' => 'always']
            // ]
            [
                'end_date' => ['$gte' => $now],
                '$or' => static::PUBLIC_LISTING_OR_QUERY
            ], [
                'sort' => [
                    'start_date' => 1,
                    'end_date' => -1
                ]
            ]
        );
    }

    public function getUrlPath():string {
        return route("Cobalt\\EventListings\\Controllers\\Events@public_listing", [(string)$this->_id]);
    }

    public function getICalEvent():CalendarEvent {
        $cal = new CalendarEvent($this->start_date, $this->end_date, $this->_id);
        $cal->set_name($this->getName());
        $cal->set_description($this->getBody());
        $cal->set_location($this->location->value ?? "");
        $cal->set_url(server_name().$this->getUrlPath());
        return $cal;
    }

    public function getName() {
        return $this->public_name->value ?? $this->headline->value;
    }

    public function getBody() {
        return ($this->public_body->length()) ? $this->public_body->display() : $this->body->md();
    }
}