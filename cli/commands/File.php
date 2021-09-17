<?php

use Drivers\Watch;
use Files\UploadManager;

class File {
    public $help_documentation = [
        'thumbnails' => [
            'description' => "Generate thumbnails for uploaded files",
            'context_required' => true
        ]
    ];

    function thumbnails($watchId) {
        $watch = new Watch($watchId);
        if (!$watch) return;
        $doc = $watch->findOne(['_id' => $watch->__id($watchId)]);
        if (!$doc) {
            $watch->queue();
            $watch->done();
            return;
        }
        $manager = new UploadManager();
        $manager->restore((array)$doc['data'][0], $doc['data'][1], $doc['data'][2]);
        $manager->generate_thumbnails(200, $watch);
    }
}

// https://www.milmike.com/run-php-asynchronously-in-own-threads