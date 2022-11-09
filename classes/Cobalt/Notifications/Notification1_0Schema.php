<?php

namespace Cobalt\Notifications;
/**
 * * subject
 * * body
 * * from
 * * type
 * * for - user
 * * action
 * * action.params
 * * action.route
 * * action.path
 * 
 * @param array $notification 
 */
class Notification1_0Schema extends \Validation\Normalize {
    function __get_schema(): array {
        return [
            'subject' => [
                'max_char_length' => 80
            ],
            'body' => [],
            'sent' => [
                'get' => fn ($val) => $this->get_date($val, 'relative'),
                'set' => fn ($val) => $this->make_date($val),
            ],
            'from' => [
                'get' => fn ($val) => $this->user($val),
                'set' => fn ($val) => $this->user_id($val),
                'display' => function ($val) {

                }
            ],
            'type' => [],
            'for' => [
                "each" => [
                    'user' => [
                        'get' => fn ($val) => $this->user($val),
                        'set' => fn ($val) => $this->user_id($val),
                    ],
                    'read' => [
                        'set' => fn ($val) => $this->boolean_helper($val)
                    ],
                    'recieved' => [
                        'get' => fn ($val) => $this->get_date($val, 'relative'),
                        'set' => fn ($val) => $this->make_date($val)
                    ]
                ],
                'display' => function ($val) {
                    $id = (string)session()['_id'];
                    $me = "";
                    foreach($val as $user){
                        if((string)$user['user'] !== $id) continue;
                        $me = "you";
                        break;
                    }
                    $returnString = "";
                    $and = "";
                    if($me) {
                        $returnString .= $me;
                        $and = " and ";
                    }
                    $count = count($val) - 1;
                    switch($count) {
                        case 0:
                            $others = "";
                            $and = "";
                            break;
                        case 1:
                            $others = "$count other user";
                            break;
                        default:
                            $others = "$count other users";
                            break;
                    }
                    return $me . $and . $others;
                }
            ],

            // We need a way to make notifications actionable
            'action' => [
                'get' => 'get_action',
                'set' => null
            ],
            'action.params' => [
                'type' => 'array'
            ],
            'action.context' => [],
            'action.route' => [
                'set' => function ($val) {
                    validate_route($val, $this->{'action.context'});
                }
            ],
            'action.path' => [],
            "ip" => ['set' => $_SERVER['REMOTE_ADDR']],
            "token" => ['set' => $_SERVER["HTTP_X_CSRF_MITIGATION"]]
        ];
    }

    function get_action($params) {
        $route = $this->{'action.path'};
        if(!$route && $this->{'action.route'}) $route = route($this->{'action.route'},$params['params']);
        else $route = route_replacement($route, $this->{'action.params'});
        return $route;
    }

    function getTemplate() {
        return "/cobalt/notifications/notification-1.0.html";
    }

    function render() {
        return view($this->getTemplate(), ['ntfy' => $this]);
    }
}

/* 'for.$.user' => [
    'set' => function ($value) {
        return new \MongoDB\BSON\ObjectId($value);
    }
],
'for.$.read' => [
    'set' => fn ($val) => $this->boolhelper($val),
    'default' => false
],
'for.$.recieved' => [
    'get' => fn ($val) => $this->get_date($val, 'verbose'),
    'set' => fn ($val) => $this->make_date($val)
],*/
