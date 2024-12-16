<?php

namespace Webmention\Traits;

use League\HTMLToMarkdown\HtmlConverter;
use Rct567\DomQuery\DomQuery;

trait Reply {
    private array $reply_data = [
        'is' => false,
        'p_content' => '',
        'p_author' => '',
        'p_avatar' => '',
    ];

    public function discoverReply(DomQuery $h_entry) {
        // $finder = new DOMXPath($h_entry->ownerDocument);

        // Let's check to see if this is a reply:
        $u_in_reply_to = $h_entry->find("a.u-in-reply-to");
        if(count($u_in_reply_to) === 0 && !$this->reply_data['is']) {
            $this->reply_data['is'] = false;
            return;
        }

        $h_card = $this->get_h_card($h_entry);
        if(!empty($h_card)) {
            $this->reply_data['is'] = true;
            $this->reply_data['p_author'] = $h_card['p_author'];
            $this->reply_data['p_avatar'] = $h_card['p_avatar'];
        }

        $p_content = $h_entry->find(".p-content");
        if(!empty($p_content)) {
            $this->reply_data['is'] = true;
            $converter = new HtmlConverter(['strip_tags' => true, 'use_autolinks' => false]);
            $md = $converter->convert($p_content->getInnerHtml());
            $this->reply_data['p_content'] = $md;
        }

        return $this;
    }

    public function replyRestore($details):void {
        $this->reply_data = $details['reply']->getArrayCopy();
    }

    public function replyData():array {
        return $this->reply_data;
    }

    public function isReply():bool {
        return $this->reply_data['is'];
    }

    abstract function get_h_card(DomQuery $h_entry):array;
}