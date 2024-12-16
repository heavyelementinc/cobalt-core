<?php

namespace Webmention;

use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use Rct567\DomQuery\DomQuery;
use stdClass;
use Webmention\Traits\Like;
use Webmention\Traits\Reply;
use Webmention\Traits\Repost;

class Mention implements Persistable {
    use Reply, Like, Repost;

    public ?ObjectId $_id = null;
    private string $source = "";
    private string $target = "";
    private array $url = [
        'scheme' => '',
        'host' => '',
        'path' => '',
        'withQuery' => '',
    ];

    protected DomQuery $dom;
    protected DomQuery $h_entry;
    protected DomQuery $link;

    public function getTarget(): string {
        return $this->target;
    }

    public function setTarget(string $target) {
        $this->target = $target;
        $this->url = parse_url($target);
        $this->url['withQuery'] = ($this->url['query']) ? $this->url['path']."?".$this->url['query'] : $this->url['path'];
    }

    public function setDomElements(DomQuery $dom, DomQuery $h_entry): void {
        $this->dom = $dom;
        $this->h_entry = $h_entry;
    }

    public function getSource(): string {
        return $this->source;
    }

    public function setSource(string $source) {
        $this->source = $source;
    }

    public function getHEntries() {
        return $this->h_entry;
    }

    public function bsonSerialize(): array|stdClass|Document {
        $doc = [
            'source' => $this->source,
            'target' => $this->target,
            'url'    => $this->url,
            'reply'  => $this->replyData(),
            'repost' => $this->repostData(),
            'like'   => $this->likeData(),
        ];
        
        return $doc;
    }

    public function bsonUnserialize(array $data): void {
        $this->_id = $data['_id'];
        $this->source = $data['source'];
        $this->target = $data['target'];
        $this->url = $data['url']->getArrayCopy();
        $this->replyRestore($data);
        $this->repostRestore($data);
        $this->likeRestore($data);
    }

    public function get_h_card(DomQuery $h_entry):array {
        $arr = [];
        $p_author = $h_entry->find(".p-author");
        if(count($p_author) !== 0) {
            // $this->is_like = true;
            $arr['p_author'] = trim($p_author->textContent);
            $imgs = $p_author->find("img");
            if(count($imgs)) {
                $src = $imgs->attr("src");
                $arr['p_avatar'] = trim($src);
            }
        }
        return $arr;
    }

    public function __isset($name) {
        switch($name) {
            case "source":
            case "target":
            case "url":
            case "reply":
            case "like":
            case "repost":
                return true; // $this->repost_data;
            default:
                return false;
        }
    }

    public function __get($prop) {
        switch($prop) {
            case "source":
                return $this->source;
            case "target":
                return $this->target;
            case "url":
                return $this->url;
            case "reply":
                return $this->reply_data;
            case "like":
                return $this->like_data;
            case "repost":
                return $this->repost_data;
            default:
                return null;
        }
    }

}