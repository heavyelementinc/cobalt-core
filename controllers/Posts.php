<?php

use Cobalt\Posts\PostManager;
use Controllers\ClientFSManager;
use Controllers\PostController;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\PostNotFound;
use MongoDB\BSON\ObjectId;

class Posts extends PostController {
    use ClientFSManager;
    function __construct() {
        $this->initialize(__APP_SETTINGS__['Posts']['collection_name']);
    }

    function upload($id) {
        $id = new ObjectId($id);
        $query = ['_id' => $id];
        $post = $this->postMan->findOne($query);

        if(!$post) throw new PostNotFound("There's no post matching that ID");
        $schemaName = $this->postMan->get_schema_name();
        $schema = new $schemaName(array_merge($query,['url_slug' => $post->{'url_slug'}]));
        $valid = $schema->validate(['attachments' => $_FILES]);
        // update("#gallery", ['style' => ['background-image' ]])
        return ['#gallery' => $schema->{'attachments.display'}];
    }

    function downloadFile($slug, $filename) {
        $this->download($filename);
    }


    function defaultImage($id) {
        $_id = new ObjectId($id);
        $this->initFS();
        $result = $this->fs->findOne(["_id" => $_id]);
        if(!$result) throw new NotFound("No file found.");
        $doc = $this->postMan->findOneAsSchema(['_id' => $result->for]);
        if(!$doc) throw new NotFound("No document paired with this file.");

        $this->postMan->updateOne(['_id' => $doc->_id], [
            '$set' => [
                'default_image' => $result->filename
            ]
        ]);

        $doc = $this->postMan->findOneAsSchema(['_id' => $result->for]);

        update("#default_image", ['style' => ['background-image' => "url('$doc->default_image')"]]);

        return $result;
    }
}
