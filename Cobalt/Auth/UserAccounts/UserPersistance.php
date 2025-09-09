<?php

namespace Cobalt\Auth\UserAccounts;

use Cobalt\Auth\UserAccounts\Types\UserIntegrations;
use Cobalt\Auth\UserAccounts\Types\UserPreferences;
use Cobalt\Auth\UserAccounts\Types\UserSocialAccounts;
use Cobalt\Controllers\ModelController;
use Cobalt\DefinedModel\Model;
use Cobalt\DefinedModel\Traits\ModelInitialize;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\FakeType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\StringType;

class UserPersistance extends Model {
    use ModelInitialize;

    readonly StringType $fname;
    readonly StringType $lname;
    readonly StringType $uname;
    readonly StringType $pword;
    readonly EmailAddressType $email;
    readonly ImageType $avatar;
    readonly ArrayType $flags;
    readonly BinaryType $state;
    readonly ArrayType $token;
    readonly DateType $since;
    readonly ArrayType $groups;
    readonly ArrayType $permissions;
    readonly BooleanType $is_root;
    readonly StringType $public_name;
    readonly FakeType $display_name;
    readonly BlockType $default_bio_blurb;
    readonly BlockType $full_biography;
    readonly UserPreferences $prefs;
    readonly UserSocialAccounts $socials;
    readonly UserIntegrations $integrations;

    public function modelView($document): string {
        return "";
    }
    
    public function defineSchema(array $schema = []): array {
        return [
            'uname' => [
                new StringType
            ],
            'fname' => [
                new StringType
            ],
            'lname' => [
                new StringType
            ],
            'name' => [
                new StringType
            ],
            
            'pword' => [
                new StringType,
            ],

            'email' => [
                new EmailAddressType
            ],
            'avatar' => [
                new ImageType
            ],
            'flags' => [
                new ArrayType
            ],
            'state' => [
                new BinaryType
            ],
            'token' => [
                new ArrayType
            ],
            'prefs' => [
                new ModelType
            ],
            'since' => [
                new DateType
            ],
            'groups' => [
                new ArrayType
            ],
            'permissions' => [
                new ArrayType
            ],
            'is_root' => [
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
        ];
    }

    public function getCollectionName($string = null): string {
        return "users";
    }

}