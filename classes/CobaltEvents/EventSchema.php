<?php

namespace CobaltEvents;

use Validation\Exceptions\ValidationIssue;

class EventSchema extends \Validation\Normalize {

    protected $allowed_event_types = ['modal' => "Modal pop-up", 'banner' => "Banner"];
    protected $allowed_session_policies = [
        'nag' => 'On every page (not recommended)',
        'with_session' => 'After closing tab (session)',
        '24_hours' => 'After 24+ hours',
        'half_date' => 'Event end - closed time / 2',
        'never' => 'Never show event again'
    ];


    public function __get_schema(): array {
        return [
            'name' =>  [ // An name for internal purposes
                'get' => fn ($val) => $val,
                'set' => function ($val) {
                    $val = $this->sanitize($val);
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
                    $val = $this->sanitize($val);
                    $val = $this->required_field($val);
                    return $val;
                }
            ],
            'body' => [ // The body content of the user's input
                'get' => function ($val) {
                    return from_markdown($val);
                },
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
                'get' => fn ($val) => ($val) ? $val : app("css-vars.events-banner-background"),
                'set' => 'hex_color',
                'display' => fn ($val) => $this->hex_color($val, app("css-vars.events-banner-background")),
            ],
            "txtColor" =>  [
                'get' => fn ($val) => ($val) ? $val : app("css-vars.events-banner-text"),
                'set' => function ($val) {
                    return $this->contrast_color($val, $this->__dataset['bgColor']);
                },
                'display' => fn ($val) => $this->hex_color($val ?? app("css-vars.events-banner-text"), app("css-vars.events-banner-text")),
            ],
            'btnColor' =>  [
                'get' => fn ($val) => ($val) ? $val : app("css-vars.events-banner-text"),
                'set' => function ($val) {
                    return $this->contrast_color($val, $this->__dataset['bgColor'], 1.2);
                },
                'display' => fn ($val) => ($val) ? $this->hex_color($val ?? app("css-vars.color-button-init"), app("css-vars.color-button-init")) : "",
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
                'get' => fn ($val) => $this->get_date('start_time'),
                'set' => false,
            ],
            'start_time' => [
                'get' => 'get_time',
                'set' => fn ($val) => $this->set_time($val, 'start')
            ],
            'end_date' => [
                'get' => fn () => $this->get_date('end_time'),
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
                'get' => fn ($val) => $val ?? true,
                'set' => 'boolean_helper'
            ],
            "advanced.delay" => [
                'get' => fn ($val) => $val ?? 10,
                'set' => fn ($val) => $this->min_max($val, 0, 90)
            ]
        ];
    }

    function __merge_private_fields($mutant): array {
        return [
            'last_updated_on' => $this->make_date(),
            'last_updated_by' => session('_id')
        ];
    }

    function get_date($val) {
        if (!$this->__dataset[$val]) return "";
        return mongo_date($this->__dataset[$val]);
    }

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
        return $this->sanitize($m);
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
