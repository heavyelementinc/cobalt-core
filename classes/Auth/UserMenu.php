<?php
namespace Auth;
class UserMenu{
    public array $instructions = [];
    function __construct(array $instructions){
        $this->instructions = $instructions;
    }
    
    function create_menu(){
        $menu = "<button id='user-menu-button'><ion-icon name=\"caret-down\"></ion-icon></button><menu id='user-menu-container' class='hidden'><ul>";
        foreach($this->instructions as $opt => $props){
            if(key_exists('setting',$props)) {
                if(!$this->prereq($opt, $props['setting'])) continue;
            }
            if(key_exists('permission',$props)) {
                if(!$this->permission($props['permission'])) continue;
            }
            
            $element = $this->element($props, $opt);
            $menu .= "<li id='main-menu-$opt' name='$opt'><$element[0] class='user-menu-option $opt'><ion-icon name=\"$props[icon]\"></ion-icon><span class='user-menu-text'>$props[text]</span></$element[1]></li>";
        }
        return $menu .= "</ul></menu>";
    }

    function prereq(string|int $key, mixed $value){
        $k = key($value);
        if(app($key) === $value[$key]) return true;
        return false;
    }

    function permission(string $value) {
        if(user()->hasPermission(null, $value, false)) {
            return true;
        }
        return false;
    }

    function element($props){
        $element = ["div","div"];
        if(!key_exists("element",$props)) return $element;
        $element = [$props['element'],$props['element']];
        if(key_exists("href",$props)) $element[0] .= "href=\"$props[href]\"";
        return $element;
    }
}