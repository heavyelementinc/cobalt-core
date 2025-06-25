<?php

namespace Cobalt\Notifications\Models;

use Auth\UserCRUD;
use Auth\UserPersistance;
use Cobalt\Maps\PersistanceMap;
use Cobalt\Notifications\Classes\NotificationManager;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\IpResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Cobalt\SchemaPrototypes\MapResult;
use Drivers\Database;
use Error;
use Exception;

class NotificationSchema extends PersistanceMap {
    const NOTIFY_SEEN  = 0b0001;
    const NOTIFY_READ  = 0b0010;
    const NOTIFY_MUTED = 0b0100;
    const NOTIFICATION_EMAIL_SENT = 0b001;

    function __set_manager(?Database $manager = null): ?Database {
        return new NotificationManager();
    }

    public function __get_schema(): array {
        $addressee = new NotificationAddresseeSchema();
        return [
            'from' => [
                new UserIdResult("Notifications_can_send_notification"),
                'nullable' => true,
                'coalesce' => true,
                'default' => [
                    'uname' => 'Web Admin',
                ]
            ],
            'for' => [
                new ArrayResult,
                'each' => $addressee->__get_schema(),
                'facepile' => function () {
                    $uman = new UserCRUD();
                    $facepile = "";
                    /** @var UserPersistance $data */
                    foreach($this->for->getValue() as $data) {
                        $u = $uman->findOne(['_id' => $data->user]);
                        $pile_entry = "<img src=\"%s\" class=\"cobalt--facepile-item\" height=\"16\" width=\"16\">";
                        try {
                            $facepile .= sprintf($pile_entry, $u?->avatar?->getValue()['url'] ?? "/core-content/img/unknown-user.thumb.jpg");
                        } catch (Error $e) {
                            $facepile .= sprintf($pile_entry, "/core-content/img/unknown-user.thumb.jpg");
                        } catch (Exception $e) {
                            $facepile .= sprintf($pile_entry, "/core-content/img/unknown-user.thumb.jpg");
                        }
                    }
                    return "<div class='cobalt--facepile'><i name='arrow-right-bold-circle'></i><span class='cobalt--facepile-container'>$facepile</span></div>";
                },
                'display' => function () {
                    $facepile = "<ul>";
                    /** @var UserPersistance $user */
                    foreach($this->for->getValue() as $user) {
                        $facepile .= "<li>".$user->name->tag()."</li>";
                    }
                    return "$facepile</ul>";
                },
                'attributes' => function ($user = null) {
                    if($user === null) $user = session();
                    $uid = $user->_id;
                    $addressee = null;
                    foreach($this->for->getValue() as $data) {
                        if((string)$data->user->_id === (string)$uid) {
                            $addressee = $data;
                            break;
                        }
                    }
                    $seen = "false";
                    $read = "false";
                    if($addressee !== null) {
                        $seen = ($addressee->seen) ? "true" : "false";
                        $read = ($addressee->read) ? "true" : "false";
                    }
                    return "seen=\"$seen\" read=\"$read\"";
                }
            ],
            'read_status' => new ArrayResult,
            'subject' => [
                new StringResult,
                'char_limit' => 80
            ],
            'body' => [
                new MarkdownResult
            ],
            /** Automatically set by the sendNotification method */
            'action' => [
                new MapResult,
                'schema' => [
                    'href' => new StringResult,
                    'route' => new StringResult,
                    'params' => new ArrayResult,
                ],
                'attributes' => function() {
                    return "action=\"".$this->action->href."\"";
                    // $action = nullable_route($this->action->route->getValue(), $this->action->params->getValue());
                    // return "action=\"$action\"";
                }
            ],
            'type' => [
                new EnumResult,
                'default' => 0,
                'valid' => [
                    0 => "Notification"
                ],
            ],
            'sent' => [
                new DateResult,
            ],
            'ip' => [
                new IpResult,
                'nullable' => true
            ],
            'template' => [
                new StringResult,
                'default' => "/Cobalt/Notifications/templates/types/notification-1.0.php"
            ],
            // 'token' => new StringResult,
            'version' => [
                new StringResult,
                'default' => '1.0'
            ]
        ];
    }

    function getUserIdsByUsernames() {

    }

    function getTemplate() {
        return $this->template->getValue();
    }

    function getHref() {
        $href = "";
        if($this->action->href) $href = $this->action->href;
        if(!$href) {
            $href = route($this->action->route, $this->action->params);
        }
        return server_name().$href;
    }

}