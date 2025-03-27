<?php

namespace Cobalt\EventListings\Models;

use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\HexColorType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
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

    public function defineSchema(array $schema = []): array {
        return [
            'name' => [
                new StringType,
                'required' => true,
                'index' => [
                    'title' => 'Name'
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
                new StringType,
            ],
            'type' => [
                new EnumType,
                'valid' => fn () => $this->allowed_event_types,
                'default' => 'banner'
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
                'default' => __APP_SETTINGS__["vars-web.events-banner-background"]
            ],
            'txtColor' => [
                new HexColorType,
                'default' => __APP_SETTINGS__["vars-web.events-banner-text"]
            ],
            'txtJustification' => [
                new EnumType,
                'default' => __APP_SETTINGS__["CobaltEvents_default_h1_alignment"],
                'valid' => [
                    "space-between" => "<i name='format-align-left'></i>",
                    "center" => "<i name='format-align-center'></i>",
                    "flex-end" => "<i name='format-align-right'></i>"
                ]
            ],
            'btnColor' => [
                new HexColorType,
                'default' => __APP_SETTINGS__["vars-web.events-button-color"],
            ],
            'btnTextColor' => [
                new HexColorType,
                'get' => fn ($val, $ref) => ($ref->__reference->btnColor) ? $ref->__reference->btnColor->getContrastColor() : "#000000"
            ],
            'valid_paths' => new StringType,
            'published' => new BooleanType,
            'start_date' => new DateType,
            'end_date' => new DateType,
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
                    'public_index' => [
                        new EnumType,
                        'valid' => [
                            'false'  => 'Unlisted (default)',
                            'true'   => 'Displayed, if also marked as "Public"',
                            'always' => 'Displayed, regardless of "Public" status',
                        ]
                    ],
                ]
            ],
            // 'changes_override' => new BooleanType,
            // 'public' => [
            //     new ModelType,
            //     'schema' => [
            //         'body' => new BlockType,
            //         // 'image' => new 
            //     ],
            // ]
        ];
    }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function getCollectionName($string = null): string {
        return "EventListings";
    }
    
    public function getPublicListing() {
        return $this->find([
            // 'start_time' => ['$lte' => $this->__date()],
            'end_time' => ['$gte' => new UTCDateTime()],
            '$or' => [
                ['advanced.public_index' => 'true', 'published' => true],
                ['advanced.public_index' => 'always']
            ]
        ]);
    }
}