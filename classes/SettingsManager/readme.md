The SettingsManager class should only need to exist ONCE for each session. 
The default_settings file defines meta information about each setting. This 
file may define directives to be handled by this class upon instantiation.

========================================
Valid directive keywords are as follows:
========================================

  default   - The default value of this setting. If it's not specified by the
              app settings, this will be the setting's value

              NOTE that the default directive DOES NOT start with a '$'

  $env      - The $env directive will import an environment variable IF IT 
              EXISTS using the directive's value as the name of the env 
              variable. If the env variable is _not_ specified, then this
              directive will FAIL SILENTLY and inherit its value from the 
              app's settings OR the default definition.
             
              NOTE that if the variable DOES exist, it will OVERRIDE whatever
              app settings might exist.

  $loadJSON - If the $loadJSON key is found and set to `true` or `false`, the
              value of 'default' or the value of app_setting will be used as 
              a path name, with $loadJSON used as the second argument for 
              json_decode.

              NOTE that an SettingsManagerException will be thrown if
              pathname does not exist and a JSON parse exception will be 
              thrown if there is a problem parsing the JSON.

              ALSO NOTE that pathnames which do not begin with a `/` will 
              have either the __ENV_ROOT__ or __APP_ROOT__ prepended to them 
              depending on which file specified the path.

  $alt      - (String) JS object path to another ALREADY DEFINED setting 
              value. $alt will check if the current value is a string and if 
              that string is empty it will reference the name of the value 
              specified using JS syntax.
              
              This is meant to allow certain settings to be left out of the 
              app's settings and still inherit a value relevant to the app.

  $combine  - Processes the associated array of strings and combines them 
              into a STRING, look at the the value of each string to see if 
              there's a matching value defined in this class.

              If the VALUE of the property before the $combine operation is 
              bool false, the combine operation is ignored and an empty 
              string is provided instead.

ARRAY HANDLING
==============

  $prepend  - Prepends the default values with the app's values. An array 
              with unique entries will be stored as the value of the setting.

  $merge    - Merges the 'default' of the default settings and the value of 
              the app setting with app settings taking precedence.

  $mergeAll - Recursively merges the 'default' of default settings and the 
              value of the app setting with app settings taking precedence.

  $push     - Accepts an array of variable names, will append those variables
              to the end of the default and app's specified array

MISC HANDLING
=============

  $required - This directive accepts a key => pair value of Setting_name => 
              bool value and will compare the value of Setting_name to the 
              bool specified. If the comparison FAILS, the current setting 
              will be set to the value of on_fail_value or false if no 
              on_fail_value is supplied. If the comparison for all required 
              settings succeeds, either the app's setting OR the default 
              value will be allowed to stand.

              on_fail_value should be supplied as a key in the $required
              directive. It doesn't matter what order the list is supplied.

              NOTE: non-boolean values of $required settings may fail the 
              check. Please supply an on_fail_value for non-boolean values.

  $public   - If truthy, this setting is exposed to the client as JavaScript.
              Should be last directive.

==============================
Defining App-specific Settings
==============================

Follow the same syntax as the default_settings file. The new setting 
definition must:

  #1 Have a unique name--not otherwise defined in default_settings
  #2 MUST have a 'default' directive--even if other directives override its 
     value

Unrecognized settings in tmp_app_setting_values lacking definition directives 
will throw a warning.