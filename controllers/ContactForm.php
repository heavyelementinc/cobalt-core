<?php

use Auth\UserCRUD;
use Cobalt\Maps\GenericMap;
use Cobalt\Notifications\Classes\NotificationManager;
use Cobalt\Notifications\Classes\PushNotifications;
use Cobalt\Notifications\Models\NotificationSchema;
use Contact\AdditionalContactFields;
use Contact\ContactManager;
use Contact\Persistance;
use Controllers\Controller;
use Controllers\Crudable;
use Drivers\Database;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\HTTPException;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\ServiceUnavailable;
use Exceptions\HTTP\TooManyRequests;
use Mail\SendMail;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

/** @package  */
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

    // function index():string {
    //     // $conMan = new ContactManager();
    //     // $results = $conMan->find(...$this->getParams($conMan,[],[],[],['sort' => ['date' => -1]]));
    //     // $lines = "";
    //     // foreach($results as $doc) {
    //     //     $lines .= view("/admin/contact-form/index-item.html", ['doc' => $doc]);
    //     // }
    //     // // $lines = $this->docsToViews($results, );
    //     // add_vars([
    //     //     'title' => 'Contact Form Submissions',
    //     //     'lines' => $lines
    //     // ]);
    //     return view("/admin/contact-form/index.html");
    // }

    function read_status($id) {
        $conMan = new ContactManager();
        $status = $_POST;
        $_id = new ObjectId($id);
        if($status === false) return $conMan->unread_for_user($_id, session());
        return $conMan->read_for_user($_id, session());
    }

    function read($document): GenericMap|BSONDocument|null {
        $this->manager->read_for_user($document->_id, session());

        $unread = (new ContactManager())->get_unread_count_for_user(session());
        $update = "innerHTML";
        $query = "[href=\"/admin/contact-form/\"] .unread";
        if($unread === 0) $unread = "";
        update($query, [$update => $unread]);
        return $document;
    }

    function read_old($id) {
        $conMan = new ContactManager();
        $_id = $conMan->__id($id);
        $found = $conMan->findOne(['_id' => $_id]);
        if(!$found) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        add_vars([
            'title' => "Contact",
            'doc' => $found,
        ]);

        return view("/admin/contact-form/read.html");
    }
    
    // function delete($id) {
    //     confirm("Are you sure you want to delete this item? (There is no undoing this!)",$_POST);
    //     $result = (new ContactManager())->delete_submission($id);
    //     header("X-Location: /admin/contact-form/");
    //     return $result;
    // }

    function contact_submit() {
        $mode = (isset($_POST['is_human'])) ? "stage2" : "stage1";

        switch($mode) {
            case "stage1":
                return $this->stage1($_POST);
            case "stage2":
                return $this->stage2($_POST);
        }
        
        throw new BadRequest("Bad request");
    }

    private function stage1($data) {
        $className = __APP_SETTINGS__['Contact_form_validation_classname'];
        /** @var Persistance */
        $persistance = new $className();
        $mutant = $persistance->__validate($data);
        $mutant->ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $mutant->token = $_SERVER["HTTP_X_CSRF_MITIGATION"];
        $mutant->date  = new \MongoDB\BSON\UTCDateTime();

        $_SESSION['__contact_form_submission'] = $mutant->jsonSerialize();
        update("@form", [
            'next' => view("/parts/contact-form/verify.php")
        ]);

    }

    private function stage2($data) {
        /** @var Persistance */
        $className = __APP_SETTINGS__['Contact_form_validation_classname'];
        $persistance = new $className($_SESSION['__contact_form_submission']);
        $error = 0;
        // $mutant = $persistance->__validate($_SESSION['__contact_form_submission']);
        if($data['email']) $error += 0b001;
        if($data['details']) $error += 0b010;
        if($data['is_human'] !== "false") $error += 0b100;

        if($error > 0) {
            // header("X-Message: @error Something went wrong. Please try again later");
            update("@form", [
                'next' => view("/parts/contact-form/stage2-error.php", ['error_code' => $error])
            ]);
            return 0;
        }

        switch(app("Contact_form_interface")) {
            case "SMTP":
                $result = $this->contactSMTP($persistance);
                break;
            case "panel":
            default:
                $id = $this->contactPanel($persistance);
                break;
        }
        update("@form", [
            'clear' => true,
            'next' => view("/parts/contact-form/contact-complete.php")
        ]);
        $fields = new AdditionalContactFields();
        $fields->__on_submit();
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

    private function contactNotify($mutant, $href) {
        $ntfy = new NotificationManager();
        $to = [];
        $userCrud = new UserCRUD();
        $users = $userCrud->getUsersByPermission("Contact_form_submissions_access");
        foreach($users as $u) {
            $to[] = [
                'user' => $u->_id,
                'state' => 0,
                'modified' => new UTCDateTime()
            ];
        }
        $notification = new NotificationSchema([
            'from' => null,
            'for' => $to,
            'subject' => 'New Contact Form Submission',
            'body' => "**$mutant->name** filled out your contact form:\n\n".trim(substr($mutant->additional,0, 100)),
            'action' => [
                'href' => route("ContactForm@__edit",[$href])
            ],
            'type' => 0,
        ]);
        $ntfy->sendNotification($notification);
    }

    private function contactPanel($mutant) {
        $backend = new ContactManager();
        $two_min_ago = strtotime("-".__APP_SETTINGS__['Contact_form_submission_throttle_period'], time()) * 1000;
        $now = new UTCDateTime($two_min_ago);
        $throttle = $backend->count(['ip' => (string)$mutant->ip, 'date' => ['$gte' => $now]]);
        // if($throttle >= __APP_SETTINGS__['Contact_form_submission_throttle_after_max_submissions']) {
        //     throw new TooManyRequests("Too many requests", __APP_SETTINGS__['Contact_form_fail_message']);
        // }
        
        try {
            $result = $backend->insertOne($mutant);
            $id = $result->getInsertedId();
            $action = "/admin/contact-form/".(string)$id;
            $method = "GET";
        } catch (\Exception $e) {
            throw new ServiceUnavailable("An unknown error occurred");
        }
        try{
            $push = new PushNotifications();
            $push->push(
                'Contact Submission',
                "Someone has filled out the {{app.app_name}} contact form!",
                ['contact_form_new'],
                ['path' => $action]
            );
        } catch (\Exception $e) {
            
        }
        $this->contactNotify($mutant, $id);

        if(app("Contact_form_notify_on_new_submission")) {
            // $notify = new Notification1_0Schema([
            //     'subject' => 'New contact form submission',
            //     'body' => "Name: *$mutant[name]*\n$mutant[additional]",
            //     ''
            // ]);
        }

        return $id;
    }

    static public function route_details_create():array {
        return ['permission' => 'Contact_form_submissions_access'];
    }

    static public function route_details_index():array {
        return ['permission' => 'Contact_form_submissions_access'];
    }

    static public function route_details_destroy(): array {
        return ['permission' => 'Contact_form_submissions_delete'];
    }

    static public function route_details_read(): array {
        return ['permission' => 'Contact_form_submissions_access'];
    }

    static public function route_details_update(): array {
        return ['permission' => 'Contact_form_submissions_access'];
    }
}
