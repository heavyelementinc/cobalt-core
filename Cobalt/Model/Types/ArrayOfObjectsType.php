<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Error;

class ArrayOfObjectsType extends ArrayType {
    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        if($this->hasDirective("field")) return $this->getDirective("field", $class, $misc, $tag);
        // if($tag === null && $this->hasDirective("input_tag")) $tag = $this->getDirective("input_tag") ?? "input-object-array";
        // if($tag === null) $tag = "input-object-array";
        return $this->inputObjectArray($class, $misc);
    }

    protected function getTemplate():string {
        $template = $this->directiveOrNull("template") ?? $this->directiveOrNull("view");
        if(!$template) {
            if(!$this->hasDirective("each")) {
                throw new Error("Field $this->name does not have a `template`, `view`, or `each` directive specified. Cannot render field.");
            }
            $template = "<ul class='list-panel'>";
            $target = [];
            $each = $this->getDirective('each');
            /** @var string $key
             * @var MixedType $type
             */
            foreach($each as $key => $type) {
                // $this->hydrate($each, $key, null, $this->model, $key, $type['directives'], $type);
                $type->setName($key);
                $template .= "<li>".$type->getLabel() . $type->field()."</li>";
            }
            $template .= "</ul>";
        }
        return $template;
    }

    protected function inputObjectArray($classes = "", $misc = []) {
        if($this->hasDirective("immutable") && $this->getDirective("immutable")) $misc['readonly'] = "readonly";
        
        $value = json_encode($this->getValue());

        [$misc, $attrs] = $this->defaultFieldData($misc);
        $template = $this->getTemplate();
        return <<<HTML
        <input-object-array name="$this->name" $attrs>
        <script type="application/json">$value</script>
        <template>$template</template>
        </input-object-array>
        HTML;
    }

    /** TODO: Filter each items! */
}