<?php

namespace Routes;

use Exception;

class RouteGroup {
    private $listGroupId = "";
    private $iconMode  = false;
    private $iconPanel = false;
    private $submenuVisibility = false;
    private $currentRt = null;
    private $groupName = null;
    private $excludeWrappers = false;
    private $required = false;
    private $submenuDepth = 1;
    private $unorderedListTags = ["<ul>","</ul>"];
    private $listItemTags = ["<li>", "</li>"];
    private $externalLinkCache = [];
    private $classes = [];

    function __construct(string $groupMode, string $currentRoute, bool $iconMode = false, int $allowedSubmenuDepth = 1) {
        $this->setGroup($groupMode);
        $this->setCurrentRoute($currentRoute);
        $this->setIconMode($iconMode);
        $this->setSubmenuDepth($allowedSubmenuDepth);
    }

    public function setGroup(string $group) {
        $this->groupName = $group;
        return $this;
    }

    public function setSubmenuDepth(int $depth) {
        $this->submenuDepth = $depth;
        return $this;
    }

    public function setID(string $id) {
        $this->listGroupId = $id ?? "";
        return $this;
    }

    public function setIconMode(bool $mode) {
        $this->iconMode = $mode;
        return $this;
    }

    public function setIconPanel(bool $mode) {
        $this->setIconMode($mode);
        $this->iconPanel = $mode;
        return $this;
    }

    public function setCurrentRoute(string $route) {
        $this->currentRt = $route;
        return $this;
    }

    public function setClasses(array $classes) {
        $this->classes = $classes;
        return $this;
    }

    public function setClassesFromString(string $classes) {
        $this->classes = explode(" ",$classes);
        return $this;
    }

    public function setExcludeWrappers(bool $mode) {
        $this->excludeWrappers = $mode;
        if($this->excludeWrappers) $this->unorderedListTags = ["", ""];
        else $this->unorderedListTags = ["<ul>", "</ul>"];
        return $this;
    }

    public function setSubmenuVisibilty(bool $mode) {
        $this->submenuVisibility = $mode;
        return $this;   
    }

    /**
     * [
     * 
     *    'href'  => (string)
     *    'label' => (string)
     *    'order' => (int)
     *    'icon'  => (string)
     * 
     * ]
     */
    public function setExternalLinks($links = []) {
        foreach($links as $link) {
            $this->externalLinkCache[] = array_merge([
                'anchor' => ['label' => 'Unknown']
            ],
            $link,
            [
                'real_path' => $link['href'] ?? "Unknown",
                'anchor'    => [
                    'label' => $link['label'] ?? "Unknown"
                ]
            ],[
                'externalLink' => true
            ]);
        }
        return $this;
    }

    function getRouteGroup() {
        $groups = getRouteGroups();
        if(!key_exists($this->groupName, $groups)) {
            if($this->required === true) throw new Exception("The group you're requesting does not exist");
            return "";
        }
        $grp = $groups[$this->groupName];
        array_push($grp, ...$this->externalLinkCache);
        uasort($grp,
            fn ($a, $b) => $a['anchor']['order'] ?? $a['navigation'][$this->groupName]['order'] ?? $a['nat_order'] - $b['anchor']['order'] ?? $b['navigation'][$this->groupName]['order'] ?? $b['nat_order']
        );
        return $grp;
    }

    public function render($groupName = null) {
        if($groupName) $this->setGroup($groupName);
        
        $rendered = [];
        foreach($this->getRouteGroup() as $index => $route) {
            if(!$this->authorizedForRoute($route)) continue;
            // Apply the <li> tags (or don't)
            $rendered[] = $this->getEntry($route, $index);
        }
        
        if($this->excludeWrappers) return implode("",$rendered);

        // Prepare for returning the rendered links
        $id = ($this->listGroupId) ? " id=\"$this->listGroupId\"" : "";
        if($this->iconMode) $this->classes[] = "directory--icon-group";
        if($this->iconPanel) $this->classes[] = "directory--icon-panel";
        if($this->submenuVisibility) $this->classes[] = "subgroup--display";

        return "<ul$id class='directory--group ".implode(" ", $this->classes)."'>".implode("",$rendered)."</ul>\n";
    }

    function getEntry($entry):string {
        $icon = $this->getIcon($entry);
        $link = $this->getLink($entry);
        $label = $this->getLabel($entry);
        $submenu = $this->getSubmenu($entry);
        $classes = $this->getClasses($entry);
        $unread = $this->getUnread($entry);
        if($link === $this->currentRt) $classes[] = "navigation--current";
        if($entry['externalLink']) $classes[] = "external-link";
        return "{$this->listItemTags[0]}<a href=\"$link\" class=\"".implode(" ", $classes)."\">{$icon}{$label}{$unread}</a>{$submenu}{$this->listItemTags[1]}\n";
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
        $color = "";
        $icon = $entry['navigation'][$this->groupName]['icon'] ?? $entry['anchor']['icon'];
        // if($this->iconPanel) {
            $color = $entry['navigation'][$this->groupName]['icon_color'] ?? $entry['anchor']['icon_color'] ?? "";
            if($color) {
                $lin = "linear-gradient";
                if(substr($color, 0, strlen($lin)) === $lin) $color = " style=\"background: $color;background-clip:text;-webkit-text-fill-color:transparent;text-fill-color:transparent;\"";
                else $color = " style='color:$color'";
            }
        // }
        return "<i name='{$icon}'$color></i> ";
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
        if($this->submenuDepth <= 0) return "";
        $submenu = $entry['navigation'][$this->groupName]['submenu_group'] ?? $entry['anchor']['submenu_group'] ?? "";
        if(!$submenu) return "";
        if($submenu === $this->groupName) throw new Exception("\"$this->groupName\" is specified as a submenu_group, but it is the current group.");
        $menu = new RouteGroup($submenu, $this->currentRt, $this->iconMode, $this->submenuDepth - 1);
        $menu->setClasses(['directory--submenu']);
        $menu->setSubmenuVisibilty($this->submenuVisibility);
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