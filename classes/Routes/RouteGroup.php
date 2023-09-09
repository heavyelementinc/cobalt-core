<?php

namespace Routes;

use Exception;

class RouteGroup {
    private $listGroupId = "";
    private $iconMode  = false;
    private $currentRt = null;
    private $groupName = null;
    private $excludeWrappers = false;
    private $classes = [];

    function __construct(string $groupMode, string $currentRoute, bool $iconMode = false) {
        $this->setGroup($groupMode);
        $this->setCurrentRoute($currentRoute);
        $this->setIconMode($iconMode);
    }

    public function setGroup(string $group) {
        $this->groupName = $group;
    }

    public function setID(string $id) {
        $this->listGroupId = $id ?? "";
    }

    public function setIconMode(bool $mode) {
        $this->iconMode = $mode;
    }

    public function setCurrentRoute(string $route) {
        $this->currentRt = $route;
    }

    public function setClasses(array $classes) {
        $this->classes = $classes;
    }

    public function setClassesFromString(string $classes) {
        $this->classes = explode(" ",$classes);
    }

    public function setExcludeWrappers(bool $mode) {
        $this->excludeWrappers = $mode;
    }

    function getRouteGroup() {
        $groups = getRouteGroups();
        if(!key_exists($this->groupName, $groups)) throw new Exception("The group you're requesting does not exist");
        $grp = $groups[$this->groupName];
        uasort($grp, 
            fn ($a, $b) => $a['anchor']['order'] ?? $a['navigation'][$this->groupName]['order'] ?? $a['nat_order'] - $b['anchor']['order'] ?? $b['navigation'][$this->groupName]['order'] ?? $b['nat_order']
        );
        return $grp;
    }

    public function render($groupName = null) {
        if($groupName) $this->setGroup($groupName);
        
        $li_wrapper_start = "<li>";
        $li_wrapper_end = "</li>";
        // if($this->excludeWrappers) {
        //     $li_wrapper_start = "";
        //     $li_wrapper_end = "";
        // }
        
        $rendered = [];
        foreach($this->getRouteGroup() as $index => $route) {
            if(!$this->authorizedForRoute($route)) continue;
            // Apply the <li> tags (or don't)
            $rendered[] = $li_wrapper_start . $this->getEntry($route, $index) . $li_wrapper_end;
        }
        
        if($this->excludeWrappers) return implode("",$rendered);

        // Prepare for returning the rendered links
        $id = ($this->listGroupId) ? " id=\"$this->listGroupId\"" : "";
        if($this->iconMode) $this->classes[] = "directory--icon-group";
        return "<ul$id class='directory--group ".implode(" ", $this->classes)."'>".implode("",$rendered)."</ul>";
    }

    function getEntry($entry):string {
        $icon = $this->getIcon($entry);
        $link = $this->getLink($entry);
        $label = $this->getLabel($entry);
        $submenu = $this->getSubmenu($entry);
        $classes = $this->getClasses($entry);
        $unread = $this->getUnread($entry);
        if($link === $this->currentRt) $classes[] = "navigation--current";
        return "<a href=\"$link\" class=\"".implode(" ", $classes)."\">{$icon}{$label}{$unread}{$submenu}</a>";
    }

    /**
     * The label for an anchor link. Order is navigation->[group]->label, anchor->label, navigation->[group]->name, anchor->name
     * @param mixed $entry 
     * @return string 
     */
    private function getLabel($entry):string {
        return $entry['navigation'][$this->groupName]['label'] ?? $entry['anchor']['label'] ?? $entry['navigation'][$this->groupName]['name'] ?? $entry['anchor']['name'] ?? "Unknown Label";
    }

    private function getIcon($entry):string {
        if(!$this->iconMode) return "";
        $icon = $entry['navigation'][$this->groupName]['icon'] ?? $entry['anchor']['icon'];
        return "<i name='{$icon}'></i> ";
    }

    private function getLink($entry):string {
        $candidate = $entry['real_path'];
        $missing_vars = false;
        if(strpos($candidate,"...")) $missing_vars = true;
        if(strpos($candidate,"{")) $missing_vars = true;
        if($missing_vars) $candidate = $this->addContextRootToLink($entry, $entry['navigation'][$this->groupName]['href'] ?? $entry['anchor']['href']) ?? $candidate;
        if($missing_vars && $entry['real_path'] === $candidate) throw new Exception("Route path contains a variable but is missing an HREF!");
        
        return $candidate;
    }

    private function addContextRootToLink($entry, $route) {
        if(!$route) return null;
        return $entry['context_root'] . $route;
    }

    private function getSubmenu($entry) {
        $submenu = $entry['navigation'][$this->groupName]['submenu_group'] ?? $entry['anchor']['submenu_group'] ?? "";
        if(!$submenu) return "";
        $menu = new RouteGroup($submenu, $this->currentRt, $this->iconMode);
        $menu->setClasses(['directory--submenu']);
        return " ".$menu->render();
    }

    private function getClasses($entry):array {
        return $entry['navigation'][$this->groupName]['classes'] ?? $entry['anchor']['classes'] ?? [];
    }

    private function getUnread($entry):string {
        $unread = $entry['navigation'][$this->groupName]['unread'] ?? $entry['anchor']['unread'] ?? $entry['unread'];
        if(!$unread || $unread instanceof \Closure === false) return "";
        $ur = $unread($entry);
        if(!$ur) return "";
        return " <span class='unread'>{$ur}</span>";
    }

    private function getAttributes($entry) {
        // if($entry['attributes'])
    }

    private function authorizedForRoute($route) {
        if(!$route['permission']) return true;
        return has_permission($route['permission'], null, null, false);
    }
}