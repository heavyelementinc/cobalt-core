<?php
use \Cobalt\CLI\Migration;
use Cobalt\Maps\GenericMap;
use Cobalt\Pages\PageMap;
use Cobalt\Pages\PostMap;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;
use Cobalt\SchemaPrototypes\MapResult;
use Controllers\ClientFSManager;
use MongoDB\BSON\ObjectId;

class upgradeposts extends Migration {
    use ClientFSManager;
    function config():void {
        $this->__run_one = true;
    }

    function runAll() {
        return null;
    }

    public function get_collection_name() {
        return "CobaltPosts";
    }

    public function beforeOneExecute(): ?\MongoDB\Driver\Cursor {
        return $this->find([], ['limit' => $this->count([])]);
    }

    public function runOne($document) {
        $id = $document['_id'];
        if($document instanceof PostMap) return null;
        $document['__v'] = "2.0";
        $dom = $this->convert_markdown_to_html($document->body);
        $landingImage = new UploadImageResult();
        $landingImage->setName("splash_image");
        if($document->default_image) {
            $img = new stdClass();
            $img->__dataset = ['media' => ['filename' => '/res/fs/' . $document->default_image], 'thumbnail' => ['filename' => '/res/fs/' . $document->default_image]];
            $landingImage->setValue($img);
        }
        else $landingImage->setValue(new GenericMap([],[]));
        $doc = [
            '_id' => $id,
            // '__pclass' => json_decode('{"$binary":"Q29iYWx0XFBhZ2VzXFBvc3RNYXA=", "$type": "80"}'),
            'md' => $document->body, // We'll back up the markdown for this post
            'type' => '',
            'body' => $this->convert_dom_to_blockeditor_output($dom),
            'visibility' => ($document->published === true) ? (string)PageMap::VISIBILITY_PUBLIC : (string)PageMap::VISIBILITY_DRAFT,
            'live_date' => $document->publicationDate,
            'summary' => strip_tags(from_markdown($document->excerpt)),
            'splash_image' => $landingImage,
            'splash_image_alignment' => match($document->splash_image_alignment) {
                "center center" => ["center"],
                "center left" => ["center","left"],
                "center right" => ["center","right"],
                "top left" => ["top","left"],
                "top center" => ["top","center"],
                "top right" => ["top","right"],
                "bottom left" => ["bottom","left"],
                "bottom center" => ["bottom","center"],
                "bottom right" => ["bottom","right"],
                default => ["center"]
            },

            /** Stuff that's going to stay the same */
            'author' => new ObjectId($document->author),
            'title' => $document->title,
            'url_slug' => $document->url_slug->get_path(),
            'rss_attachment' => $document->rss_attachment,
            'attachments' => $document->attachments ?? [],
            'tags' => $document->tags ?? []
        ];
        
        $result = $this->deleteOne(['_id' => $id]);
        $doc = (new PostMap())->ingest($doc);
        $result = $this->insertOne($doc);
        // return $result;
        return $result;
    }

    private function convert_markdown_to_html(string $markdown):DOMDocument {
        $parsed_md = from_markdown($markdown, false);
        if(!$parsed_md) $parsed_md = "<p>No content.</p>";
        $dom = new DOMDocument();
        $dom->loadHTML($parsed_md);
        return $dom;
    }

    private function convert_dom_to_blockeditor_output(DOMDocument $dom): array {
        $array = [];
        /** @var DOMElement */
        $el = $dom->getElementsByTagName("body")[0]->firstChild;
        do {
            $el;
            switch($el->tagName) {
                case "p":
                    $this->paragraph_convert($dom, $el, $array);
                    break;
                case "h1":
                case "h2":
                case "h3":
                case "h4":
                case "h5":
                case "h6":
                    $this->header_convert($dom, $el, $array);
                    break;
                case "img":
                    $this->img_convert($dom, $el, $array);
                    break;
                case "blockquote":
                    $this->quote_convert($dom, $el, $array);
                    break;
                case "ul":
                case "ol":
                    $this->nestedList_convert($dom, $el, $array);
                    break;
                case "iframe":
                    $this->embed_convert($dom, $el, $array);
                    break;
                default:
                    $this->rawtool_convert($dom, $el, $array);
                    break;
            }
            if($el->nextElementSibling) $el = $el->nextElementSibling;
            else break;
        } while (true);

        return [
            'time' => (int)(new DateTime())->format('v'),
            'blocks' => $array,
            'version' => '2.30.2',
        ];
    }

    private function paragraph_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        if($el->firstChild === $el->lastChild) {
            /** @var DOMElement */
            $fc = $el->firstChild;
            switch($fc->tagName) {
                case "img":
                    return $this->img_convert($dom, $fc, $array);
                    break;
            }
        }
        $html = $dom->saveHTML($el);
        $array[] = [
            'id' => random_string(8),
            'type' => 'paragraph',
            'data' => [
                'text' => strip_tags($html, ["em", "strong", "code", "a", "ul", "li"])
            ]
        ];
    }

    private function header_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        $array[] = [
            'id' => random_string(8),
            'type' => 'header',
            'data' => [
                'level' => (int)str_replace("h","",$el->tagName),
                'text' => $el->textContent
            ]
        ];
    }

    private function img_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        $src = $el->getAttribute("src");
        $url = parse_url($src);
        $replaced = str_replace("$url[scheme]://$url[host]"."/res/fs/", "", $src);
        $details = [
            'accent_color' => '#000',
            'accent_contrast' => '#fff',
            'height' => '',
            'width' => '',
            'mime' => '',
            'url' => $src,
        ];
        if($replaced !== $src) {
            $this->initFS();
            $result = $this->findFile(['filename' => $replaced]);
            if($result) {
                $details = [
                    'id' => $result->_id,
                    'accent_color' => $result->meta->accent_color,
                    'accent_contrast' => $result->meta->contrast_color,
                    'mime' => $result->meta->mimetype,
                    'height' => $result->meta->height,
                    'width' => $result->meta->width,
                    'url' => $src
                ];
            }
        }

        $array[] = [
            'id' => random_string(8),
            'type' => 'imagetool',
            'data' => [
                'caption' => $el->getAttribute("alt") ?? "",
                'file' => $src,
                'text' => $el->textContent
            ],
            'stretched' => false,
            'withBackground' => false,
            'withBorder' => false,
        ];
    }

    private function quote_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        $array[] = [
            'id' => random_string(8),
            'type' => 'quote',
            'data' => [
                'alignment' => 'left',
                'caption' => '',
                'text' => $el->textContent
            ],
        ];
    }

    private function embed_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        // $html = $dom->saveHTML($el);
        $src = $el->getAttribute('src');
        $url = parse_url($src);
        $array[] = [
            'id' => random_string(8),
            'type' => 'embed',
            'data' => [
                'service' => str_replace(['www.','.com','.be','.io','.tv','.fm','.org'],'',$url['host']),
                'source' => $src,
                'embed' => $src,
                'width' => 580,
                'height' => 320,
                'caption' => ''
            ],
        ];
    }

    private function rawtool_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        $html = $dom->saveHTML($el);

        $array[] = [
            'id' => random_string(8),
            'type' => 'rawtool',
            'data' => [
                'html' => $html,
            ],
        ];
    }

    private function nestedList_convert(DOMDocument $dom, DOMElement $el, array &$array) {
        $arr = [
            'id' => random_string(8),
            'type' => 'nestedlist',
            'data' => []
        ];
        $this::nestedListDetails($el, $arr['data']);
        $arr['data'] = $arr['data'][0];
        $array[] = $arr;
    }

    static private function nestedListDetails(?DOMElement $list, array &$arr) {
        // // If there's no UL or OL tag, just return an empty array

        // Let's get our tag name so we can decide which style this block is
        $tag = $list->tagName;
        $currentListContainer = [
            'style' => ($tag === "ol") ? "ordered" : "unordered",
            'items' => [],
        ];

        $listItems = $list->getElementsByTagName("li");
        /** @var DOMElement */
        foreach($listItems as $li) {
            // Check for an <ol> tag
            $childList = $li->getElementsByTagName("ol")->item(0);
            // If an <ol> doesn't exist, check for a <ul> tag
            if(is_null($childList)) $childList = $li->getElementsByTagName("ul")->item(0);
            
            // Set up our element data
            $a = [
                'content' => trim($li->firstChild->textContent),
                'items' => [],
            ];
            
            // And then do some fancy recursion
            if(!is_null($childList)) self::nestedListDetails($childList ?? null, $a['items']);
            $currentListContainer['items'][] = $a;
        }

        $arr[] = $currentListContainer;
    }

}