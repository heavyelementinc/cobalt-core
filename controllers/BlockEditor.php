<?php

use Controllers\ClientFSManager;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Error;
use GuzzleHttp\Client;

use function PHPSTORM_META\map;

class BlockEditor {
    use ClientFSManager;

    function fileUpload() {
        if(empty($_FILES)) {
            header("HTTP/1.0 400 Files must not be empty");
            echo json_encode(['success' => 0]);
            exit;
        }

        $this->fs_filename_path = "block-editor-content";

        
        $_FILES['image']['name'] = sha1(time().$_FILES['image']['name']) . "." . get_extension_from_file($_FILES['image']['tmp_name'], $_FILES['image']['name'], false);

        $result = $this->clientUploadFile("image", null, [], $_FILES);

        return [
            'success' => 1,
            'file' => [
                'url' => server_name()."/res/fs/$result[filename]",
                'mime' => $result['meta']['mimetype'],
                'width' => $result['meta']['width'],
                'height' => $result['meta']['height'],
                'accent_color' => $result['meta']['accent_color'],
                'accent_contrast' => $result['meta']['contrast_color'],
                'id' => (string)$result['_id'],
            ]
        ];
    }

    function fileByURL() {
        $url = $_POST['url'];
        $tmpname = "/tmp/".uniqid();
        $result = file_put_contents($tmpname, file_get_contents($url));
        if($result == false) {
            throw new Error("Failed to write file");
        }
        $_FILES = [
            'image' => [
                'name' => pathinfo($url, PATHINFO_FILENAME),
                'type' => mime_content_type($tmpname),
                'tmp_name' => $tmpname,
                'error' => UPLOAD_ERR_OK,
                'size' => $result,
            ]
        ];
        return $this->fileUpload();
    }

    function linkFetcher() {
        $client = new Client();
        $response = $client->request("GET", $_GET['url']);
        if($response->getStatusCode() !== 200) {
            throw new BadRequest("Requested URL failed response with " .$response->getStatusCode(). " status code!");
        }
        if($response->getHeader("content-type")[0]) {
            // If not html, throw an error
        }
        $body = $response->getBody();
        if(!$body) {
            throw new BadRequest("Response body was empty!");
        }
        $dom = new DOMDocument();
        $parseResult = $dom->loadHTML($body);
        if(!$parseResult) {
            throw new BadRequest("Response was malformed");
        }

        /** @var DOMNodeList */
        $domList = $dom->getElementsByTagName("meta");
        $pageTitle = trim(iterator_to_array($dom->getElementsByTagName('title'))[0]->textContent);
        $description = "";
        $listOfParagraphs = iterator_to_array($dom->getElementsByTagName("p"));
        if(!empty($listOfParagraphs)) $description = trim($listOfParagraphs[0]->textContent);
        $metaTags = [];
        
        /** @var DOMElement */
        foreach($domList as $element) {
            $property = $element->getAttribute("property");
            // if($property && substr($property, 0, 2) == "og") {
            $metaTags[$property] = $element->getAttribute("content");
            // }
        }

        return [
            'success' => 1,
            'link' => $metaTags['og:url'] ?? $_GET['url'],
            'meta' => [
                'title' => $metaTags['og:title'] ?? $pageTitle,
                'site_name' => $metaName['og:site_name'] ?? parse_url($metaTags['og:url'] ?? $metaTags['og:title'], PHP_URL_HOST),
                'description' => $metaTags['og:description'] ?? $metaTags['description'] ?? $description,
                'image' => [
                    'url' => $metaTags['og:image'],
                    'height' => $metaTags['og:image:height'],
                    'width' => $metaTags['og:image:width'],
                ]
            ]
        ];
    }

}