<?php

namespace Cobalt\Auth\Users\Models;

use Cobalt\Auth\Users\Models\AdditionalUserFields;
use Cobalt\Auth\UserAccounts\Types\UserIntegrations;
use Cobalt\Auth\UserAccounts\Types\UserPreferences;
use Cobalt\Auth\UserAccounts\Types\UserSocialAccounts;
use Cobalt\Auth\Users\Controllers\Users;
use Cobalt\Auth\Users\Traits\Permissions;
use Cobalt\Controllers\ModelController;
use Cobalt\DBManagement\CobaltCursor;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Directives\SearchableDirective;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayOfPermissionsType;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\DictionaryType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\FakeType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\PasswordHashType;
use Cobalt\Model\Types\StringType;
use Cobalt\Notifications\Classes\NotificationManager;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use DateTime;
use Drivers\DatabaseManagement;
use Exceptions\HTTP\NotFound;
use Generator;
use MongoDB\UpdateResult;
use PSpell\Dictionary;
use Validation\Exceptions\ValidationIssue;

/**
 * @property StringType $uname
 * @property StringType $fname
 * @property StringType $lname
 * @property EnumType $name_format
 * @property PasswordHashType $pword
 * @property EmailAddressType $email
 * @property ImageType $avatar
 * @property BinaryType $flags
 * @property BinaryType $state
 * @property DateType $since
 * @property ArrayType $tokens
 * @property ModelType $prefs
 * @property ArrayType $groups
 * @property ArrayOfPermissionsType $permissions
 * @property BooleanType $is_root
 * @property StringType $public_name
 * @property FakeType $display_name
 * @property BlockType $default_bio_blurb
 * @property BlockType $full_biography
 * @property StringType $fediverse_profile
 * @property StringType $facebook_profile
 * @property StringType $twitter_profile
 * @property StringType $instagram_profile
 * @property StringType $youtube_profile
 * @property ModelType $integrations
 * @property ArrayType $login_tokens
 * @property BooleanType $tfa->totp->enabled
 * @property StringType $tfa->totp->secret
 * @property ArrayType $tfa->totp->backups
 * @property ModelType $tfa->totp
 * @property ModelType $tfa
 * @property ModelType $notifications
 * @property StringType $session_data
 * @package Cobalt\Auth\Users\Models
 */
class User extends Model implements Migration
{
    use Permissions;
    var $additional = null;

    public function defineController(): ModelController
    {
        return new Users();
    }

    public static function __getVersion(): string
    {
        return "4.0";
    }

    // public function modelView($document): string
    // {
    //     throw new \Exception('Not implemented');
    // }

    const NAME_FORMAT_DEFAULT    = 0;
    const NAME_FORMAT_FIRST_LAST = 1;
    const NAME_FORMAT_FIRST_ONLY = 2;
    const NAME_FORMAT_USER_ONLY  = 3;

    public function defineSchema(array $schema = []): array
    {
        $this->__set_index_checkbox_state(true);
        if (!$this->additional) $this->additional = new AdditionalUserFields();
        $app_fields = $this->additional->__get_additional_schema();

        $fields = [
            'uname' => [
                new StringType,
                'tag' => function () {
                    return "<div class='cobalt-user--profile-display'>" . embed_image($this->avatar) . $this->name() . " </div>";
                },
                'index' => [
                    'title' => 'Username'
                ],
                'searchable' => new SearchableDirective(true),
                'placeholder' => 'Username',
                'label' => 'Username',
                'onUpdate' => function () {
                    update(".name-tag", [
                        'innerHTML' => $this->name("F L")
                    ]);
                },
                'filter' => function ($value) {
                    if($this->findOne(['uname' => $value])) {
                        throw new ValidationIssue("Username already exists");
                    }

                    return $value;
                }
            ],
            'fname' => [
                new StringType,
                'index' => [
                    'title' => 'First Name'
                ],
                'searchable' => new SearchableDirective(true),
                'placeholder' => 'First Name',
                'onUpdate' => function () {
                    update(".name-tag", [
                        'innerHTML' => $this->name("F L")
                    ]);
                }
            ],
            'lname' => [
                new StringType,
                'index' => [
                    'title' => 'First Name'
                ],
                'searchable' => new SearchableDirective(true),
                'placeholder' => 'Last Name',
                'onUpdate' => function () {
                    update(".name-tag", [
                        'innerHTML' => $this->name("F L")
                    ]);
                }
            ],
            'name_format' => [
                new EnumType,
                'valid' => [
                    self::NAME_FORMAT_DEFAULT    => "First name, last initial",
                    self::NAME_FORMAT_FIRST_LAST => "First and last names",
                    self::NAME_FORMAT_FIRST_ONLY => "First name only",
                    self::NAME_FORMAT_USER_ONLY  => "Username only",
                ],
                'default' => self::NAME_FORMAT_DEFAULT,
            ],
            // 'name' => [
            //     new StringType
            // ],

            'pword' => [
                new PasswordHashType,
                'label' => 'Password'
            ],

            'email' => [
                new EmailAddressType,
                'label' => 'Email Address',
                'index' => [],
            ],

            'avatar' => [
                new ImageType,
                'placeholder' => '/core-content/img/unknown-user.jpg'
            ],
            'flags' => [
                new BinaryType
            ],
            'state' => [
                new BinaryType
            ],
            'since' => new DateType,
            'tokens' => [
                new ArrayType
            ],
            'prefs' => [
                new ModelType
            ],
            'groups' => [
                new ArrayType
            ],
            'permissions' => [
                new ArrayOfPermissionsType,
                // 'index' => [
                //     'display' => function () {
                //         return $this->permissions?->length ?? 0;
                //     }
                // ]
            ],
            'is_root' => [
                new BooleanType,
                'index' => [
                    'display' => function ($value) {
                        return ($value) ? "Root" : "User";
                    }
                ]
            ],
            'public_name' => [
                new StringType
            ],
            'display_name' => [
                new FakeType
            ],
            'default_bio_blurb' => [
                new BlockType
            ],
            'full_biography' => [
                new BlockType
            ],
            'fediverse_profile' => [
                new StringType
            ],
            'facebook_profile' => [
                new StringType
            ],
            'twitter_profile' => [
                new StringType
            ],
            'instagram_profile' => [
                new StringType
            ],
            'youtube_profile' => [
                new StringType
            ],
            'integrations' => [
                new ModelType,
            ],
            'login_tokens' => [
                new ArrayType,
            ],
            'tfa' => [
                new ModelType,
                'schema' => [
                    'totp' => [
                        new ModelType,
                        'schema' => [
                            'enabled' => new BooleanType,
                            'secret' => new StringType,
                            'backups' => new ArrayType,
                        ]
                    ]
                ]
            ],
            'notifications' => [
                new ModelType,
                'schema' => [
                    'push' => new ModelType
                ]
            ],
            'session_data' => new StringType
        ];
        return array_merge($fields, $app_fields);
    }

    public function getCollectionName($string = null): string
    {
        return "users";
    }

    public function __initializeDataset(int &$count):Generator
    {
        throw new \Exception('Not implemented');
    }

    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void
    {
        $mutated_doc['avatar'] = $doc['avatar']['media']['id'];
        unset($mutated_doc['__v']);
        $mutated_doc['tfa'] = ['totp' => $doc['tfa']];
        $update['$unset'] = ['__v' => 1];
    }

    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void {}

    // #[Prototype]
    // function tag() {
        
    // }

    /**
     * Valid format characters are:
     *  * `F` - First name
     *  * `f` - First initial
     *  * `L` - Last name
     *  * `l` - Last initial
     *  * `U` - Username
     *  * `u` - Username initial
     * @param string $format 
     * @return string 
     */
    #[Prototype]
    function name(?string $format = null): string
    {
        switch ($this->name_format->value) {
            case self::NAME_FORMAT_FIRST_LAST:
                $format = "F L";
                break;
            case self::NAME_FORMAT_FIRST_ONLY:
                $format = "F";
                break;
            case self::NAME_FORMAT_USER_ONLY:
                $format = "U";
                break;
            case self::NAME_FORMAT_DEFAULT:
            default:
                $format = "F l.";
                break;
        }
        // Quick and dirty optimization. If the format string is the default
        // value, then we should return the value
        if($format === "F l.") {
            if($this->fname && $this->lname) {
                $name = "$this->fname ".$this->lname->value[0] .".";
            }
            else $name = $this->uname;
        } else {
            $name = "";
            for($i = 0; $i <= strlen($format); $i++) {
                switch($format[$i]) {
                    case "F":
                        $name .= "$this->fname";
                        break;
                    case "f":
                        $name .= $this->fname->value[0];
                        break;
                    case "L":
                        $name .= "$this->lname";
                        break;
                    case "l":
                        $name .= $this->lname->value[0];
                        break;
                    case "U":
                        $name .= "$this->uname";
                        break;
                    case "u":
                        $name .= $this->uname->value[0];
                        break;
                    default:
                        $name .= $format[$i];
                        break;
                }
            }
        }

        // $name = str_replace(
        //     ['F',          'f',                    'L',          'l',                     'U',         'u',],
        //     [$this->fname, $this->fname->value[0], $this->lname, $this->lname->value[0], $this->uname, $this->uname->value[0],],
        //     $format
        // );
        $trimmed = trim($name);
        if (!$trimmed || $trimmed === ".") {
            return $this->uname;
        }
        return $name;
    }

    /**
     * 
     * @param array<string> $permissions 
     * @param bool $state 
     * @param array $options 
     * @return CobaltCursor|null 
     */
    function getUsersByPermission(string|array $permissions = [], bool $state = true, array $options = []): ?CobaltCursor
    {
        $query = [
            '$or' => []
        ];

        if ($state === true) $query['$or'][] = ['is_root' => true];
        if (is_string($permissions)) $permissions = [$permissions];

        foreach ($permissions as $permission) {
            $query['$or'][] = [$permission => $state];
        }

        return $this->find([
            '$or' => [
                ['is_root' => $state],
                ['permissions' => ['$in' => $permissions]]
            ]
        ], $options);
    }

    function getUsersByGroup(array $groups = [])
    {
        return $this->find(['groups' => ['$in' => $groups]]);
    }

    const TFA_IS_DISABLED     = 0b000;
    const TFA_TOTP_ENABLED    = 0b001;
    const TFA_PASSKEY_ENABLED = 0b010;
    /**
     * Returns an int
     * @return int
     */
    function getUserTFAModes(): int
    {
        $result = self::TFA_IS_DISABLED;
        if ($this->user?->tfa?->totp?->enabled?->value ?? false) $result += self::TFA_TOTP_ENABLED;
        return $result;
    }

    function getUnreadNotificationCount()
    {
        $ntfy = new NotificationManager();
        return $ntfy->getUnreadNotificationCountForUser($this->_id);
    }
}
