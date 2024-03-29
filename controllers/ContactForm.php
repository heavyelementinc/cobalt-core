<?php

use Cobalt\Notifications\PushNotifications;
use Contact\ContactManager;
use Controllers\Controller;
use Exceptions\HTTP\HTTPException;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\ServiceUnavailable;
use Exceptions\HTTP\TooManyRequests;
use Mail\SendMail;
use MongoDB\BSON\ObjectId;

class ContactForm extends Controller {

    function index() {
        $conMan = new ContactManager();
        $results = $conMan->find(...$this->getParams($conMan,[],[],[],['sort' => ['date' => -1]]));
        $lines = "";
        foreach($results as $doc) {
            $lines .= view("/admin/contact-form/index-item.html", ['doc' => $doc]);
        }
        // $lines = $this->docsToViews($results, );
        add_vars([
            'title' => 'Contact Form Submissions',
            'lines' => $lines
        ]);

        return view("/admin/contact-form/index.html");
    }

    function read_status($id) {
        $conMan = new ContactManager();
        $status = $_POST;
        $_id = new ObjectId($id);
        if($status === false) return $conMan->unread_for_user($_id, session());
        return $conMan->read_for_user($_id, session());
    }

    function read($id) {
        $conMan = new ContactManager();
        $_id = $conMan->__id($id);
        $found = $conMan->findOne(['_id' => $_id]);
        if(!$found) throw new NotFound("Not found", "That resource does not exist");
        add_vars([
            'title' => "Contact",
            'doc' => $found,
        ]);

        $conMan->read_for_user($_id, session());

        $unread = (new ContactManager())->get_unread_count_for_user(session());
        $update = "innerHTML";
        $query = "[href=\"/admin/contact-form/\"] .unread";
        if($unread === 0) $unread = "";
        update($query, [$update => $unread]);

        return set_template("/admin/contact-form/read.html");
    }
    
    function delete($id) {
        confirm("Are you sure you want to delete this item? (There is no undoing this!)",$_POST);
        $result = (new ContactManager())->delete_submission($id);
        header("X-Location: /admin/contact-form/");
        return $result;
    }

    function contact_submit() {
        $className = __APP_SETTINGS__['Contact_form_validation_classname'];
        $persistance = new $className();
        $mutant = $persistance->__validate($_POST);
        $mutant->ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $mutant->token = $_SERVER["HTTP_X_CSRF_MITIGATION"];
        $mutant->date  = new \MongoDB\BSON\UTCDateTime();

        // $persistance->ingest($mutant);
        switch(app("Contact_form_interface")) {
            case "SMTP":
                $result = $this->contactSMTP($persistance);
                header("X-Status: @info " . app("Contact_form_success_message"));
                break;
            case "panel":
            default:
                $id = $this->contactPanel($persistance);
                header("X-Status: @info " . app("Contact_form_success_message"));
                return $id;
                break;
        }
        return "error";
    }

    private function contactSMTP($mutant) {
        $email = new SendMail();
        $email->set_vars(array_merge(
            $mutant,
            ['POST' => $_POST]));
        $email->set_body_template("/emails/contact-form.html");
        try {
            $subject = "New contact form submission";
            if (key_exists("subject", $_POST)) $subject = "Webform: \"" . strip_tags($_POST['subject'] . "\"");
            $email->send(app("API_contact_form_recipient"), $subject);
        } catch (Exception $e) {
            throw new ServiceUnavailable("There was an error on our end.");
        }
        return $mutant;
    }

    private function contactPanel($mutant) {
        $backend = new ContactManager();

        $throttle = iterator_to_array($backend->find(['ip' => $mutant['ip']], ['sort' => ['date' => -1]]));
        // if(count($throttle) > 3) {
        //     $now = (new \MongoDB\BSON\UTCDateTime())->toDateTime()->getTimestamp();
        //     $then = $throttle[0]->date->toDateTime()->getTimestamp();
        //     if($now - $then <= app("Contact_form_submission_throttle")) {
        //         sleep(5);
        //         throw new TooManyRequests("Looks like you've already submitted a few.");
        //     }
        // }

        try {
            $result = $backend->insertOne($mutant);
        } catch (\Exception $e) {
            throw new ServiceUnavailable("An unknown error occurred");
        }
        try{
            $push = new PushNotifications();
            $push->push(
                'Contact Submission',
                "Someone has filled out the {{app.app_name}} contact form!",
                ['contact_form_new'],
                ['path' => "/admin/contact-form/".(string)$result->getInsertedId()]
            );
        } catch (\Exception $e) {
            
        }
        
        if(app("Contact_form_notify_on_new_submission")) {
            // $notify = new Notification1_0Schema([
            //     'subject' => 'New contact form submission',
            //     'body' => "Name: *$mutant[name]*\n$mutant[additional]",
            //     ''
            // ]);
        }
        $id = $result->getInsertedId();

        return $id;
    }

}
