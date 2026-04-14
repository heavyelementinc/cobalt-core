<?php

namespace Cobalt\Auth\Permissions;

class Permission {
    private string $identifier = "";
    private string $group = "";
    private string $label = "";
    private string $help = "";
    private string $name = "";
    private bool $dangerous = true;
    private bool $default = false;
    private bool $display = false;
    private int $ring = 0;
    
    function __construct($identifier)    {
        $this->setIdentifier($identifier);
    }

    function getIdentifier(): mixed {
        return $this->identifier;
    }
    function setIdentifier(string $value) {
        $this->identifier = $value;
        return $this;
    }

    

    function getName() {
        return $this->name;
    }
    function setName(string $value) {
        $this->name = $value;
        return $this;
    }

    function getGroup() {
        return $this->group;
    }
    function setGroup(string $value) {
        $this->group = $value;
        return $this;
    }
    
    function getLabel() {
        return $this->label;
    }
    function setLabel(string $value) {
        $this->label = $value;
        return $this;
    }

    function getHelp() {
        return $this->help;
    }
    function setHelp(string $value) {
        $this->help = $value;
        return $this;
    }
    
    function getDangerous() {
        return $this->dangerous;
    }
    function setDangerous(bool $value) {
        $this->dangerous = $value;
        return $this;
    }
    
    function getDefault() {
        return $this->default;
    }
    function setDefault(bool $value) {
        $this->default = $value;
        return $this;
    }
    
    function getDisplay() {
        return $this->display;
    }
    function setDisplay(bool $value) {
        $this->display = $value;
        return $this;
    }
    
    function getRing() {
        return $this->ring;
    }
    function setRing(int $value) {
        $this->ring = $value;
        return $this;
    }
}