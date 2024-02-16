<?php

namespace CobaltEvents;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\HrefResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;

class EventMap extends PersistanceMap {
    function __get_schema():array {
        return [
            'name' => [
                new StringResult,
                'required' => true
            ],
            'container_id' => [
                new StringResult,
                'set' => function ($val) {
                    if (!$val) $val = $this->__to_validate['name'];
                    return strtolower(preg_replace("/([\W\s_])/", "-", $val));
                }
            ],
            'headline' => [
                new StringResult,
                'required' => true
            ],
            'body' => [
                new MarkdownResult,
            ],
            'type' => [
                new EnumResult,
                'valid' => fn () => $this->allowed_event_types,
                'default' => 'banner'
            ],
            'session_policy' => [
                new EnumResult,
                'valid' => fn () => $this->allowed_session_policies,
                'default' => '24_hours'
            ],
            'session_policy_hours' => [
                new NumberResult,
                'default' => 12
            ],
            'call_to_action_prompt' => new StringResult,
            'call_to_action_href' => new HrefResult,
            'bgColor' => [
                new HexColorResult,
                'default' => __APP_SETTINGS__["vars-web.events-banner-background"]
            ],
            'txtColor' => [
                new HexColorResult,
                'default' => __APP_SETTINGS__["vars-web.events-banner-text"]
            ],
            'txtJustification' => [
                new EnumResult,
                'default' => __APP_SETTINGS__["CobaltEvents_default_h1_alignment"],
                'valid' => [
                    "space-between" => "<i name='format-align-left'></i>",
                    "center" => "<i name='format-align-center'></i>",
                    "flex-end" => "<i name='format-align-right'></i>"
                ]
            ],
            'btnColor' => [
                new HexColorResult,
                'default' => __APP_SETTINGS__["vars-web.events-button-color"],
            ],
            'btnTextColor' => [
                new HexColorResult,
                'get' => fn ($val, $ref) => ($ref->__reference->btnColor) ? $ref->__reference->btnColor->getContrastColor() : "#000000"
            ],
            'valid_paths' => new StringResult,
            'published' => new BooleanResult,
            'start_time' => new DateResult,
            'edit_time' => new DateResult,
            'advanced.included_paths' => new ArrayResult,
            'advanced.excluded_pahts' => new ArrayResult,
            'advanced.exclusive' => new BooleanResult,
            'advanced.delay' => new NumberResult,
            'changes_override' => new BooleanResult,
            'advanced.public_index' => [
                new EnumResult,
                'valid' => [
                    'false'  => 'Unlisted (default)',
                    'true'   => 'Displayed, if also marked as "Public"',
                    'always' => 'Displayed, regardless of "Public" status',
                ]
            ]
        ];
    }

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
}