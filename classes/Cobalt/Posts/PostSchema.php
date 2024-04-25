<?php
/**
 * 
 */
namespace Cobalt\Posts;

use Auth\UserCRUD;
use Controllers\ClientFSManager;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\UnknownError;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

class PostSchema extends \Validation\Normalize {
    use ClientFSManager;
    private $soft_char_cap = 200;
    public function __get_schema(): array {
        return [
            'author' => [
                // 'get' => [],
                'valid' => function ($val) {
                    return $this->valid_users_by_permission('Posts_manage_posts','permission');
                },
                'display' => function ($val) {
                    $man = new UserCRUD();
                    $author = $man->findOneAsSchema(['_id' => new ObjectId($val)]);
                    return $author->nametag;
                },
                'attrs' => function ($val) {
                    $man = new UserCRUD();
                    $author = $man->findOneAsSchema(['_id' => new ObjectId($val)]);
                    return $author->display_name;
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
                'set' => fn ($val) => $this->make_date($val . " " . $this->__to_validate['publicationTime']),
                'display' => fn ($val) => $this->get_date($val, 'relative'),
            ],
            'publicationTime' => [
                'get' => fn () => $this->get_date($this->__dataset['publicationDate'], "24-hour"),
                'set' => false,
            ],
            'body' => [
                'display' => function ($val) {
                    return from_markdown($val, !$this->allow_html_content);
                }
            ],
            'excerpt' => [
                'get' => function ($val) {
                    if(!$val) return $this->markdown_word_limit($this->__dataset['body'], $this->soft_char_cap);
                    return $val;
                },
                'set' => function ($val) {
                    if($val) return $this->markdown_word_limit($val, $this->soft_char_cap);
                    $truncated = $this->markdown_word_limit($this->__dataset['body'], $this->soft_char_cap);
                    return $truncated;
                },
                // 'soft_char_cap' => 200,
                'display' => function ($val) {
                    // return markdown_to_plaintext($this->excerpt);
                    return from_markdown($this->{"excerpt"});
                },
                'attrs' => function ($val) {
                    return markdown_to_plaintext($this->{"excerpt"} ?? $this->__dataset['body.strip']);
                }
            ],
            'postType' => [
                'get' => fn ($val) => $val ?? "Posts",
            ],
            'default_image' => [
                'get' => function ($val) {
                    if($val) return "/res/fs/$val";
                    $this->initFS();
                    $results = $this->fs->findOne(['for' => $this->_id],['sort' => ['order' => 1]]);
                    if(!$results) return "";
                    return "/res/fs/$results->filename";
                },
                'display' => function ($val) {
                    $val = $this->default_image;
                    if(!$val) return "";
                    return "style=\"background-image:url('".$val."'); background-position: ".$this->{'alignment.position'}."\" bg-splash";
                },
            ],
            'rss_attachment' => [
                'attrs' => function ($val) {
                    $this->initFS();
                    $results = $this->fs->findOne(['for' => $this->_id],['sort' => ['order' => 1]]);
                    if(!$results) return "";
                    return "url=\"http://".app("domain_name")."/res/fs/$results->filename\" length=\"".$results->length."\" type=\"".$results->meta->mimetype."\"";
                }
            ],
            'no_image' => [
                'get' => fn () => ($this->__dataset['default_image']) ? "" : "cobalt-post--no-image",
                'set' => false,
            ],
            'alignment.position' => [
                'get' => fn ($val) => $val ?? "center center",
                'set' => fn ($val) => $this->setAlignment($val),
                'valid' => [
                    "center center" => "Background Centered",
                    "center left"   => "Center Left",
                    "center right"  => "Center Right",
                    "top left"      => "Top Left",
                    "top center"    => "Top Center",
                    "top right"     => "Top Right",
                    "bottom left"   => "Bottom Left",
                    "bottom center" => "Bottom Center",
                    "bottom right"  => "Bottom Right",
                ]
            ],
            'alignment.parallax' => [
                'get' => fn ($val) => $val ?? false,
                'set' => fn ($val) => $this->boolean_helper($val)
            ],
            'attachments' => [
                'set' => function ($val) {
                    if(empty($val)) throw new ValidationIssue("No files were attached");
                    if(!$this->_id) throw new BadRequest("There's no _id specified for storage");
                    $mutant = ["attachments" => $val['attachments']];
                    
                    foreach($val['attachments']['name'] as $index => $file) {
                        $mutant['attachments']['name'][$index] = uniqid() . "." . pathinfo($file,PATHINFO_EXTENSION);//array_merge($file, ['name' => uniqid() . "." . pathinfo($file['name'],PATHINFO_EXTENSION)]);
                    }

                    return $this->clientUploadImagesAndThumbnails("attachments", 200, null, ['for' => $this->_id], $mutant);
                },
                'display' => function ($val) {
                    return view("/posts/parts/edit-gallery.html",['gallery' => $this->directoryListing("/res/fs/", "gallery", ['filter' => ['for' => $this->_id]])]);
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
            ],
            'prominent' => [
                'set' => fn ($val) => $this->boolean_helper($val),
                'display' => function ($val) {
                    if(app("Posts_default_index_display") === "prominent") return " cobalt-post--prominent";
                    return ($val) ? " cobalt-post--prominent" : "";
                }
            ],
            'allow_html_content' => [
                'set' => function ($val) {
                    $session = session();
                    if(!$session) throw new ValidationIssue("You're not logged in. I don't know how you managed to get this far.");
                    if(!has_permission('Posts_allow_unsafe_post_content', null, $session, false)) {
                        if($val === true) throw new ValidationIssue("You do not have permission to pass through HTML content");
                    }
                    return $val;
                }
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
