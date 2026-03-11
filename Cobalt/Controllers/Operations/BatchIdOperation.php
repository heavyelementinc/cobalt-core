<?php

namespace Cobalt\Controllers\Operations;

use Closure;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\MixedType;
use Exceptions\HTTP\Error;

class BatchIdOperation {
    protected ModelController $modelController;
    protected string $name = "";
    protected Types $type = Types::ASYNCBUTTON;
    protected string $icon = "";
    protected string $title = "";
    protected MixedType $field;
    // protected $view = null;
    protected $post = null;
    protected string $privelge = 'self';
    protected Locations $location = Locations::FILTER_MENU;

    function __construct(ModelController $modelController, string $name){
        $this->setName($name);
        $this->modelController = $modelController;
    }

    function __toString() {
        switch($this->type) {
            case Types::SELECT:
                return $this->renderSelectBox();
            case Types::ASYNCBUTTON:
            default:
                return $this->renderAsyncButton();
        }
    }
    
    function renderSelectBox():string {
        $title = $this->title;
        $action = route($this->modelController::class ."@__batchIdOperation", [$this->name]);
        $actionName = $this->getName();
        $valid = $this->field->options();
        $name = $this->field->getName();
        return <<<HTML
        <form-request method="POST" action="$action" autosave="form">
            <label for="batch-action--$name">$actionName</label><br>
            <input-array type="hidden" name="_ids" style="display:none"></input-array>
            <select id="batch-action--$name" name="$name" oninput="this.previousElementSibling.value = document.querySelector('table-container').value;" title="$title">
                $valid
            </select>
        </form-request>
        HTML;
    }

    function renderAsyncButton():string {
        $icon = isset($this->icon) ? "<i name='$this->icon'></i>" : $this->name;
        $title = $this->title;
        $action = route($this->modelController::class ."@__batchIdOperation", [$this->name]);
        $name = $this->field->getName();
        return <<<HTML
            <label for="batch-action--$this->name"></label>
            <async-button id="batch-action--$this->name" type="batch-action" method="POST" action="$action" title='$title'><i name='$icon'></i></async-button>
        HTML;
    }

    function getName(){
        return $this->name;
    }
    function setName($name):BatchIdOperation {
        $this->name = $name;
        return $this;
    }

    function getType():Types {
        return $this->type;
    }
    function setType(Types $type):BatchIdOperation {
        $this->type = $type;
        return $this;
    }

    function getIcon():string {
        return $this->icon;
    }
    function setIcon(string $icon):BatchIdOperation {
        $this->icon = $icon;
        return $this;
    }

    function getTitle():string {
        return $this->title;
    }
    function setTitle(string $title):BatchIdOperation {
        $this->title = $title;
        return $this;
    }

    // function getView():?Closure {
    //     return $this->view;
    // }
    // function setView(?Closure $view):Operation {
    //     $this->view = $view;
    //     return $this;
    // }
    // function runView() {
        
    // }

    function getPost():?Closure {
        return $this->post;
    }
    function setPost(?Closure $post):BatchIdOperation {
        $this->post = $post;
        return $this;
    }
    function runPost($post):mixed {
        if($this->post instanceof Closure === false) throw new Error("post is not an instance of Closure");
        return call_user_func($this->{'post'},$post, $this);
    }

    function getLocation():Locations {
        return $this->location;
    }
    function setLocation(Locations $location):BatchIdOperation {
        $this->location = $location;
        return $this;
    }
    
    function getField():MixedType {
        return $this->field;
    }
    function setField(MixedType $field):BatchIdOperation {
        $this->field = $field;
        return $this;
    }
}