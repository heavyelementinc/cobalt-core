<?php

namespace Cobalt\Auth\Users\Models;

use Cobalt\Auth\Users\Models\AdditionalUserFields;
use Cobalt\Auth\UserAccounts\Types\UserIntegrations;
use Cobalt\Auth\UserAccounts\Types\UserPreferences;
use Cobalt\Auth\UserAccounts\Types\UserSocialAccounts;
use Cobalt\Auth\Users\Controllers\Users;
use Cobalt\Auth\Users\Traits\Permissions;
use Cobalt\Controllers\ModelController;
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
use Cobalt\Model\Types\FakeType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\PasswordHashType;
use Cobalt\Model\Types\StringType;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use DateTime;
use Drivers\DatabaseManagement;
use Exceptions\HTTP\NotFound;
use MongoDB\UpdateResult;
use PSpell\Dictionary;

class User extends Model implements Migration {
    use Permissions;
    var $additional = null;

    public function defineController(): ModelController {
        return new Users();
    }

    public static function __getVersion(): string {
        return "4.0";
    }

    // public function modelView($document): string
    // {
    //     throw new \Exception('Not implemented');
    // }
    
    public function defineSchema(array $schema = []): array {
        $this->__set_index_checkbox_state(true);
        if(!$this->additional) $this->additional = new AdditionalUserFields();
        $app_fields = $this->additional->__get_additional_schema();

        $fields = [
            'uname' => [ // ☑️
                new StringType,
                'tag' => function () {
                    return "<div class='cobalt-user--profile-display'>".embed_image($this->avatar).$this->name()." </div>";
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
                }
            ],
            'fname' => [ // ☑️
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
            'lname' => [ // ☑️
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
            // 'name' => [
            //     new StringType
            // ],
            
            'pword' => [ // ☑️
                new PasswordHashType,
                'label' => 'Password'
            ],

            'email' => [ // ☑️
                new EmailAddressType,
                'label' => 'Email Address',
                'index' => [],
            ],
            'avatar' => [ // ☑️
                new ImageType
            ],
            'flags' => [ // ☑️
                new BinaryType
            ],
            'state' => [
                new BinaryType
            ],
            'since' => new DateType, // ☑️
            'tokens' => [ // ☑️
                new ArrayType
            ],
            'prefs' => [ // ☑️
                new ModelType
            ],
            'since' => [
                new DateType
            ],
            'groups' => [ // ☑️
                new ArrayType
            ],
            'permissions' => [ // ☑️
                new ArrayOfPermissionsType,
                // 'index' => [
                //     'display' => function () {
                //         return $this->permissions?->length ?? 0;
                //     }
                // ]
            ],
            'is_root' => [ // ☑️
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
            'fediverse_profile' => [ // ☑️
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
            'login_tokens' => [ // ☑️
                new ArrayType,
            ],
            'tfa' => [ // ☑️
                new ModelType,
                'schema' => [
                    'enabled' => new BooleanType, // ☑️
                    'secret' => new StringType,
                    'backups' => new ArrayType,
                ]
            ],
            'notifications' => [ // ☑️
                new ModelType,
                'schema' => [
                    'push' => new ModelType
                ]
            ],
            'session_data' => new StringType
        ];
        return array_merge($fields, $app_fields);
    }

    public function getCollectionName($string = null): string {
        return "users";
    }

    public function __initializeDataset()
    {
        throw new \Exception('Not implemented');
    }

    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void
    {
        $mutated_doc['avatar'] = $doc['avatar']['media']['id'];
        unset($mutated_doc['__v']);
        $update['$unset'] = ['__v' => 1];
    }

    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void
    {
        
    }

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
    function name(string $format = "F l."):string {
        // Quick and dirty optimization. If the format string is the default
        // value, then we should return the value
        if($format === "F l.") {
            if($this->fname && $this->lname) {
                return "$this->fname ".$this->lname->value[0] .".";
            }
            return $this->uname;
        }
        $name = str_replace(
         ['f',                   'F',          'l',                    'L',           'u',                    'U'],
        [$this->fname->value[0], $this->fname, $this->lname->value[0], $this->lname, $this->uname->value[0], $this->uname],
        $format
        );
        $trimmed = trim($name);
        if(!$trimmed || $trimmed === ".") {
            return $this->uname;
        }
        return $name;
    }

    function getUsersByPermission(array $permissions = []) {
        return $this->find([
            '$or' => [
                ['is_root' => true],
                ['permissions' => ['$in' => $permissions]]
            ]
        ]);
    }

    function getUsersByGroup(array $groups = []) {
        return $this->find(['groups' => ['$in' => $groups]]);
    }
}