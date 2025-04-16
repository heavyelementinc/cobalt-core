<?php
namespace Cobalt\Notifications\Controllers;

use Auth\UserCRUD;
use Cobalt\Notifications\Classes\NotificationManager;
use Cobalt\Notifications\Classes\PushNotifications;
use Cobalt\Notifications\Models\NotificationAddresseeSchema;
use Cobalt\Notifications\Models\NotificationSchema;
use Controllers\Controller;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Notifications extends Controller {
    private $ntfy;

    function __construct() {
        $this->ntfy = new NotificationManager();
    }
    
    function getUserNotifications() {
        header("Content-Type: text/html");
        $notes = "";
        // $state = match($_GET['state']) {
        //     "all" => false,
        //     "unread" => true,
        //     default => false
        // };
        $filter = [
            'sort' => ['sent' => (int)$_GET['sort']]
        ];
        foreach($this->ntfy->getNotificationsForUser(null, (int)$_GET['state'], $filter) as $note) {
            $notes .= view($note->getTemplate(), ['ntfy'=> $note]);
        }

        if(!$notes) return "Nothing here.";

        return $notes;
    }

    function one_notification($id) {
        if(has_permission('Notifications_can_access_any_notification')) {
            return $this->getOneNoteById($id);
        }
        return $this->getOneNoteByIdForUser($id);
    }

    private function getOneNoteByIdForUser($id) {
        header("Content-Type: text/html");

        $note = $this->ntfy->findOneAsSchema([
            '_id' => new ObjectId($id),
            '$or' => [
                ['for.user.id' => session('_id')],
                ['from' => session('_id')]
            ]
        ]);

        if(!$note) throw new NotFound("The specified resource was not found");

        return view($note->getTemplate(), ['ntfy' => $note]);
    }

    private function getOneNoteById($id) {
        header("Content-Type: text/html");

        $note = $this->ntfy->findOne(['_id' => new ObjectId($id)]);

        if(!$note) throw new NotFound("The specified resource was not found");

        return view($note->getTemplate(), ['ntfy' => $note]);
    }

    function getUserNotificationCount() {
        return $this->ntfy->getUnreadNotificationCountForUser();
    }

    function sendNotification() {
        $note = new NotificationSchema();
        $submission = $_POST;        
        $users = $submission['users'];
        $submission['users'] = [];

        foreach($submission['for'] as $id) {
            $schema = new NotificationAddresseeSchema();
            array_push($submission['users'], $schema->__validate(['user' => $id]));
        }
        
        
        $mutant = $note->__validate($submission);
        
        if(!key_exists('action', $submission)) {
            $mutant->action->path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        }
        return $this->ntfy->sendNotification($mutant);
    }

    function pushNotification($recipient = null) {
        if(!$recipient || $recipient === "root") $recipient = 'root';
        else $recipient = new ObjectId($recipient);

        $push = new PushNotifications();
        $push->push('Test Subject', 'Hello {{user.fname}}, this is a test of your push notifications.', [$recipient], ['details' => "Here's a secret message from uncharted space"]);
    }

    function delete_one($id) {
        $_id = new ObjectId($id);
        $query = ['_id' => $_id];
        $note = $this->ntfy->findOne($query);
        if(!$note) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        // confirm("Are you sure you want to delete this notification?", $_POST);
        $result = $this->ntfy->deleteOne($query);
        return $result->getDeletedCount();
    }

    function state($id) {
        $key = array_keys($_POST)[0];
        $value = $_POST[$key];
        $_id = new ObjectId($id);
        $note = $this->ntfy->findOne(['_id' => $_id]);
        if(!$note) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);

        $modified = $this->ntfy->setReadState($_id, session('_id'), $value, $key);
        update("[data-id='$id']", [
            'setAttribute' => [
                'seen' => $value,
                'read' => $value,
            ]
        ]);
        return $modified;
    }

    function debug() {
        $note = new NotificationSchema();
        $note->subject = "Hello, World";
        $note->body = "Here's some **markdown** to use for a test";
        $note->from = null;
        $note->for = [session()['_id']];
        $note->action = [
            'route' => 'CoreAdmin@individual_user_management_panel',
            'params' => [
                session()['_id']
            ]
        ];
        $note->sent = new UTCDateTime();

        
        // [
        //     '_id' => new ObjectId(),
        //     'version' => '1.0',
        //     'subject' => 'Hello, World',
        //     'body'    => "Here's some **markdown** to use for a test",
        //     'sent'    => strtotime("-1 day") * 1000,
        //     'from'    => session()["_id"],
        //     'for'     => [
        //         // [
        //         //     'user' => "8888888888888888",
        //         //     'read' => false,
        //         //     'recieved' => new \MongoDB\BSON\UTCDateTime(1661612937423)
        //         // ],
        //         [
        //             'user' => session()["_id"],
        //             'read' => false,
        //             'recieved' => new \MongoDB\BSON\UTCDateTime(1661612937423)
        //         ]
        //     ],
        //     'action' => [
        //         // 'path'    => "/",
        //         'route'   => 'CoreAdmin@individual_user_management_panel',
        //         'params'  => [
        //             session()['_id']
        //         ],
        //     ]
        // ];

        $ntfy = new NotificationManager();

        add_vars([
            'title' => "Notifications Debug",
            'notifications' => $ntfy->renderNotification($note)
        ]);

        set_template("debug/notifications.html");
    }

    function addressees() {
        $query = $_GET['search'];
        $ua = new UserCRUD();
        $regex = new \MongoDB\BSON\Regex($query);
        $results = $ua->find([
            '$or' => [
                ['uname' => $regex],
                ['fname' => $regex],
                ['lname' => $regex],
            ]
        // ], 
        // [
        //     'projection' => [
        //         'uname'  => 1,
        //         'fname'  => 1,
        //         'lname'  => 1,
        //         'avatar' => 1,
        //     ]
        ]);

        $r = [];

        foreach($results as $user) {
            $r[] = [
                '_id'   => (string)$user->_id,
                'uname' => (string)$user->uname,
                'fname' => (string)$user->fname,
                'lname' => (string)$user->lname,
                'avatar' => (string)$user->avatar,
                'value' => (string)$user->_id
            ];
        }

        return $r;
    }

    function addresseeList($id) {
        $_id = new ObjectId($id);
        $nt = $this->ntfy->findOne(["_id" => $_id]);
        
    }

    function edit_notification($id) {
        // header("Content-Type: text/html");
        $note = $this->ntfy->findOneAsSchema([
            '_id' => new ObjectId($id),
            '$or' => [
                ['from' => session('_id')],
                ['for.user.id' => session('_id')],
            ]
        ]);
        if(!$note) throw new NotFound("Specified resource was not found");
        
        add_vars([
            'title' => "Editing notification: ".(string)$note->_id,
        ]);

        return view("/Cobalt/Notifications/templates/edit.html", [
            'ntfy' => $note,
            'json' => syntax_highlighter($note, "")
        ]);
    }

    function vapid_pub_key(){
        // header("Content-Type: application/json;charset=UTF-8");
        return (new PushNotifications())->vapid_keys->keyset->publicKey;
        // exit;
    }

    function push_test() {
        return $this->pushNotification(session()['_id']);
    }
}
