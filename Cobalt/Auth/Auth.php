<?php

namespace Cobalt\Auth;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\BlockType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\FakeType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\StringType;

class UserPersistance extends Model {
    public function defineSchema(array $schema = []): array {
        return [
            'fname' =>[
                new StringType
            ],
            'lname' =>[
                new StringType
            ],
            'name' =>[
                new StringType
            ],
            'uname' =>[
                new StringType
            ],
            'pword' =>[
                new StringType,
                
            ],
            'email' =>[
                new EmailAddressType
            ],
            'avatar' =>[
                new ImageType
            ],
            'flags' =>[
                new ArrayType
            ],
            'state' =>[
                new BinaryType
            ],
            'token' =>[
                new ArrayType
            ],
            'prefs' =>[
                new ModelType
            ],
            'since' =>[
                new DateType
            ],
            'groups' =>[
                new ArrayType
            ],
            'permissions' =>[
                new ArrayType
            ],
            'is_root' =>[
                new BooleanType
            ],
            'public_name' =>[
                new StringType
            ],
            'display_name' =>[
                new FakeType
            ],
            'default_bio_blurb' =>[
                new BlockType
            ],
            'full_biography' =>[
                new BlockType
            ],
            'fediverse_profile' =>[
                new StringType
            ],
            'facebook_profile' =>[
                new StringType
            ],
            'twitter_profile' =>[
                new StringType
            ],
            'instagram_profile' =>[
                new StringType
            ],
            'youtube_profile' =>[
                new StringType
            ],
            'integrations' =>[
                new ModelType,
            ],
        ];
    }

    public function defineController(): ModelController { }

    public static function __getVersion(): string { }

    public function getCollectionName($string = null): string { }

}