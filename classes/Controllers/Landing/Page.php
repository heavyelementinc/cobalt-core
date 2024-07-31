<?php
namespace Controllers\Landing;
use Controllers\Landing\Map;

abstract class Page {
    abstract function get_page_data($query):Map;

    function page($query) {
        $data = $this->get_page_data($query);
        // $required = ['h1', 'body', 'bio', 'cta', 'cta_href'];
        add_vars([
            'title' => $data['title'] ?? $data['h1'],
            'opengraph' => [
                'title' => $data['title'] ?? $data['h1']
            ],
            'doc' => $data
        ]);

        return view('/pages/landing/default.html');
    }
}