<?php

namespace Cobalt\Requests\Remote;

class AmazonPA extends API {

    public function refreshTokenCallback($result): string {
        return "";
    }

    public function getPaginationToken(): array {
        return [];
    }

    function getIfaceName():string {
        return "\\Cobalt\\Requests\\Tokens\\AmazonPA";
    }

    function getChannelDataById($id) {
        $this->addRequestParams([
            'part' => [
                // 'snippet',
                // 'auditDetails',
                'brandingSettings',
                'contentDetails',
                'contentOwnerDetails',
                'id',
                'localizations',
                'snippet',
                'statistics',
                'status',
                'topicDetails'
            ],
            'contentDetails' => 'statistics',
            'id' => $id
        ]);

        return $this->get("https://www.googleapis.com/youtube/v3/channels");
    }

    static function getMetadata(): array {
        return [
            'icon' => "<ion-icon name='logo-amazon'></ion-icon>",
            'name' => "Amazon Product Advertising"
        ];
    }
}