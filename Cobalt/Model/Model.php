<?php declare(strict_types=1);

namespace Cobalt\Model;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Traits\Accessible;
use Cobalt\Model\Traits\Schemable;
use Cobalt\Model\Traits\Viewable;
use Cobalt\Model\Types\Abstracts\ForeignId;
use Cobalt\Model\Types\Abstracts\OrderedListOfForeignIds;
use Cobalt\Model\Types\ArrayOfObjectsType;
use Cobalt\Model\Types\StringType;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use stdClass;

abstract class Model extends GenericModel implements Persistable {
    use Accessible, Schemable;

    function __construct() {
        parent::__construct($this->defineSchema(), null);
    }

    /**
     * Specify the schema used by this model
     * @return array{}
     */
    abstract function defineSchema(array $schema = []): array;
    abstract function defineController():ModelController;

    abstract static function __getVersion(): string;

    public function bsonSerialize(): array|stdClass|Document {
        return $this->getData();
    }

    public function bsonUnserialize(array $data): void {
        parent::__construct($this->defineSchema());
        $this->setData($data);
    }
    public function getTimestamp() {
        return $this->_id->getTimestamp() * 1000;
    }

    /************** ORDERED LIST OF ID STUFF *******************/
    public function __model(string $model_id, string $field_name):array {
        $_id = new ObjectId($model_id);
        /** @var Model $specified_model */
        if(method_exists($this, 'findOne')) $specified_model = $this->findOne(['_id' => $_id]);
        else if(method_exists($this->model, 'findOne')) $specified_model = $this->model->findOne(['_id' => $_id]);
        else throw new BadRequest("This looks like a generic model and there's no persistent model defined.");
        if(!$specified_model) throw new NotFound("The specified model was not found.");
        
        if(!key_exists($field_name, $specified_model->readSchema())) throw new BadRequest("Field $field_name does not exist.");

        $field = $specified_model->{$field_name};
        if($field instanceof OrderedListOfForeignIds == false && $field instanceof ForeignId == false) {
            throw new BadRequest("Field $field_name is not a queryable type.");
        }
        
        $exclude = $_GET['exclude'] === "false" ? false : true;
        $results = $field->queryForObjects(
            (int)($_GET[QUERY_PARAM_LIMIT] ?? 50),
            (int)($_GET[QUERY_PARAM_PAGE_NUM] ?? 0),
            (string)($_GET[QUERY_PARAM_SORT_NAME] ?? '_id'),
            (int)($_GET[QUERY_PARAM_SORT_DIR] ?? -1),
            (string)($_GET[QUERY_PARAM_SEARCH] ?? ""),
            $exclude
        );
        // $controller = $this->defineController();
        
        $template = $field->fieldItemTemplate();
        $field_value = $field->getValue() ?? [];
        if($field_value instanceof BSONDocument) $field_value = $field_value->getArrayCopy();
        $html = "";
        $type = "checkbox";
        if($_GET['max'] == "1") {
            $type = "radio";
        }
        foreach($results['cursor'] as $value) {
            $disabled = "";
            if(array_search($value->_id, $field_value) !== false) {
                $disabled = " disabled=\"disabled\"";
            }
            
            $html .= "<label class=\"object-gallery--item-selection\"$disabled><input type=\"$type\" value=\"$value->_id\">".view($template, ['item' => $value, 'ordered_list' => $this])."</label>";
        }

        // header("Content-Type: text/html");
        return [
            'html' => $html,
            'count' => $results['count']
        ];
    }

    public function __defaultSchema():array {
        return [
            '__version' => new StringType,
        ];
    }
}
