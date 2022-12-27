<?php

namespace CobaltEvents;

use Validation\Exceptions\ValidationIssue;

class EventSchema extends \Validation\Normalize {

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


    public function __get_schema(): array {
        return [
            'name' =>  [ // An name for internal purposes
                'get' => fn ($val) => $val,
                'set' => function ($val) {
                    // $val = $this->sanitize($val);
                    $val = $this->required_field($val);
                    return $val;
                }
            ],
            'container_id' => [
                'set' => function ($val) {
                    if (!$val) $val = $this->__to_validate['name'];
                    return strtolower(preg_replace("/([\W\s_])/", "-", $val));
                }
            ],
            'headline' => [ // An external name
                'set' => function ($val) {
                    // $val = $this->sanitize($val);
                    $val = $this->required_field($val);
                    return $val;
                }
            ],
            'body' => [ // The body content of the user's input
            ],
            'type' => [ // The type of modal
                'valid' => fn () => $this->allowed_event_types,
                'get' => fn ($val) => $val ?? 'banner',
                'set' => function ($val) {
                    $valid = array_keys($this->__schema['type']['valid']($val));
                    if (in_array($val, $valid)) return $val;
                    throw new ValidationIssue("Type must be valid");
                }
            ],
            'session_policy' => [
                'get' => fn ($val) => $val ?? '24_hours',
                'set' => function ($val) {
                    $valid = array_keys($this->__schema['session_policy']['valid']());
                    if (!in_array($val, $valid)) throw new ValidationIssue("Invalid session policy");
                    return $val;
                },
                'valid' => fn () => $this->allowed_session_policies
            ],
            'session_policy_hours' => [
                'get' => fn ($val) => $val ?? 12,
                'set' => function ($val) {
                    return clamp(filter_var($val, FILTER_VALIDATE_INT), 1, 1000);
                }
            ],
            'call_to_action_prompt' => [
                'set' => 'sanitize'
            ],
            'call_to_action_href' => [
                'set' => function ($val) {
                    if (!$val) return null;
                    if (!filter_var($val, FILTER_VALIDATE_DOMAIN)) {
                        throw new ValidationIssue("Malformed URL or pathname");
                    }
                    return $val;
                }
            ],
            "bgColor" => [
                'get' => fn ($val) => ($val) ? $val : app("vars-web.events-banner-background"),
                'set' => 'hex_color',
                'display' => fn ($val) => $this->hex_color($val, app("vars-web.events-banner-background")),
            ],
            "txtColor" =>  [
                'get' => fn ($val) => ($val) ? $val : app("vars-web.events-banner-text"),
                'set' => function ($val) {
                    return $this->contrast_color($val, $this->__dataset['bgColor']);
                },
                'display' => fn ($val) => $this->hex_color($val ?? app("vars-web.events-banner-text"), app("vars-web.events-banner-text")),
            ],
            'btnColor' =>  [
                'get' => fn ($val) => ($val) ? $val : app("vars-web.events-button-color"),
                'set' => function ($val) {
                    return $val;
                },
                'display' => function ($val) {
                    return $this->hex_color($val ?? app("vars-web.events-button-color"),app("vars-web.events-button-color"));
                    // ($val) ? $this->hex_color($val ?? app("vars-web.events-button-color"), app("vars-web.events-button-color")) : ""
                },
            ],
            'btnTextColor' => [
                'get' => fn ($val) => $val,
                'set' => function ($val) {
                    $color = $this->get_best_contrast($this->btnColor);
                    return $color;
                },
                'display' => fn ($val) => ($val) ? $this->hex_color($val ?? app("vars-web.events-button-text"), app("vars-web.events-button-text")) : "",
            ],
            'valid_paths' => [
                'get' => fn ($val) => $val ?? ["/"],
                'set' => function ($vals) {
                    foreach ($vals as $val) {
                        if (!filter_var($val, FILTER_VALIDATE_DOMAIN)) {
                            throw new ValidationIssue("An invalid URL was detected");
                        }
                    }
                    return $vals;
                }
            ],
            'published' => [
                'set' => 'boolean_helper'
            ],
            'start_date' => [
                'get' => fn () => $this->get_date($this->__dataset['start_time'],'input'),
                'set' => false,
            ],
            'start_time' => [
                'get' => 'get_time',
                'set' => fn ($val) => $this->set_time($val, 'start')
            ],
            'end_date' => [
                'get' => fn () => $this->get_date($this->__dataset['end_time'],'input'),
                'set' => false
            ],
            'end_time' => [
                'get' => 'get_time',
                'set' => fn ($val) => $this->set_time($val, 'end')
            ],
            'advanced.included_paths' => [
                'set' => 'relative_pathnames',
                'valid' => fn ($val) => $this->filled($val)
            ],
            'advanced.excluded_paths' => [
                'get' => fn ($val = null) => $val ?? ['/admin'],
                'set' => 'relative_pathnames',
                'valid' => fn ($val) => $this->filled($val)
            ],
            'advanced.exclusive' => [
                'get' => fn ($val) => $val ?? false,
                'set' => 'boolean_helper'
            ],
            "advanced.delay" => [
                'get' => fn ($val) => $val ?? 10,
                'set' => fn ($val) => $this->min_max($val, 0, 90)
            ],
            "changes_override" => [
                'get' => fn ($val) => $val,
                'set' => fn ($val) => $this->boolean_helper($val)
            ]
        ];
    }

    function __merge_private_fields($mutant): array {
        return [
            'last_updated_on' => $this->make_date(),
            'last_updated_by' => session('_id')
        ];
    }

    // function get_date($val) {
    //     if (!$this->__dataset[$val]) return "";
    //     return mongo_date($this->__dataset[$val]);
    // }

    function set_time($val, $type) {
        $this->date_sanity_check($val, 'start_date', 'start_time', 'end_time', 'end_date');
        $date = $type . "_date";
        return $this->set_date_time($date, $val);
    }

    function get_time($val, $name) {
        return mongo_date($val, 'H:i');
    }

    function sanitize_elements($val) {
        $mutant = [];
        foreach ($val as $i => $v) {
            $mutant[$i] = $this->sanitize($v);
        }
        return $mutant;
    }

    function relative_pathnames($val) {
        $mutant = [];
        foreach ($val as $i => $v) {
            $mutant[$i] = $this->remove_junk($v);
        }
        return $mutant;
    }

    private function remove_junk($v) {
        $m = preg_replace("/^(https?:\/\/)/", "", $v);
        $m = str_replace(app("domain_name"), "", $m);
        return $m; // $this->sanitize($m);
    }

    function filled($val = null) {
        if (!$val) $val = ['/admin'];
        $options = [];
        foreach ($val as $v) {
            $options[$v] = $v;
        }
        return $options;
    }
}
