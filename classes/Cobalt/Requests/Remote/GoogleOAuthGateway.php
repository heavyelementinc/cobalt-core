<?php

namespace Cobalt\Requests\Remote;

class GoogleOAuthGateway extends API {

    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\GoogleOAuthGateway";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function testAPI(): bool {
        return true;
    }

    public function getOnboardLink($scopes) {
        $parameters = [
            'client_id' => $this->__token->client_id,
            'redirect_uri' => $this->__token->redirect_uri,
            'response_type' => $this->getRequestType(),
            'scope' => $this->getScopes($scopes),
            'access_type' => $this->getAccessType(),
        ];
        return $this->getEndpoint() . "?" . http_build_query($parameters);
    }

    public static function getMetadata(): array {
        return [
            'name' => 'Google OAuth Gateway',
            'icon' => '<i name="google"></i>',
            'view' => '/admin/api/editors/oauth-gateway-default.html'
        ];
    }

}
