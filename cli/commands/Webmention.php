<?php

use MongoDB\BSON\ObjectId;
use PEAR2\Services\Linkback\Client;

class Webmention {
    public $help_documentation = [
        'dispatch' => [
            'description' => "[classname [id]] - Executes Webmention",
            'context_required' => true
        ]
    ];

    function dispatch($className, $id) {
        // sleep(2);
        /** @var \Drivers\Database */
        $manager = new $className();
        $_id = new ObjectId($id);
        /** @var \Webmentions\WebmentionDocument $doc */
        $doc = $manager->findOne(['_id' => $_id]);
        if(!$doc) return;
        if($doc->webmention_is_locked()) return;
        $doc->webmention_lock();
        $responses = [];
        try {
            foreach($doc->webmention_get_urls_to_notify() as $link) {
                $linkbackClient = new Client();
                $request = $linkbackClient->getRequest();
                $request->setConfig([
                    'ssl_verify_peer' => false,
                    'ssl_verify_host' => false,
                ]);
                $request->setHeader('user-agent', 'Cobalt Engine Webmention Discovery Bot');
                $linkbackClient->setRequestTemplate($request);
                $response = $linkbackClient->send($doc->webmention_get_canonincal_url(), $link);
                array_push($responses, $response);
            }
        } catch(Exception $e) {
            $doc->webmention_unlock();
            return;
        }

        $doc->webmention_unlock();
    }
}