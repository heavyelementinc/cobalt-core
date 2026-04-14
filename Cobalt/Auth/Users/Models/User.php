<?php

namespace Cobalt\Auth\Users\Models;

use Cobalt\Auth\Users\Models\AdditionalUserFields;
use Cobalt\Auth\UserAccounts\Types\UserIntegrations;
use Cobalt\Auth\UserAccounts\Types\UserPreferences;
use Cobalt\Auth\UserAccounts\Types\UserSocialAccounts;
use Cobalt\Auth\Users\Controllers\Users;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
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
use Cobalt\Model\Types\StringType;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\DatabaseManagement;
use MongoDB\UpdateResult;
use PSpell\Dictionary;

class User extends Model implements Migration {
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
                new StringType
            ],
            'fname' => [ // ☑️
                new StringType
            ],
            'lname' => [ // ☑️
                new StringType
            ],
            // 'name' => [
            //     new StringType
            // ],
            
            'pword' => [ // ☑️
                new StringType,
            ],

            'email' => [ // ☑️
                new EmailAddressType
            ],
            'avatar' => [ // ☑️
                new ImageType
            ],
            'flags' => [ // ☑️
                new DictionaryType
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
                new ArrayType
            ],
            'is_root' => [ // ☑️
                new BooleanType
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
        unset($mutated_doc['__v']);
        $update['$unset'] = ['__v' => 1];
    }

    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void
    {
        
    }

}