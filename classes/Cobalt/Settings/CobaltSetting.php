<?php

namespace Cobalt\Settings;

class CobaltSetting {

    /**
     * The property where we store the setting.
     * @var mixed
     */
    protected $value = null;
    protected $settings = null;
    
    function __construct($name, $definition, &$user_modified_settings, &$settings, $reference, &$toCache) {
        $this->name = $name;
        $this->defaultValue = $definition['default'];
        $this->directives   = $definition['directives'];
        $this->meta         = $definition['meta'] ?? null;
        $this->validate     = $definition['validate'];
        $this->user_modified_settings = $user_modified_settings;
        $this->allSettings  = $settings;

        $this->reference = $reference;
        $this->toCache = $toCache;
    }

    function get_value() {
        $value = $this->defaultValue;
        if(isset($this->user_modified_settings[$this->name])) return $this->user_modified_settings[$this->name];
        $mutant = $value;
        foreach($this->directives as $directive => $data) {
            if(!method_exists($this, "directive_$directive")) continue;

            $mutant = $this->{"directive_$directive"}($mutant, $data);
        }

        return $mutant;
    }

    function directive_public($value) {
        $GLOBALS['PUBLIC_SETTINGS'][$this->name] = $value;
        return $value;
    }

    /**
     * If an environment variable is set, it overrides everything else.
     * @param mixed $value 
     * @param mixed $data 
     * @return mixed 
     */
    function directive_env($value, $data) {
        $environment = getenv($data);
        if (!$environment) return $value;
        if ($environment) return $environment;
    }

    function directive_alias($value, $data) {
        if(!$value) return $value;
        if($this->user_modified_settings[$data]) return $this->user_modified_settings[$data];
        if($this->allSettings[$data]) return $this->allSettings[$data];
        throw new AliasMissingDependency("Setting $this->name depends on $data but it's not yet defined");
    }

    function directive_prepend(array|null $value, array $data) {
        return array_merge($value ?? [], $data ?? []);
    }

    function directive_merge(array|null $value, array $data) {
        return array_merge($data ?? [], $value ?? []);
    }

    function directive_mergeAll(array|null $value, array $data) {
        return array_merge_recursive($data ?? [], $value ?? []);
    }
    
    function directive_push(array|null $value, array $data) {
        if(!$value) $value = [];
        $mutant = $value;
        foreach ($data as $ref) {
            $pushable = lookup_js_notation($ref, $this->allSettings, false);
            // if(is_string($value)) $value = [$value];
            array_push($mutant, $pushable);
        }
        return array_unique($mutant);
    }

    function directive_style($value, $data) {
        if(!isset($this->toCache['root-style'])) $this->toCache['root-style'] = "";
        if ($this->name === "fonts") {
            foreach ($value as $type => $v) {
                if(!isset($this->toCache['vars-web'])) $this->toCache['vars-web'] = [];
                $this->toCache['vars-web']["$type-family"] = $v['family'];
            }
        }
        if($this->name === "css-vars") {
            foreach ($value as $type => $v) {
                $this->toCache['root-style'] .= "--project-$type: $v;\n";
            }
        }
        return $value;
    }

    function directive_required($value, array $data) {
        $rq = [
            'is'  => fn($name, $value) => $this->{$name} === $value,
            'not' => fn($name, $value) => $this->{$name} !== $value,
            'gt'  => fn($name, $value) => $this->{$name}   > $value,
            'gte' => fn($name, $value) => $this->{$name}  >= $value,
            'lt'  => fn($name, $value) => $this->{$name}   < $value,
            'lte' => fn($name, $value) => $this->{$name}  <= $value,
        ];

        return $value;
    }
}
