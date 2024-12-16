<?php
namespace Webmention;

interface WebmentionDocument {
    function webmention_get_urls_to_notify():array;

    // function webmention_get_completed_urls():array;

    // function webmention_set_completed_urls(array $completed):void;

    function webmention_get_canonincal_url():string;

    function webmention_lock():void;

    function webmention_unlock():void;

    function webmention_is_locked():bool;
}