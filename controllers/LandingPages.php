<?php

use Cobalt\Maps\GenericMap;
use Cobalt\Pages\PageManager;
use Cobalt\Pages\PageMap;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

/** @package  */
class LandingPages extends Controllers\Landing\Page {

    public function get_manager(): Database {
        
        return new PageManager();
    }
    
    public function get_schema($data): GenericMap {
        return new PageMap();
    }

    public function edit($document): string {
        // add_vars(["autosave" => "autosave=\"form\""]);
        return view("/pages/landing/edit.html");
    }

    public function destroy(GenericMap|BSONDocument $document): array {
        return ['message' => "Are you sure you want to delete this page?"];
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

    // public function get_page_data($query): ?PageMap {
    //     $map = new PageMap([
    //         'h1' => get_custom("website_h1"),
    //         'title' => get_custom("website_h1"),
    //         'subtitle' => get_custom('website_h2'),
    //         'summary' => get_custom('website_bio'),
    //         'body' => get_custom('website_body'),
    //         'bio' => get_custom('website_bio'),
    //         'style' => get_custom('website_style'),
    //         'cta' => get_custom('website_cta'),
    //         'cta_href' => get_custom('website_cta_href'),
    //     ]);
    //     return $map;
    // }

}