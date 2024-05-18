<?php

use Cobalt\Integrations\MailChimp\MailChimp as MailChimpIntegration;
use Cobalt\Requests\Remote\Mailchimp as RemoteMailchimp;
use Controllers\Controller;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Error;
use Exceptions\HTTP\ServiceUnavailable;

class Mailchimp extends Controller {
    var $mc;
    function __construct() {
        $this->mc = new RemoteMailchimp();
    }

    function onboard() {
        if(!$_POST['email']) throw new BadRequest("You need to supply your email address.");
        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) throw new BadRequest("That doesn't look like a valid email address.");
        $test = $this->mc->getAPIStatus();
        
        if(!$test) throw new BadRequest("Mailing list servers are unavailable. Try again later.");
        
        $alreadyExists = $this->mc->emailSubscriptionStatus($_POST['email']);

        $success = "@success Watch your inbox! You've been subscribed to our newsletter!";
        $action = "@email Check your inbox! A subscription email is waiting for you to accept!";
        switch($alreadyExists) {
            case false:
            case "":
                $subscribe = $this->mc->insertEmailToAudience($_POST, 'subscribed');
                header("HTTP/1.0 201 Created");
                break;
            case "subscribed":
                $success = "Your're already subscribed!";
                header("HTTP/1.0 204 No content");
                break;
            case "pending":
                $success = $action;
                header("HTTP/1.0 204 No content");
                break;
            default:
                $success = $action;
                header("HTTP/1.0 202 Accepted");
                $subscribe = $this->mc->updateEmailInAudience($_POST['email'], 'pending');
                break;
        }
        
        // $_SESSION['mailchimp-onboarded'] = true;
        header("X-Status: $success");
        return $subscribe;
    }

    function onboard_landing() {
        add_vars([
            'title' => 'Email Newsletter',
        ]);
        return view("/parts/mailchimp-onboarding.html");
    }

    function onboarding() {
        $id = __APP_SETTINGS__['Mailchimp_default_list_id'];
        if(!$id) throw new Error("Default list ID is not specified!","Application misconfiguration");
        $mc = new MailChimpIntegration();

        $status = $mc->contact_exists($id, $_POST['email']);
        if(!$status) {
            $result = $mc->contact_add($id, [
                'email_address' => $_POST['email'],
                'status' => 'subscribed',
                'merge_fields' => [
                    'FNAME' => $_POST['fname'],
                    'LNAME' => $_POST['lname'],
                ]
            ]);
        } else {
            $result = $mc->contact_update($id, $_POST['email'], "subscribed");
        }
        
        header("X-Status: @success You've been added to our newsletter!");

        $mc->contact_tags($id, $_POST['email'], [
            [
                'name' => 'Newsletter',
                'status' => 'active'
            ]
        ]);

        update("localStorage", ['set' => [
            'newsletterSignUp' => 0,
            'newsletterDate' => date("c"),
        ]]);
    }
}