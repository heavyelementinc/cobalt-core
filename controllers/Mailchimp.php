<?php

use Cobalt\Requests\Remote\Mailchimp as RemoteMailchimp;
use Controllers\Controller;
use Exceptions\HTTP\BadRequest;
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
}