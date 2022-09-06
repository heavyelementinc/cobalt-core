<?php
namespace Posts;
use Exceptions\HTTP\NotFound;

class PostSchema extends \Validation\Normalize {

    public function __get_schema(): array {
        return [
            'author' => [
                'get' => [],
                'display' => function ($val) {
                    return "";
                }
            ],
            'title' => [],
            'url_slug' => [],
            'published' => [
                'set' => fn ($val) => $this->boolean_helper($val)
            ],
            'publicationDate' => [
                'set' => fn ($val) => $this->make_date($val),
                'display' => fn ($val) => $this->get_date($val),
            ],
            'body' => [],
            'excerpt' => [
                'max_char_length' => 200
            ],
            'attachments' => []
        ];
    }

    public function getTemplate($type = "post"):string {
        $types = [
            'post' => "/posts/pages/individual.html",
            'public' => "/posts/pages/index.html",
            'blurb' => "/posts/parts/index-blurb.html",
            // Admin pages
            'edit' => "/posts/admin/edit.html",
            'admin' => "/posts/admin/admin.html",
            'table' => "/posts/parts/index-admin.html",
        ];
        if(!key_exists($type,$types)) throw new NotFound("The template was not found");
        return $types[$type];
    }
    
}