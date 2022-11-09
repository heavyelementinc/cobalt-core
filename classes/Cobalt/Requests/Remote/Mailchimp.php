<?php

namespace Cobalt\Requests\Remote;

use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\HTTPException;
use stdClass;

class Mailchimp extends API {

    public function refreshTokenCallback($result): string {
        return "";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function testAPI(): bool {
        return $this->getAPIStatus();
    }

    function getIfaceName():string {
        return "\\Cobalt\\Requests\\Tokens\\Mailchimp";
    }

    function getAPIStatus() {
        // $this->addRequestParams();
        $endpoint = $this->token->endpoint;
        $result = $this->get("https://$endpoint.api.mailchimp.com/3.0/ping");
        if($result->health_status === "Everything's Chimpy!") return true;
        return false;
    }

    function insertEmailToAudience($data, $status = "subscribed") {
        if(!$data['email']) throw new BadRequest("You need to fill out the email field");
        $endpoint = $this->token->endpoint;
        $list_id = $this->token->key;
        $result = $this->post(
            "https://$endpoint.api.mailchimp.com/3.0/lists/$list_id/members",
            [
                'email_address' => $data['email'],
                'status' => $status,
                // 'merge_fields' => [
                //     'FNAME' => $data['fname'],
                //     'LNAME' => $data['lname'],
                // ]
            ]
        );
        return $result;
    }

    function updateEmailInAudience($email, $status = "subscribed") {
        if(!$email) throw new BadRequest("You need to fill out the email field");
        $endpoint = $this->token->endpoint;
        $list_id = $this->token->key;
        $hash = md5(strtolower($email));
        $result = $this->put(
            "https://$endpoint.api.mailchimp.com/3.0/lists/$list_id/members/$hash",
            [
                'status' => $status
            ]
        );
        return $result;
    }

    function emailSubscriptionStatus($email):string {
        $result = $this->subscriberDetails($email);
        return ($result->status) ? $result->status : false;
    }

    function isEmailInAudience($email) {
        $result = $this->subscriberDetails($email);
        return is_object($result);
    }

    function subscriberDetails($email):object|false {
        $endpoint = $this->token->endpoint;
        $list_id = $this->token->key;
        $hash = md5(strtolower($email));
        try{
            $result = $this->get(
                "https://$endpoint.api.mailchimp.com/3.0/lists/$list_id/members/$hash"
            );
        } catch (HTTPException $e) {
            $e->dismissError();
            return false;
        }
        return $result;
    }


    static function getMetadata(): array {
        return [
            'icon' => "<i name='email'></i>",
            'name' => "Mailchimp",
            'view' => "/admin/api/mailchimp.html"
        ];
    }
}
