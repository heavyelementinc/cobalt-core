<?php
/**
 * 
 */
namespace Cobalt\Posts;

use Controllers\ClientFSManager;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\UnknownError;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

class PostSchema extends \Validation\Normalize {
    use ClientFSManager;
    public function __get_schema(): array {
        return [
            'author' => [
                // 'get' => [],
                'valid' => function ($val) {
                    return $this->valid_users($GLOBALS['POST_PERMISSIONS'],'permission');
                }
            ],
            'title' => [],
            'url_slug' => [
                'set' => function ($val) {
                    if(!$val) return $this->url_fragment_sanitize($this->__dataset['title']);
                    return $this->url_fragment_sanitize($val);
                }
            ],
            'published' => [
                'set' => fn ($val) => $this->boolean_helper($val),
                'display' => fn ($val) => ($val) ? "Published" : "Draft"
            ],
            'publicationDate' => [
                'get' => fn ($val) => $this->get_date($val),
                'set' => fn ($val) => $this->make_date($val),
                'display' => fn ($val) => $this->get_date($val, 'verbose'),
            ],
            'body' => [],
            'excerpt' => [
                'set' => function ($val) {
                    $soft_char_cap = 200;
                    if($val) return $val;
                    return $this->markdown_word_limit($this->__dataset['body'], $soft_char_cap);
                }
                // 'soft_char_cap' => 200
            ],
            'postType' => [
                'get' => fn ($val) => $val ?? "Posts",
            ],
            'default_image' => [
                'get' => function ($val) {
                    if($val) return "$this->public_link/attachment/$val";
                    $this->initFS();
                    $results = $this->fs->findOne(['for' => $this->_id],['sort' => ['order' => 1]]);
                    if(!$results) return "";
                    return "$this->public_link/attachment/$results->filename";
                },
                'display' => function ($val) {
                    if(!$val) return "";
                    return "style=\"background-image:url(".$this->default_image."); background-position: ".$this->{'alignment.position'}."\" bg-splash";
                }
            ],
            'alignment.position' => [
                'get' => fn ($val) => $val ?? "center center",
                'set' => fn ($val) => $this->setAlignment($val),
            ],
            'alignment.parallax' => [
                'get' => fn ($val) => $val ?? false,
                'set' => fn ($val) => $this->boolean_helper($val)
            ],
            'attachments' => [
                'set' => function ($val) {
                    if(empty($val)) throw new ValidationIssue("No files were attached");
                    if(!$this->_id) throw new BadRequest("There's no _id specified for storage");
                    $mutant = ["attachments" => []];
                    
                    foreach($val['attachments'] as $index => $file) {
                        $mutant['attachments'][$index] = array_merge($file, ['name' => uniqid() . "." . pathinfo($file['name'],PATHINFO_EXTENSION)]);
                    }

                    return $this->clientUploadImagesAndThumbnails("attachments", 200, null, ['for' => $this->_id], $mutant);
                },
                'display' => function ($val) {
                    return with("/posts/parts/edit-gallery.html",['gallery' => $this->directoryListing("$this->public_link/attachment/", "gallery", ['filter' => ['for' => $this->_id]])]);
                    // '
                    // <cobalt-listing id="gallery" 
                    //     custom-label-1="Make Post Default" custom-action-1="/api/v1/posts/attachment/{id}/default" custom-method-1="PUT"
                    //     delete-action="/api/v1/posts/attachment/{id}"
                    //     sort-action="/api/v1/posts/attachment/{id}/sort" sort-method="PUT"
                    //     >'.$this->directoryListing("$this->public_link/attachment/", "gallery", ['filter' => ['for' => $this->_id]]).'
                    //     </cobalt-listing>';
                }
            ],
            'public_link' => [
                'get' => function () {
                    if($this->url_slug) return $this->getPath('post',[$this->url_slug]);
                    return null;
                },
                'set' => false
            ]
        ];
    }

    public function getTemplate($type = "post"):string {
        return (new PostManager())->getTemplate($type);
    }

    function getPath(string $methodName, array $args = [], string $method = "get", $context = "web") {
        $path = get_path_from_route($this->postType, $methodName, $args, $method, $context);
        if(!$path) {
            throw new UnknownError("Could not find '$methodName' route");
        }
        return $path;
    }

    function setAlignment($val) {
        $allowed = [
            "top left",
            "top center",
            "top right",
            "center left",
            "center center",
            "center right",
            "bottom left",
            "bottom center",
            "bottom right",
        ];
        $val = strtolower($val);
        $validated = false;
        if(in_array($val, $allowed)) $validated = true;

        if(!$validated && preg_match("/[0-9]+[(px)%(r?em)]+/",$val)) $validated = true;

        if(!$validated) throw new ValidationIssue("Bad value specified for header image alignment");
        return $val;
    }
}