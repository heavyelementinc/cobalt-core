<?php

namespace Cobalt\Integrations\MailChimp;

use Cobalt\Integrations\Config;
use Cobalt\Integrations\Base;
use Cobalt\Integrations\IntegrationRemoteException;
use Exceptions\HTTP\BadRequest;
use GuzzleHttp\Exception\ClientException;
use TypeError;

class MailChimp extends Base {
    const STATUS_NOT_SUBSCRIBED = false;
    const STATUS_SUBSCRIBED = "subscribed";
    const STATUS_UNSUBSCRIBED = "unsubscribed";
    const STATUS_PENDING = "pending";
    const STATUS_CLEANED = "cleaned";

    public function oauth_errors(): array {
        return [
            'user_denied' => [
                'callback' => fn () => false,
                'message' => fn () => "You denied the request"
            ]
        ];
    }

    public function publicName(): string {
        return "MailChimp";
    }

    public function publicIcon(): string {
        return "mailchimp";
    }

    public function get_unique_token(): string {
        return "mailchimp";
    }

    public function configuration(): Config {
        return new MailChimpConfig();
    }

    public function html_token_editor(): string {
        return view("/admin/integrations/edit/mailchimp.html");
    }

    private function get_host() {
        return "https://" . $this->config->region . ".api.mailchimp.com/3.0/";
    }

    public function status():int {
        $result = $this->fetch("GET", $this->get_host() . "ping", [], []);
        if($result['response']['health_status'] === "Everything's Chimpy!") return self::STATUS_CHECK_OK;
        return self::STATUS_CHECK_FAIL;
    }

    private function validate_contact_data($data):array {
        if(!key_exists('email_address', $data)) throw new BadRequest('Missing required field: "email_address"');
        if(!key_exists('status', $data)) throw new BadRequest('Missing required field: "status"');
        if(!filter_var($data['email_address'], FILTER_VALIDATE_EMAIL)) throw new BadRequest("Malformed email address");
        $valid_status = ['subscribed', 'pending'];
        if(!in_array($data['status'], $valid_status)) throw new BadRequest("Invalid status");
        return $data;
    }

    public function contact_details($list_id, $email_address) {
        if(!$list_id || !$email_address) throw new TypeError("Must not be empty");
        try {
            $result = $this->fetch(
                "GET",
                $this->get_host() . "lists/$list_id/members/" . md5($email_address),
            );
        } catch (IntegrationRemoteException $e) {
            if($e->error()->getCode() == 404) return null;
            return [];
        }
        return $result['response'];
    }

    public function contact_exists($list_id, $email_address):bool {
        $result = $this->contact_details($list_id, $email_address);
        if(is_null($result)) return false;
        if(empty($result)) return false;
        return true;
    }

    public function contact_add($list_id, $contact_data) {
        if(!$list_id) throw new TypeError("ListID must not be empty");

        $result = $this->fetch(
            "POST",
            $this->get_host() . "lists/$list_id/members",
            $this->validate_contact_data($contact_data)
        );
        return $result['response'];
    }

    public function contact_update($list_id, $email_address, $status) {
        if(!$list_id) throw new TypeError("ListID must not be empty");
        $result = $this->fetch(
            "PUT",
            $this->get_host() . "lists/$list_id/members/" . md5($email_address),
            ['body' => ['status' => $status]]
        );
        return $result['response'];
    }

    private function validate_tags(array $tags):array {
        foreach($tags as $i => $v) {
            if(!is_array($v)) throw new BadRequest('`$tags` must be an array of arrays!');
            if(!key_exists("name", $v)) throw new BadRequest("Tag must include a 'name' field");
            if(!key_exists("status", $v)) throw new BadRequest("Tag must include a 'status' field");
            if(!in_array($v['status'], ['active', 'inactive'])) throw new BadRequest("Tag value is invalid!");
        }
        return $tags;
    }

    public function contact_tags(string $list_id, string $email_address, array $tags) {
        if(!$list_id) throw new TypeError("ListID must not be empty");
        $hash = md5($email_address);
        $result = $this->fetch(
            "POST",
            $this->get_host() . "lists/$list_id/members/$hash/tags",
            ['tags' => $this->validate_tags($tags)]
        );
        return $result['response'];
    }

}