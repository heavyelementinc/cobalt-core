<?php

namespace Cobalt\ContactForm\Controllers;

use Auth\UserCRUD;
use Cobalt\ContactForm\Model\AdditionalContactFields;
use Cobalt\ContactForm\Model\FormSubmission;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Notifications\Classes\NotificationManager;
use Cobalt\Notifications\Classes\PushNotifications;
use Cobalt\Notifications\Models\NotificationSchema;
use Contact\ContactManager;
use Error;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\ServiceUnavailable;
use Exceptions\HTTP\TooManyRequests;
use Mail\SendMail;
use MongoDB\Model\BSONDocument;

class Submissions extends ModelController {
    static $api_read_permission          = "Contact_form_submissions_access";
    static $api_create_permission        = "Contact_form_submissions_access";
    static $api_update_permission        = "Contact_form_submissions_access";
    static $api_destroy_permission       = "Contact_form_submissions_access";
    static $api_multidestroy_permission  = "Contact_form_submissions_access";
    static $api_batch_archive_permission = "Contact_form_submissions_access";
    static $api_archive_permission       = "Contact_form_submissions_access";
    static $admin_index                  = "Contact_form_submissions_access";
    static $admin_new_document           = "Contact_form_submissions_access";
    static $admin_edit                   = "Contact_form_submissions_access";

    public static function defineModel(): Model {
        return new FormSubmission();
    }

    public function edit($document): string {
        return view("/Cobalt/ContactForm/templates/admin/edit.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [

        ];
    }


    /** PUBLIC SUBMISSION API */

    function public_form_submission() {
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
                'next' => view("Cobalt/ContactForm/templates/web/stage-2--stepped-click.php")
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
                'next' => view("Cobalt/ContactForm/templates/web/stage-2--error.php", ['error_code' => $error])
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
                'next' => view("Cobalt/ContactForm/templates/web/stage-2--error.php", ['error_code' => $error])
            ]);
            return 0;
        }
        update("@form", [
            'clear' => true,
            'next' => view("Cobalt/ContactForm/templates/web/stage-3--contact-complete.php")
        ]);
        $fields = new AdditionalContactFields();
        $fields->onSubmit();
    }

    private function getRecipients() {
        $crud = new UserCRUD();
        $users = $crud->getUsersByPermission(__APP_SETTINGS__['Contact_form_user_permissions_to_notify']);
        return $users;
    }

    private function contactSMTP($mutant) {
        $email = new SendMail();
        $email->set_vars(array_merge(
            $mutant,
            ['POST' => $_POST]));
        $email->set_body_template("Cobalt/ContactForm/templates/emails/form-submission.html");
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
        $to = NotificationManager::getAddresseesByPermission("Contact_form_submissions_access");
        $notification = new NotificationSchema([
            'from' => null,
            'for' => $to,
            'subject' => 'New Contact Form Submission',
            'body' => "**$mutant->name** filled out your contact form:\n\n".trim(substr($mutant->additional,0, 100)),
            'action' => [
                'href' => route("Cobalt\\ContactForm\\Controllers\\Submissions@__edit",[$href])
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
}