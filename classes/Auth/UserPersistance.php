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
use Cobalt\Token;
use DateTime;
use Drivers\Database;
use MongoDB\BSON\UTCDateTime;
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
                    if($this->fname) {
                        $lname = $this->lname[0];
                        if($lname) $lname = " $lname.";
                        return "<span title='Username: $this->uname'>$this->fname $lname</span>";
                    }
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

            'pword' => [
                new StringResult,
                'filter' => function ($value) {
                    $password_fail = "";

                    /** Check if the password starts or ends with whitespace (not allowed) */
                    if ($value !== trim($value)) $password_fail .= "Passwords must not begin or end with spaces.\n";
            
                    /** Check if the password length meets the minimum required length */
                    if (strlen($value) < app("Auth_min_password_length")) $password_fail .= "Password must be at least " . app("Auth_min_password_length") . " characters long.\n";
            
                    /** Detect if submitted passwords are all alphabetical or all numerical characters */
                    if (ctype_alpha($value) || ctype_digit($value)) $password_fail .= "Password must include at least one letter and one number.\n";
            
                    /** Check if strings are only comprised of alphanumeric characters */
                    if (ctype_alnum($value)) $password_fail .= "Password must contain at least one special character.\n";
            
                    if (!empty($password_fail)) throw new ValidationIssue($password_fail);
            
                    $this->__validatedFields["flags.password_reset_required"] = false;
                    $this->__validatedFields["flags.password_last_changed_by"] = session("_id") ?? "CLI";
                    $this->__validatedFields["flags.password_last_changed_on"] = new UTCDateTime();
            
                    /** Finally, we have a valid password. */
                    return password_hash($value, PASSWORD_DEFAULT);
                }
            ],
            'email' => [
                new EmailAddressResult,
                'index' => [
                    'title' => 'Email Address',
                ]
            ],
            'avatar' => [
                new ImageResult,
                'default' => [
                    'url' => '/core-content/img/unknown-user.thumb.jpg',
                    'height' => 250,
                    'width' => 226
                ]
            ],
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
            'token' => new ArrayResult,
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
                },
                'hcard' => function ($display_name, $ref = "", $misc, $classes, $img_classes = "avatar") {
                    return view("/authentication/user-h-card.html",[
                        'doc' => $this,
                        'class' => $classes,
                        'img_class' => $img_classes,
                        'href' => server_name(),
                    ]);
                }
            ],
            'default_bio_blurb' => [
                new BlockResult,
            ],
            'full_biography' => [
                new BlockResult,
            ],
            'fediverse_profile' => [
                new HrefResult,
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

    public function generate_token($name, null|int|DateTime $expires = null) {
        if(!$this->_id) throw new \Exception("Tokens may only be generated for populated schemas.");
        $token = new \Cobalt\Token();
        $crud = $this->__set_manager();
        $crud->updateOne(
            [
                '_id' => $this->_id
            ],[
                '$addToSet' => [
                    "token" => [
                        'name' => $name,
                        'value' => (string)$token,
                        'expires' => new UTCDateTime((gettype($expires) === "int") ? $expires * 1000 : $expires)
                    ]
                ]
            ]
        );
        return $token;
    }

    public function get_token($name) {
        foreach($this->token as $token) {
            if($token->name === $name) {
                return new Token($token->value, $token->expires, $token->name);
            }
        }
        return null;
    }

    public function expire_token($token_object) {
        if(!$this->_id) throw new \Exception("Tokens may only be expired for populated schemas.");
        $crud = new UserCRUD();
        $query = $token_object;
        $result = $crud->updateOne(['_id' => $this->_id], [
            '$pull' => [
                'token' => $query,
            ]
        ]);
        return $result->getModifiedCount();
    }

    public function expire_token_type($name) {
        if(!$this->_id) throw new \Exception("Tokens may only be expired for populated schemas.");
        $crud = new UserCRUD();
        $query = [
            '$elemMatch' => ['name' => $name]
        ];
        $result = $crud->updateOne(
            ['_id' => $this->_id],
            [
                '$pull' => [
                    'token' => [
                        'name' => $name
                    ]
                ]
            ]);
        return $result->getModifiedCount();
    }

}