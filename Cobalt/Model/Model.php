<?php declare(strict_types=1);

namespace Cobalt\Model;

use Cobalt\Model\Traits\Accessible;
use Cobalt\Model\Traits\Schemable;
use Cobalt\Model\Traits\Viewable;
use Cobalt\Model\Types\Abstracts\OrderedListOfIds;
use Cobalt\Model\Types\ArrayOfObjectsType;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
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

    abstract static function __getVersion(): string;

    public function bsonSerialize(): array|stdClass|Document {
        return $this->getData();
    }

    public function bsonUnserialize(array $data): void {
        parent::__construct($this->defineSchema());
        $this->setData($data);
    }

    /************** ORDERED LIST OF ID STUFF *******************/
    public function __model(string $model_id, string $field_name):array {
        $_id = new ObjectId($model_id);
        /** @var Model $specified_model */
        if(method_exists($this, 'findOne')) $specified_model = $this->findOne(['_id' => $_id]);
        else if(method_exists($this->model, 'findOne')) $specified_model = $this->model->findOne(['_id' => $_id]);
        else throw new BadRequest("This looks like a generic model and there's no persistent model defined.");
        if(!$specified_model) throw new NotFound("The specified model was not found.");
        
        if(!isset($specified_model->{$field_name})) throw new BadRequest("Field $field_name does not exist.");

        $field = $specified_model->{$field_name};
        if($field instanceof OrderedListOfIds == false) throw new BadRequest("Field $field_name is not a queryable type.");

        $model = $field->getModel();
        $limit = $_GET['limit'] ?? 50;
        $skip  = $_GET['page'] ?? 0;

        $results = $model->find([], ['limit' => (int)$limit, 'skip' => (int)$skip]);
        $template = $field->fieldItemTemplate();
        $html = "";
        foreach($results as $value) {
            $html .= "<label class=\"object-gallery--item-selection\"><input type=\"checkbox\" value=\"$value->_id\">".view($template, ['item' => $value])."</label>";
        }

        // header("Content-Type: text/html");
        return ['html' => $html];
    }
}
