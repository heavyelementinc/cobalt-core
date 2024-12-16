<?php

namespace Webmention\Traits;

use Rct567\DomQuery\DomQuery;

trait Repost {
    private array $repost_data = [
        'is' => false,
        'p_author'  => '',
        'p_avatar'  => '',
    ];

    public function discoverRepost(DomQuery $h_entry) {
        // Let's check to see if this is a like:
        $u_repost_of = $h_entry->find("a.u-repost-of");
        if(count($u_repost_of) === 0) {
            $u_repost_of = $h_entry->find("*.u-repost-of");
            if(count($u_repost_of) >= 1) {
                $this->repost_data['is'] = true;
            } else {
                if(!$this->repost_data['is']) $this->repost_data['is'] = false;
                return;
            }
        }
        
        $h_card = $this->get_h_card($h_entry);
        if(!empty($h_card)) {
            $this->repost_data['is'] = true;
            $this->repost_data['p_author'] = $h_card['p_author'];
            $this->repost_data['p_avatar'] = $h_card['p_avatar'];
        }

        return $this;
    }

    public function repostRestore($details):void {
        $this->repost_data = $details['repost']->getArrayCopy();
    }

    public function repostData():array {
        return $this->repost_data;
    }

    public function isRepost():bool {
        return $this->repost_data['is'];
    }

    abstract function get_h_card(DomQuery $h_entry):array;
}