<?php

namespace Cobalt\Requests\Remote;

class Twitter extends API {

    public function refreshTokenCallback($result): string {
        $this->setRequestBody([
            'refresh_token' => $result['refresh_token']['token'],
            'grant_type' => 'refresh_token',
            'client_id' => $this->getClientId(),
        ]);

        return $this->post("https://api.twitter.com/2/oauth2/token",[],[]);
    }

    public function getPaginationToken(): array {
        return [];
    }

    function getIfaceName():string {
        return "\\Cobalt\\Requests\\Tokens\\Twitter";
    }

    function getManyUserDataByUsername(array $users) {
        $this->addRequestParams([
            'usernames' => implode(",",$users),
            'user.fields' => 'public_metrics,created_at,entities,description,verified,profile_image_url,pinned_tweet_id'
        ]);

        return $this->get("http://api.twitter.com/2/by/");
    }

    function getSingleUserPublicDataById(string $user) {
        $this->addRequestParams([
            'user.fields' => 'public_metrics,created_at,entities,description,verified,profile_image_url,pinned_tweet_id',
        ]);
        return $this->get("https://api.twitter.com/2/users/$user");
    }

    function getTweetPublicData(array $ids) {
        $this->addRequestParams([
            'ids' => $ids,
            "tweet.fields" => [
                "attachments",
                "author_id",
                "context_annotations",
                "conversation_id",
                "created_at",
                "entities",
                "geo",
                "id",
                "in_reply_to_user_id",
                "lang",
                "possibly_sensitive",
                "public_metrics",
                "referenced_tweets",
                "reply_settings",
                "source",
                "text",
                "withheld"
            ],
            "media.fields" => [
                "alt_text",
                "duration_ms",
                "height",
                "media_key",
                "organic_metrics",
                "preview_image_url",
                "public_metrics",
                "type",
                "url",
                "variants",
                "width"
            ],
        ]);
        return $this->get("https://api.twitter.com/2/tweets");
    }

    function getTweetPrivateData(array $ids) {
        $this->addRequestParams([
            'ids' => $ids,
            "tweet.fields" => [
                "attachments",
                "author_id",
                "context_annotations",
                "conversation_id",
                "created_at",
                "entities",
                "geo",
                "id",
                "in_reply_to_user_id",
                "lang",
                "non_public_metrics",
                "organic_metrics",
                "possibly_sensitive",
                "promoted_metrics",
                "public_metrics",
                "referenced_tweets",
                "reply_settings",
                "source",
                "text",
                "withheld"
            ],
            "media.fields" => [
                "alt_text",
                "duration_ms",
                "height",
                "media_key",
                "non_public_metrics",
                "organic_metrics",
                "preview_image_url",
                "promoted_metrics",
                "public_metrics",
                "type",
                "url",
                "variants",
                "width"
            ],
        ]);
        return $this->get("https://api.twitter.com/2/tweets");
    }


    static function getMetadata(): array {
        return [
            'icon' => "<ion-icon name='logo-twitter'></ion-icon>",
            'name' => "Twitter"
        ];
    }
    
}