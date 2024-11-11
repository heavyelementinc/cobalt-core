<?php

namespace Cobalt\Posts;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\FakeResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\UniqueResult;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use Drivers\Database;

/**
 * @deprecated
 * @package Cobalt\Posts
 */
class PostPersistance extends PersistanceMap {

    public function __set_manager(?Database $manager = null): ?Database {
        return new PostManager();
    }

    public function __get_schema(): array {
        return [
            'author' => [
                new UserIdResult(),
                'valid' => function () {
                    return UserIdResult::get_valid_users('Posts_manage_posts', 'permission');
                }

            ],
            'title' => [
                new StringResult
            ],
            'url_slug' => [
                new UniqueResult(new PostManager()),
                'filter' => function ($val) {
                    return url_fragment_sanitize($val);
                }
            ],
            'published' => [
                new BooleanResult,
                'display' => fn ($val) => $val ? "Published" : "Draft"
            ],
            'publicationDate' => [
                new DateResult
            ],
            'publicationTime' => [
                new FakeResult,
                'get' => fn () => $this->publicationDate->format("relative"),
                'date' => fn () => $this->publicationDate->format("relative"),
            ],
            'body' => [
                new MarkdownResult
            ],
            'excerpt' => [
                new StringResult,
                'get' => function ($val) {
                    if(!$val) return $this->body->strip_tags();
                    return $val;
                },
                'set' => function ($val) {
                    $res = new MarkdownResult;
                    $res->setValue($val);
                    return $res->strip_tags();
                },
            ],
            'postType' => [
                new StringResult,
                'get' => fn($val) => $val ?? "Posts"
            ],
            'default_image' => [
                new UploadImageResult
            ],
            'rss_attachment' => [
                new FakeResult,
                'attrs' => function ($val) {
                    
                }
            ],
            'tags' => [
                new ArrayResult,
                'allow_custom' => true,
                'valid' => function () {
                    $posts = new PostManager();
                    $tags = $posts->distinct('tags');
                    return $tags;
                }
            ]
        ];
    }
    
}