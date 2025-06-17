<?php

namespace Auth;

use Validation\Normalize;

class SessionSchema extends Normalize {

    public function __get_schema(): array {
        return [
            // app('session_cookie_name') => [],
            'user_id' => [],
            'refresh' => [
                'get' => fn ($val) => $val * 1000,
                'set' => false,
            ],
            'created' => [
                'get' => fn () => $this->__dataset['_id']->getTimestamp() * 1000
            ],
            'expires' => [
                'get' => fn ($val) => $val * 1000,
                'set' => false,
            ],
            'persist' => [
                'get' => fn ($val) => ($val) ? "Persistent" : "Session",
                'set' => false,
            ],
            'details' => [
                'set' => false,
            ],
            'details.client.build' => [
                // 'display' => fn ($val) => $this->browser_lookup($this->{'details.browser.build'})
                'valid' => [
                    'Chrome' => 'google-chrome',
                    'Edge' => 'microsoft-edge',
                    'Firefox' => 'firefox',
                    'Opera' => 'opera',
                    'Safari' => 'apple-safari',
                    'Unknown' => 'alert-circle-outline',
                ]
            ],
            'details.platform.build' => [
                // 'display' => fn ($val) => $this->platform_lookup($this->{'details.platform.build'})
                'valid' => [
                    'Android' => 'android" style="color: #a4c639',
                    'Windows' => 'microsoft-windows" style="color: #5ba4cf',
                    'iOS' => 'apple-ios" style="color: #808080',
                    'ChromeOS' => 'chrome" style="color: #909090',
                    'Mac OS' => 'apple" style="color: #676767',
                    'Linux' => 'linux" style="color: #4b4b4c',
                    'Unknown' => 'alert-circle-outline" style="color: black',
                ]
            ],
            'details.platform.version' => [
                'get' => function ($val) {
                    if($this->{'details.platform.build'} === "Windows" && $val >= 9) return $val + 1;
                    return $val;
                }
            ],
            'this_session' => [
                'get' => function () {
                    $name = app('session_cookie_name');
                    $current = $_COOKIE[$name];
                    if($this->{$name} === $current) return "session--current-session";
                    return "";
                }
            ]
        ];
    }

    static function platform_lookup($val) {
        $arr = [
            'Android' => 'android',
            'Windows' => 'microsoft-windows-classic',
            'iOS' => 'apple-ios',
            'ChromeOS' => 'chrome',
            'Mac OS' => 'apple',
            'Linux' => 'linux',
            'Unknown' => 'alert-circle-outline',
        ];

        return $arr[$val] ?? 'alert-circle';
    }

    static function browser_lookup($val) {
        $arr = [
            'Chrome' => 'google-chrome',
            'Edge' => 'microsoft-edge',
            'Firefox' => 'firefox',
            'Opera' => 'opera',
            'Safari' => 'apple-safari',
            'Unknown' => 'alert-circle-outline',
        ];

        return $arr[$val] ?? 'alert-circle';
    }

}
