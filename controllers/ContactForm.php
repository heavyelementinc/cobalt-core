<?php

use Cobalt\Maps\GenericMap;
use Cobalt\Notifications\PushNotifications;
use Contact\ContactManager;
use Contact\Persistance;
use Controllers\Controller;
use Controllers\Crudable;
use Drivers\Database;
use Exceptions\HTTP\HTTPException;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\ServiceUnavailable;
use Exceptions\HTTP\TooManyRequests;
use Mail\SendMail;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

class ContactForm extends Crudable {
    function get_manager(): Database {
        return new ContactManager();
    }

    function get_schema($data):GenericMap {
        return new Persistance();
    }

    function edit($document):string {
        return view("/admin/contact-form/read.html");
    }

    function destroy(GenericMap|BSONDocument $document):array {
        return [
            "message" => "Message from $document->name"
        ];
    }

    function index():string {
        // $conMan = new ContactManager();
        // $results = $conMan->find(...$this->getParams($conMan,[],[],[],['sort' => ['date' => -1]]));
        // $lines = "";
        // foreach($results as $doc) {
        //     $lines .= view("/admin/contact-form/index-item.html", ['doc' => $doc]);
        // }
        // // $lines = $this->docsToViews($results, );
        // add_vars([
        //     'title' => 'Contact Form Submissions',
        //     'lines' => $lines
        // ]);
        return view("/admin/contact-form/index.html");
    }

    function read_status($id) {
        $conMan = new ContactManager();
        $status = $_POST;
        $_id = new ObjectId($id);
        if($status === false) return $conMan->unread_for_user($_id, session());
        return $conMan->read_for_user($_id, session());
    }

    function read($document): GenericMap|BSONDocument|null {
        return $document;
    }

    function read_old($id) {
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

        return view("/admin/contact-form/read.html");
    }
    
    // function delete($id) {
    //     confirm("Are you sure you want to delete this item? (There is no undoing this!)",$_POST);
    //     $result = (new ContactManager())->delete_submission($id);
    //     header("X-Location: /admin/contact-form/");
    //     return $result;
    // }

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
        $two_min_ago = strtotime("-".__APP_SETTINGS__['Contact_form_submission_throttle_period'], time()) * 1000;
        $now = new UTCDateTime($two_min_ago);
        $throttle = $backend->count(['ip' => (string)$mutant->ip, 'date' => ['$gte' => $now]]);
        if($throttle >= __APP_SETTINGS__['Contact_form_submission_throttle_after_max_submissions']) {
            throw new TooManyRequests("Too many requests", __APP_SETTINGS__['Contact_form_fail_message']);
        }

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
