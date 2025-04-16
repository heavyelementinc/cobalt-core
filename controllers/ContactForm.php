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

    const SESSION_THROTTLE = "__contact-form--throttle-catch-count";

    private function throttleCatch() {
        $now = time();
        $two_min_ago = strtotime("-".__APP_SETTINGS__['Contact_form_submission_throttle_period'], $now);
        if(!key_exists(self::SESSION_THROTTLE, $_SESSION)) $_SESSION[self::SESSION_THROTTLE] = [];
        $cleanup = [];
        // Check if the current count of sessions 
        if(count($_SESSION[self::SESSION_THROTTLE]) > __APP_SETTINGS__['Contact_form_submission_throttle_after_max_submissions']) {
            // Run through the current items
            foreach($_SESSION[self::SESSION_THROTTLE] as $key => $item) {
                // Check if the time from this index is greater than $two_min_ago
                if($item['time'] >= $two_min_ago) {
                    // If it's greater than $two_min_ago, throw an error
                    throw new TooManyRequests("Too many requests", __APP_SETTINGS__['Contact_form_fail_message']);
                }
                $cleanup[] = $key;
            }
        }

        // Remove items from the throttle catch queue
        foreach($cleanup as $index) {
            unset($_SESSION[self::SESSION_THROTTLE][$index]);
        }


        $_SESSION[self::SESSION_THROTTLE][] = [
            'time' => $now,
        ];
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

        if(__APP_SETTINGS__['Contact_form_anti_spam_technique'] == "stepped-click") {
            update("@form", [
                'next' => view("/parts/contact-form/verify.php")
            ]);
        }
        if(__APP_SETTINGS__['Contact_form_anti_spam_technique'] == "captcha") {
            captcha_check("Please confirm you're human", array_merge($_POST, ['is_human' => 'false']));
        }
    }

    const ERROR_EMAIL         = 0b001;
    const ERROR_DETAILS       = 0b010;
    const ERROR_IS_HUMAN      = 0b100;
    const ERROR_EMAIL_FAILED  = 0b1000;
    const ERROR_SYSTEM_FAILED = 0b10000;
    const ERROR_PUSH_FAILED   = 0b100000;

    private function stage2($data) {
        $this->throttleCatch();

        /** @var Persistance */
        $className = __APP_SETTINGS__['Contact_form_validation_classname'];
        $persistance = new $className($_SESSION['__contact_form_submission']);
        $error = 0;
        // $mutant = $persistance->__validate($_SESSION['__contact_form_submission']);
        // if($data['email']) $error += self::ERROR_EMAIL;
        // if($data['details']) $error += self::ERROR_DETAILS;
        if($data['is_human'] !== "false") $error += self::ERROR_IS_HUMAN;

        if($error > 0) {
            // header("X-Message: @error Something went wrong. Please try again later");
            update("@form", [
                'next' => view("/parts/contact-form/stage2-error.php", ['error_code' => $error])
            ]);
            return 0;
        }
        $error = 0;
        $modes = __APP_SETTINGS__["Contact_form_on_success_modes"];

        $recipients = $this->getRecipients();

        switch(true) {
            // Contact form details via email
            case ($modes & CONTACT_SUCCESS_EMAIL) == CONTACT_SUCCESS_EMAIL:
                try {
                    $result = $this->contactSMTP($persistance);
                } catch (Error|Exception $e) {
                    $error += self::ERROR_EMAIL_FAILED;
                }
            // Contact form details via admin panel route
            case ($modes & CONTACT_SUCCESS_SYSTEM) == CONTACT_SUCCESS_SYSTEM:
                try {
                    $id = $this->contactPanel($persistance);
                } catch (Error|Exception $e) {
                    $error += self::ERROR_SYSTEM_FAILED;
                }
            // Contact form details via Push notification
            // case ($modes & CONTACT_SUCCESS_PUSH) == CONTACT_SUCCESS_PUSH:
                // try {
                //     $this->contactNotify($persistance, (string)$persistance->_id);
                // } catch (Error|Exception $e) {
                //     $error += self::ERROR_PUSH_FAILED;
                // }
        }
        if($error > 0) {
            update("@form", [
                'next' => view("/parts/contact-form/stage2-error.php", ['error_code' => $error])
            ]);
            return 0;
        }
        update("@form", [
            'clear' => true,
            'next' => view("/parts/contact-form/contact-complete.php")
        ]);
        $fields = new AdditionalContactFields();
        $fields->__on_submit();
    }

    private function getRecipients() {
        $crud = new UserCRUD();
        $users = $crud->getUsersByPermission(__APP_SETTINGS__['']);
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
            $crud = new UserCRUD();
            $users = $crud->getUsersByPermission(app("API_contact_form_recipients"));
            $addresses = [];
            foreach($users as $user) {
                $addresses[] = $user->email->getRaw();
            }
            $email->send($addresses, $subject);
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
                __APP_SETTINGS__['API_contact_form_recipients'],
                ['path' => $action]
            );
        } catch (\Exception $e) {
            
        }
        
        if((__APP_SETTINGS__["Contact_form_on_success_modes"] & CONTACT_SUCCESS_NOTIFY) == CONTACT_SUCCESS_NOTIFY) {
            $this->contactNotify($mutant, $id);
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
