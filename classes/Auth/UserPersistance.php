<?php

namespace Auth;

use Cobalt\Extensions\Extensions;
use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\BlockResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\FakeResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\ObjectResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Basic\UploadResult;
use Cobalt\SchemaPrototypes\Compound\EmailAddressResult;
use Cobalt\SchemaPrototypes\Compound\HrefResult;
use Cobalt\SchemaPrototypes\Compound\ImageResult;
use Cobalt\SchemaPrototypes\Compound\UniqueResult;
use Cobalt\SchemaPrototypes\MapResult;
use Drivers\Database;
use Validation\Exceptions\ValidationIssue;

class UserPersistance extends PersistanceMap {
    private ?AdditionalUserFields $additional = null;

    const STATE_USER_VERIFIED        = 0b0000000001;

    public function __set_manager(?Database $manager = null): ?Database {
        return new UserCRUD();
    }

    public function __get_schema(): array {
        $this->__set_index_checkbox_state(true);
        if(!$this->additional) $this->additional = new AdditionalUserFields();
        $app_fields = $this->additional->__get_additional_schema();
        $fields = array_merge([
            'fname' => [
                new StringResult,
                'limit' => 150,
                'index' => [
                    'title' => 'Name',
                    'view' => function () {
                        return "$this->fname $this->lname";
                    }
                ]
            ],
            'lname' => [
                new StringResult,
                'limit' => 150
            ],
            'name' => [
                new FakeResult,
                'get' => function () {
                    if($this->fname && $this->lname) return "<span title='Username: $this->uname'>$this->fname " . $this->lname[0] . ".</span>";
                    return $this->uname;
                },
                'tag' => function () {
                    return "<div class='cobalt-user--profile-display'>".$this->avatar->embed("thumb")." $this->name </div>";
                }
            ],
            'uname' => [
                new UniqueResult(new UserCRUD(), true),
                'index' => [
                    'title' => 'Username',
                ]
            ],

            'pword' => new StringResult,
            'email' => [
                new EmailAddressResult,
                'index' => [
                    'title' => 'Email Address',
                ]
            ],
            'avatar' => new ImageResult,
            'flags' => [
                new ArrayResult,
                // 'schema' => [
                //     'verified' => [
                //         new BooleanResult,
                //         'display' => fn ($val) => ($val) ? "<i name='check-decagram' title='Verified user'></i>" : ""
                //     ],
                //     'password_reset_required' => new BooleanResult,
                //     'locked' => new BooleanResult,
                // ]
            ],
            'state' => [
                new BinaryResult,
                'valid' => [
                    self::STATE_USER_VERIFIED => 'User is verified',
                ]
            ],
            'token' => new StringResult,
            'prefs' => new ObjectResult,
            'since' => [
                new DateResult,
                'index' => [
                    'title' => 'Created'
                ]
            ],
            'groups' => [
                new ArrayResult,
            ],
            'permissions' => [
                new ArrayResult,
            ],
            'is_root' => [
                new BooleanResult,
                'default' => false,
                'index' => [
                    'title' => 'Root User'
                ]
            ],
            'public_name' => [
                new StringResult
            ],
            'display_name' => [
                new FakeResult,
                'get' => function() {
                    $name = $this->fname;
                    if($name) $name .= " $this->lname";
                    if(!$name) $name = $this->uname;
                    return $name;
                }
            ],
            'biography' => [
                new BlockResult,
            ],
            'fediverse_profile' => [
                new StringResult,
                // 'filter' => function ($val) {
                //     $result = preg_match("/@\w*@\w*.\w*/",$val);
                //     switch($result) {
                //         case false:
                //             throw new ValidationIssue("Regex error occurred");
                //         case 0:
                //             throw new ValidationIssue("This does not appear to be a fediverse account");
                //     }
                //     return $val;
                // },
                'meta_tag' => function () {
                    $value = $this->fediverse_profile->getValue();
                    // if(!$value) $value = __APP_SETTINGS__['SocialMedia_fediverse'];
                    // if(!$value) return "";
                    // return "<meta name=\"fediverse:creator\" content=\"$value\" />";
                    return fediverse_href_to_user_tag($value);
                }
            ],
            'facebook_profile' => [
                new HrefResult
            ],
            'twitter_profile' => [
                new HrefResult
            ],
            'instagram_profile' => [
                new HrefResult
            ],
            'youtube_profile' => [
                new HrefResult
            ],
            
            'integrations' => [
                new MapResult,
                'schema' => [
                    'YouTube' => [
                        new ArrayResult,
                        'hydrate' => false
                        // 'each' => [
                        //     'details' => [
                        //         new MapResult,
                        //         'schema' => [
                        //             'access_token' => new StringResult,
                        //             'expires_in' => new NumberResult,
                        //             'scope' => new StringResult,
                        //             'token_type' => new StringResult
                        //         ]
                        //     ],
                        //     'expiration' => new DateResult,
                        // ]
                    ],
                    'Facebook' => [
                        new ArrayResult,
                        'hydrate' => false
                    ]
                ]
            ]
        ], 
        $GLOBALS['ADDITIONAL_USER_FIELDS'],
        $app_fields);

        return $fields;
    }

    public function name() {
        if($this->fname && $this->lname) return "<span title='Username: $this->uname'>$this->fname " . $this->lname[0] . ".</span>";
        return $this->uname;
    }

    public function nametag() {
        return "<div class='cobalt-user--profile-display'>".$this->{"avatar.display"}." $this->name ".$this->{'flags.verified.display'}."</div>";
    }

}