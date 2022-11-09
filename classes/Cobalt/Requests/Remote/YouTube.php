<?php

namespace Cobalt\Requests\Remote;

class YouTube extends API {

    public function refreshTokenCallback($result): string {
        return "";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function testAPI(): bool {
        return true;
    }

    function getIfaceName():string {
        return "\\Cobalt\\Requests\\Tokens\\YouTube";
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
            'icon' => "<i name='youtube'></i>",
            'name' => "YouTube",
            'view' => '/admin/api/youtube.html'
        ];
    }
}
