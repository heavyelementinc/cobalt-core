<?php

namespace Components\GoogleReviews\Models;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\StringType;

class Review extends Model {

    public function defineSchema(array $schema = []): array {
        return [
            "name" => new StringType,
            "reviewId" => new StringType,
            "reviewer" => [
                new ModelType,
                'schema' => [
                    "profilePhotoUrl" => new StringType,
                    "displayName" => new StringType,
                    "isAnonymous" => new BooleanType,
                ],
            ],
            "starRating" => [
                new EnumType,
                'valid' => [
                    "STAR_RATING_UNSPECIFIED" => "No Rating",
                    "ONE" => "One Star",
                    "TWO" => "Two Stars",
                    "THREE" => "Three Stars",
                    "FOUR" => "Four Stars",
                    "FIVE" => "Five Stars",
                ]
            ],
            "comment" => new StringType,
            "createTime" => [
                new DateType,
                'fromEncoding' => 'RFC3339'
            ],
            "updateTime" => [
                new DateType,
                'fromEncoding' => 'RFC3339'
            ],
            "reviewReply" => [
                new ModelType,
                'schema' => [
                    "comment" => new StringType,
                    "updateTime" => new StringType,
                ]
            ],
        ];
    }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function getCollectionName($string = null): string {
        return "CobaltGoogleReviews";
    }

}