<?php
namespace Cobalt\Pages\Controllers;

use Cobalt\Maps\GenericMap;
use Controllers\Crudable;
use Cobalt\Pages\Classes\PageMap;
use Cobalt\Pages\Classes\PageManager;
use Cobalt\Pages\Classes\PostMap;
use Cobalt\SchemaPrototypes\Basic\BlockResult;
use Cobalt\Tasks\TaskManager;
use DateTime;
use DOMDocument;
use Drivers\Database;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use stdClass;
use Traversable;

abstract class AbstractPageController extends Crudable {
    var string $landing_content_classes = "";
    /** @var PageManager */
    public Database $manager;
    
    function get_page_data($queryPath):?PageMap {
        $_id = null;
        $query = null;
        if(strpos($queryPath, "/")) {
            $exploded = explode("/", $queryPath);
            $query = $exploded[0];
            try{
                $_id = new ObjectId($exploded[1]);
            } catch (Exception $e) {
                $_id = null;
            }
        }

        if($_id) {
            $result = $this->manager->findOne([
                '_id' => $_id,
                'deleted' => ['$exists' => false]
            ]);
    
            if($result) {
                // Check to ensure we're not duplicating our routes
                return $result;
            }
        }

        $result = $this->manager->findOne([
            'url_slug' => $query ?? $queryPath,
            'deleted' => ['$exists' => false]
        ]);

        // If we've found a result for the url_slug query, we're done, return it.
        if($result) return $result;
        // If we couldn't find a result and there's no ID set, then the page doesn't exist.
        if(!$_id) return null;
        
        // We have an ID left to try, so let's do that.
        $result = $this->manager->findOne([
            '_id' => $_id,
            'deleted' => ['$exists' => false]
        ]);

        // If there's no result now, the page doesn't exist.
        if(!$result) return null;
        
        // We're done!
        return $result;
    }

    function redirect_on_no_permalink(string $queryPath, PageMap $page) {
        $requires_permalink = $page->flags->and($page::FLAGS_INCLUDE_PERMALINK);
        if($requires_permalink != $page::FLAGS_INCLUDE_PERMALINK) return null;
        $regex = preg_match("/".preg_quote($page->_id)."/",$queryPath);
        if($regex === false || $regex === 0) redirect_and_exit($page->url_slug->get_path());
        return null;
    }

    function page($query = null) {        
        // Let's get our page data
        $page = $this->get_page_data($query);
        $does_not_exist = "That page does not exist.";
        // If there's no result, then we know it's not found.
        if($page === null) throw new NotFound($does_not_exist, true);

        // Check the page's visibility criteria
        $visibility = $page->visibility->getValue();
        
        if($visibility < $page::VISIBILITY_UNLISTED) {
            $pkey = (string)$page->preview_key;
            switch($visibility) {
                case(isset($_GET['pkey']) && $pkey && $_GET['pkey'] === $pkey):
                    // Do nothing
                    break;
                case $page::VISIBILITY_PRIVATE:
                    throw new NotFound($does_not_exist, true);
                    break;
                default:
                    // If the page is set to draft, check if the user is logged in
                    if(!session()) throw new NotFound($does_not_exist, true);
                    break;
            }
        }

        $this->redirect_on_no_permalink($query, $page);

        $now = time();
        
        $fifteen_minutes = 15 * 60;
        if($now - $_SESSION['last_post_updated'] >= $fifteen_minutes || $_SESSION['last_post'] !== (string)$page->_id) {
            $q = ['views' => 1];
            if(is_bot()) {
                $q['bot_hits'] = 1;
            }
            $this->manager->updateOne(['_id' => $page->_id], ['$inc' => $q]);
            $_SESSION['last_post'] = (string)$page->_id;
            $_SESSION['last_post_updated'] = $now;
        }

        /** @var DateTime */
        $live_date = (int)$page->live_date->getValue()->format("U");
        
        // If the current time is less than the live date, then the page doesn't exist!
        if($live_date > $now) throw new NotFound($does_not_exist, true);

        // One more check to see if this page requires an account, throw an Unauthorized so they are prompted to log in
        if($page->flags->and($page::FLAGS_REQUIRES_ACCOUNT) && !session()) throw new Unauthorized("You must be logged in to view this content");

        

        // Set up our (messy) variable table
        add_vars([
            'title' => $page['title'],
            'og_template' => '/Cobalt/Pages/templates/parts/opengraph.html',
            'description' => strip_tags(str_replace(["&#039;","&amp;#039;","\""],["'", "'", "'"], $page->summary ?? $page->body->firstParagraph())),
            'og' => [
                'title' => $page['title'],
                'image' => $page->splash_image->filename(),
                'image_x' => $page->splash_image->width(),
                'image_y' => $page->splash_image->height(),
                'author_tags' => $this->get_fediverse_tag($page),
            ],
            'splash' => $this->splash($page),
            'aside' => $this->aside($page),
            'biography' => $this->biography($page),
            'style' => $this->style($page),
            'page' => $page,
            'main_id' => 'landing-page--main',
            'body_class' => 'landing-page--body',
            'classes' => $this->landing_content_classes,
            'keywords' => $page->tags->join(", "),
            'related' => $this->getRelated($page),
            'footer' => $this->getFooter($page),
            'comments' => $this->getComments($page),
            'likes' => $this->getLikes($page),
        ]);

        // Get our view and check if it's in the view types
        $v = (string)$page->view;
        return view($page::VIEW_TYPE[$v]);
    }

    private function get_fediverse_tag(PageMap $page) {
        $author_details = $page->author->getValue();
        $value = "";
        if($author_details) {
            if(!$page->metadata_flags->and($page::METADATA_FEDIVERSE_CREDIT_PUBLICATION)) $value = (string)$author_details->fediverse_profile;
        }
        if(!$value) {
            $value = __APP_SETTINGS__['SocialMedia_fediverse'];
            if(!$value) return "";
        }
        $value = fediverse_href_to_user_tag($value);
        return "<meta name=\"fediverse:creator\" content=\"$value\" />";
    }

    function splash(PageMap $page) {
        $view = "/Cobalt/Pages/templates/views/splash-default.html";
        if($page instanceof PostMap) $view = "/Cobalt/Pages/templates/views/splash-post.html";
        // Let's get our splash view
        
        // Set our classes so it appears properlty
        $classes = "";
        switch($page->splash_type->getValue()) {
            case $page::SPLASH_POSITION_SPLIT:
                $classes .= " landing-splash--type-split";
                break;
            case $page::SPLASH_POSITION_FLOAT:
                $classes .= " landing-splash--type-float";
                break;
            case $page::SPLASH_POSITION_CENTER:
                $classes .= " landing-splash--type-centered";
            case $page::SPLASH_POSITION_FADE:
            default:
                $classes .= " landing-splash--type-fade";
                break;
        }

        // And render it
        return view($view, [
            'page' => $page,
            'class' => $classes,
            'follow_link' => $page->get_follow_link(),
            'views' => $page->get_byline_meta(true),
        ]);
    }

    function biography(PageMap $page) {
        // Let's filter out any irrelevant stuff
        if(!$page->include_bio->getValue()) return "";
        // if(!$page->author->get_name("full")) return "";
        $bio = (string)$page->bio;
        if(!$bio) $bio = (string)$page->author->getValue()->default_bio_blurb;
        if(!$bio) return "";
        
        // Let's determine how our avatar should look.
        $avatar_classes = "";
        if($page->bio_flags->and($page::BIO_AVATAR_RADIUS_CIRCULAR)) $avatar_classes = "border-radius--circular";
        else if($page->bio_flags->and($page::BIO_AVATAR_RADIUS_ROUNDED)) $avatar_classes = "border-radius--rounded";
        
        // Finally, let's render out our biography section
        return view("/Cobalt/Pages/templates/parts/biography.html", [
            'page' => $page,
            'headline' => ($page->bio_headline->getValue()) ? $page->bio_headline->getValue() : __APP_SETTINGS__['LandingPage_bio_default_headline'],
            'cta' => ($page->bio_cta->getValue()) ? $page->bio_cta->getValue() : $page->cta->getValue(),
            'avatar_classes' => $avatar_classes,
        ]);
    }

    function aside(PageMap $page) {
        // If this page doesn't want a sidebar, just return nothing.
        if($page->include_aside->getValue() !== true) return "";

        // Let's see how we want our aside to position itself
        $settings = $page->aside_positioning->getValue();
        $classes = "";
        if($settings & $page::ASIDE_SIDEBAR_NATURAL) $classes = "aside-config--natural";
        else if($settings & $page::ASIDE_SIDEBAR_REVERSE) $classes = "aside-config--reverse";
        else if($settings & $page::ASIDE_SIDEBAR_FOOTER) $classes = "aside-config--reverse";

        // And another check for stickiness
        if($settings & $page::ASIDE_STICKY) $classes .= " aside-config--sticky";

        // Let's get our Table of Contents
        $headlines = "";
        if($settings & $page::ASIDE_INCLUDE_TOC_INDEX) $this->generate_headline_index($page->body, $headlines);

        // Decide how it's positioned.
        $aside = "<aside class=\"landing-main--aside $classes\"><div class=\"aside--content\">";
        if($settings & $page::ASIDE_INDEX_BEFORE_CONTENT) $aside .= "$headlines"."$page->aside";
        else $aside .= "$page->aside"."$headlines";

        // Return our rendered sidebar
        return $aside . "</div></aside>";
    }

    /**
     * 
     * @param BlockResult $body 
     * @param string &$html 
     * @return void
     */
    function generate_headline_index(BlockResult $body, string &$html) {
        $html = "<h2 class=\"aside--table-of-contents\">" . __APP_SETTINGS__['LandingPage_table_of_contents_label'] . "</h2>" . $body->tableOfContents();
    }

    function style(PageMap $page) {
        // If the app disallows custom css injection, return an empty string
        if(!__APP_SETTINGS__['LandingPage_allow_custom_css_injection']) return "";
        // Check if this page allows us to show the app navigation, if not, add this CSS to the page
        $main_nav = ($page->show_main_nav->getValue() == false) ? "#app-header nav, #nav-menu-spawn {display:none}" : "";
        $style = "<style>$main_nav"."$page->style</style>";
        return $style;
    }

    function getRelated(PageMap $page) {
        if($page->flags->and($page::FLAGS_EXCLUDE_RELATED_PAGES)) return "";
        $related = $this->manager->getRelatedPages($page);
        if(!$related) return "";
        $related_title = ($page->related_title->getValue()) ? $page->related_title->getValue() : __APP_SETTINGS__['LandingPage_related_content_title'];
        $html = "<section class=\"landing-main--related-pages\"><h2>$related_title</h2><div class=\"landing-related--container\">";
        $count = 0;
        foreach($related as $p) {
            if($p instanceof PageMap == false) continue;
            $html .= $this->renderPreview($p, $page);
            $count += 1;
            if($count >= $p->max_related->getValue()) {
                break;
            }
        }
        return $html . "</div></section>";
    }

    function getFooter(PageMap $page) {
        if(!$page->metadata_flags->and($page::METADATA_INCLUDE_FOOTER)) return "";
        $footer = "<footer class=\"cobalt-posts--footer\">";
        $nav = "";
        foreach($page->tags as $tag) {
            $html_tag = htmlspecialchars((string)$tag);
            $url_tag = urlencode((string)$tag);
            $nav .= "<a href=\"".__APP_SETTINGS__['Posts']['public_index']."?tag=$url_tag\">$html_tag</a>";
        }
        if($nav) $nav = "<strong>Tags for this post</strong><nav>$nav</nav>";
        return "$footer$nav</footer>";
    }

    function getLikes(PageMap $page) {
        if($page->flags->and($page::FLAGS_HIDE_WEBMENTIONS)) return "";
        $webmention_details = $page->get_webmention_details();
        if($webmention_details['likeCount'] === 0) return "";
        return view("/Cobalt/Pages/templates/parts/likes.html", ['page' => $page, 'details' => $webmention_details]);
    }

    function getComments(PageMap $page) {
        $details = $page->get_webmention_details();
        if($details['replyCount'] === 0) return;
        return view("/Cobalt/Pages/templates/parts/comments.html", [
            'page' => $page,
            'details' => $details,
        ]);
    }

    function renderPreview(PageMap $p, ?PageMap $page = null) {
        $common_tags = "";
        if($page) $common_tags = implode(",",$page->tags->intersect($p->tags));
        return view("/Cobalt/Pages/templates/parts/related.html", [
            'page' => $p,
            'byline_meta' => $p->get_byline_meta(),
            'common_tags' => $common_tags,
        ]);
    }


    public function preview_key($id) {
        $_id = new ObjectId($id);

        /** @var PageMap */
        $page = $this->manager->findOne(['_id' => $_id]);
        if(!$page) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);

        confirm("Are you sure you want to provision a new preview key? The previous key will become unusable!",$_POST,"Continue");
        $string = uniqid();
        $string = (double)bin2hex($string);
        $p = strtolower(str_replace("=", "", base64_encode(sprintf("%d",($string * 1.27) << 1))));
        // $p = hex2bin(str_replace("-","",$str));
        $pkey = "px-";
        $skip = false;
        // $indexes = [7, 4, 5, 7, 8, 12];
        // $index = 0;
        for($i = strlen($p); $i >= 0; $i--) {
            if($i % 7 === 1) {
                if($skip === false) {
                    $i += 1;
                    $pkey .= '-';
                    $skip = true;
                    continue;
                } else {
                    $skip = false;
                    // $index += 1;
                }
            }
            $pkey .= $p[$i];
        }
        $result = $this->manager->updateOne(['_id' => $_id], [
            '$set' => ['preview_key' => $pkey]
        ]);
        $schema = $page->__get_schema();
        update("copy-span.preview-key", [
            'value' => $schema['preview_key']['display']($pkey)
        ]);
        return $result;
    }

    function update($post_data, $id): array {
        $this->api_validate_token($id);
        return $post_data;
    }

    /**
     * 
     * @param PageMap|null $doc 
     * @return void 
     * @throws Exception 
     */
    function after_update(GenericMap|BSONDocument|null $doc): void {
        if(__APP_SETTINGS__['Webmentions_enable_sending'] === false) return;
        if($doc->visibility->getValue() <= $doc::VISIBILITY_DRAFT) return;
        $taskManager = new TaskManager();
        $task = $taskManager->task($doc->_id);
        $task->set_class($this->manager);
        $task->set_method("webmention_send_task");
        $task->set_for($doc->_id);
        $task->set_timer(60 * 5); // Set a five min timer from now
        $taskManager->update_item($task);
        // async_cobalt_command("webmention dispatch ".escapeshellarg(get_class($this->manager))." $doc->_id");
        return;
    }

    public function edit($document): string {
        // add_vars(["autosave" => "autosave=\"form\""]);
        $token = random_string(12);
        $result = $this->manager->updateOne(['_id' => $document->getId()], ['$set' => ['token' => $token]]);
        
        $privileged_field_permission = ($this::className() === "Pages") ? 'Posts_enable_privileged_fields' : 'Pages_enable_privileged_fields';
    
        return view("/Cobalt/Pages/templates/admin/edit.html", [
            'admin_fields' => (has_permission($privileged_field_permission)) ? view("/Cobalt/Pages/templates/admin/admin-fields.html") : "",
            'token' => $token,
            'deleted' => (isset($document->__dataset['deleted'])) ? "<small>This post was deleted " . $document->deleted->getValue()->toDateTime()->format("c") . ".</small>" : "",
        ]);
    }

    public function getDeleteOptionLabel(GenericMap $doc) {
        return (isset($doc->__dataset['deleted'])) ? "Undelete" : "Delete";
    }

    public function api_validate_token($id) {
        $_id = new ObjectId($id);
        $document = $this->manager->findOne(['_id' => $_id], ['projection' => ['token' => 1]]);
        if(!$document) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);

        return $this->token_validation($document->token);
    }

    protected function token_validation($document_token, $header_token = null) {
        if($header_token === null) {
            $headers = apache_request_headers();
            $header_token = $headers['X-Editor-Token'] ?? $headers['x-editor-token'];
        }
        if($document_token !== $header_token) throw new BadRequest(ERROR_STALE_TOKEN, "Someone else is editing this article in another tab! Refresh this page to become the editor.");
        return ;
    }

    public function __destroy($id) {
        $read = $this->__read($id);
        if(!$read) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);

        $default_confirm_message = "Are you sure you want to delete this record?";
        $confirm_message = $this->destroy($read);
        $action = "delete";
        if(isset($read->__dataset['deleted'])) {
            $confirm_message = ['message' => "This will undelete this post and restore it to its current visibility status. Are you sure you want to continue?", 'post' => $confirm_message['post']];
            $action = "undelete";
        }
        confirm($confirm_message['message'] ?? $confirm_message[0] ?? $default_confirm_message, $confirm_message['post'] ?? $_POST, $confirm_message['okay'] ?? "Yes", $confirm_message['dangerous'] ?? true);
        
        switch($action) {
            case "undelete":
                $result = $this->manager->updateOne(['_id' => $read->_id], ['$unset' => ['deleted' => 1]]);
                break;
            default:
                $result = $this->manager->updateOne(['_id' => $read->_id], ['$set'   => ['deleted' => new UTCDateTime()]]);
                break;
        }
        header("X-Refresh: @now");
        return $result->getModifiedCount();
    }

    public function __multidestroy() {
        $upgraded = [];
        foreach($_POST[CRUDABLE_MULTIDESTROY_FIELD] as $id) {
            if(!$id) throw new BadRequest("Invalid ID found", "Invalid ID supplied");
            $upgraded[] = new ObjectId($id);
        }
        $query = ['_id' => ['$in' => $upgraded]];
        $results = $this->manager->count($query);
        confirm("This will delete $results document".plural($results).". Do you want to continue?", $_POST);

        $deleted = $this->manager->updateMany($query, ['$set' => ['deleted' => new UTCDateTime()]]);
        header("X-Refresh: @now");
        return $deleted->getModifiedCount();
    }
            
    static public function route_details_read():array {
        return ['permission' => static::route_permission("_read")];
    }

    static public function route_details_index():array {
        return ['permission' => static::route_permission("_index")];
    }

    static public function route_details_create():array {
        return ['permission' => static::route_permission("_create")];
    }

    static public function route_details_destroy():array {
        return ['permission' => static::route_permission("_destroy")];
    }

    static public function route_details_update():array {
        return ['permission' => static::route_permission("_update")];
    }

    static protected function route_permission(string $suffix): string {
        $name = static::className();
        switch($name) {
            case "LandingPages":
                $name = "Pages";
                break;
            default:
                $name = "Post";
                break;
        }
        return $name . $suffix;
    }

    public function getRowDetails(GenericMap $doc): array {
        $arr = [];
        if(isset($doc->__dataset['deleted'])) {
            $arr['checkbox_disabled'] = true;
            $arr['row_style'] = "text-decoration: line-through;color: rgba(0,0,0, .5)";
        }
        return $arr;
    }
}