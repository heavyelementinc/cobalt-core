<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Controllers\ClientFSManager;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Validation\Exceptions\ValidationIssue;

class ImageArrayType extends MixedType {
    use ClientFSManager;

    public array $raw = [];

    public function setValue($images):void {
        $ids = [];
        foreach($images as $value) {
            // This is the primary data structure
            if($value instanceof ObjectId) {
                $ids[] = $value;
                continue;
            }
            // Supporting older formats
            $id = $value['media']['ref'] ?? $value['media']['id'];
            if($id instanceof ObjectId) {
                $ids[] = $id;
            }
        }
        $this->raw = $ids;
        
        // Now that we have all our IDs, let's find the details
        $results = $this->findFiles(['_id' => ['$in' => $ids]],['limit' => count($ids)]);
        $unordered = [];
        // if($result) $details = iterator_to_array($result);
        foreach($results as $result) {
            $unordered[(string)$result['_id']] = $result;
        }

        $ordered = [];
        foreach($ids as $id) {
            $ordered[] = $unordered[(string)$id] ?? null;
        }

        parent::setValue($ordered);
    }

    function filter($oids) {
        if(!empty($_FILES)) {
            // $this->readFile();
        }
        $value = [];
        foreach($oids as $val) {
            if(!$val) throw new ValidationIssue("Contains invalid file IDs");
            $value[] = new ObjectId($val);
        }
        return $value;
    }



    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        // $gallery = $this->input($class, $misc); // "<input class='input-type--file' type='file' name='$this->name' multiple='multiple'>";
        [$data, $attrs] = $this->defaultFieldData($misc);
        $accept = $this->directiveOrNull("accept") ?? "";
        if($accept) $accept = "accept=\"$accept\"";
        $tag = $tag ?? "file-gallery";
        $gallery = "<$tag $attrs $accept>";
        foreach($this->getValue() as $img) {
            $gallery .= view("Cobalt/Model/templates/types/gallery-item.php", ['img' => $img, 'this' => $this]);
        }
        $gallery .= "</$tag>";
        return $gallery;
    }
    
    public function eachSchema() {
        
    }
}