<?php

namespace Webmention\Traits;

use Rct567\DomQuery\DomQuery;

trait Like {
    private array $like_data = [
        'is' => false,
        'p_author' => '',
        'p_avatar' => '',
    ];

    public function discoverLike(DomQuery $h_entry) {
        // $finder = new DOMXPath($h_entry->ownerDocument);

        // Let's check to see if this is a like:
        $u_like_of = $h_entry->find("a.u-like-of");
        if(count($u_like_of) === 0) {
            $u_like_of = $h_entry->find("*.u-like-of");
            if(count($u_like_of) >= 1) {
                $this->like_data['is'] = true;
            } else {
                if(!$this->like_data['is']) $this->like_data['is'] = false;
                return;
            }
        }
        
        $h_card = $this->get_h_card($h_entry);
        if(!empty($h_card)) {
            $this->like_data['is'] = true;
            $this->like_data['p_author'] = $h_card['p_author'];
            $this->like_data['p_avatar'] = $h_card['p_avatar'];
        }

        return $this;
    }

    public function likeRestore($details):void {
        $this->like_data = $details['like']->getArrayCopy();
    }

    public function likeData():array {
        return $this->like_data;
    }

    public function isLike():bool {
        return $this->like_data['is'];
    }

    abstract function get_h_card(DomQuery $h_entry):array;
}