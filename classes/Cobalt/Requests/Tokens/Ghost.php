<?php
namespace Cobalt\Requests\Tokens;

use stdClass;

class Ghost extends TokenInterface {

    public function getKey(): string {
        return $this->__token['key'];
    }
    public function getToken(): string {
        $exploded = explode(":",$this->__token['token']);
        $tk = [
            'kid' => $exploded[0]
            // [
            //     'id' => $exploded[0],
            //     'secret' => $exploded[1],
            // ]
        ];
        return createJWT($tk, [
            'exp' => strtotime("+4 min"),
            'iat' => time(),
            'aud' => "/admin"

        ], \hex2bin($exploded[1]));
    }

    public function getSecret(): string {
        return $this->__token['secret'] ?? "";
    }

    public function setSecret(): ?string {
        return "";
    }

    public function setToken(): string {
        return "";
    }

    function getTokenType():string{
        return "Authorization";
    }

    function getTokenPrefix():string{
        return "Ghost";
    }

    function getMiscHeaders(): array {
        return [
            'Accept-Version' => '5.0'
        ];
    }
}
