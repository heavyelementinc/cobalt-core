<?php

use Cobalt\Auth\Users\Models\User;
use Cobalt\Auth\Permissions\Permission;
use Cobalt\Auth\Permissions\PermissionManager;

$additionalFields = $doc->additional->__get_additional_user_tabs();

$links = "";
$panels = "";

foreach($additionalFields as $key => $details) {
    $links .= "<a href='#$key'>$details[name]</a>";
    $panels .= "<div id='$key'>".view($details['view'], ['doc' => $doc]) . "</div>";
}

/** @var User $doc */
?>
<hgroup>
    <h1 class="name-tag"><?= $doc->name("F L") ?></h1>
    <small>{{doc._id}}</small>
</hgroup>

<form-request method="{{method}}" action="{{action}}" {{autosave}}>
    <tab-nav>
        <nav>
            <a href="#basics"><i name="card-account-details"></i> Basic Info</a>
            <a href="#security"><i name="lock"></i> Security</a>
            <a href="#permissions"><i name="security"></i> Permissions</a>

            <?= $links ?>
        </nav>
        <div id="basics">
            <ul class="list-panel">
                <li class="hbox">
                    <div>
                        <?= $doc->avatar->getLabel() ?>
                        <?= $doc->avatar->field() ?>
                    </div>
                    <div>
                        <label>First & Last Name</label>
                        <?= $doc->fname->field() ?>
                        <?= $doc->lname->field() ?>
                    </div>
                </li>
                <li>
                    <?= $doc->uname->getLabel() ?>
                    <?= $doc->uname->field() ?>
                </li>
                <li>
                    <?= $doc->uname->getLabel() ?>
                    <?= $doc->uname->field() ?>
                </li>
            </ul>
        </div>
        <div id="security">
            <ul class='list-panel'>
                <li>
                    <?= $doc->pword->getLabel() ?>
                    <?= $doc->pword->field() ?>
                </li>
            </ul>
        </div>
        <div id="permissions">
            <?php
            $permissions = [];
            /**
             * @var string $key
             * @var Permission $perm
             */
            foreach((new PermissionManager)->getAllPermissions() as $key => $perm) {
                $label = $perm->getLabel();
                $small = $perm->getHelp();
                $checked = ($doc::explicitPermission($doc, $key)) ? "checked=\"checked\"" : "";
                $group = $perm->getGroup();
                if(!key_exists($group, $permissions)) {
                    $permissions[$group] = "<fieldset><legend>$group</legend><ul class='list-panel'>";
                }
                $permissions[$perm->getGroup()] .= <<<HTML
                <li>
                    <input-switch name="permissions.$key" $checked></input-switch>
                    <div>
                        <label>$label</label>
                        <small>$small</small>
                    </div>
                </li>
                HTML;
            }
            echo implode("</ul></fieldset>", $permissions) . "</ul></fieldset>";
            ?>
            <div>
                <h2>Full Admin Rights</h2>
                <ul class="list-panel" danger>
                    <li>
                        {{doc.is_root.field()}}
                        <div>
                            <label>Root Privileges</label>
                            <small>
                                Give this user complete control over this application <strong>(dangerous)</strong>. 
                                This is essentially as though the user has every permission listed above!
                        </small>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <?= $panels ?>
    </tab-nav>
</form-request>

<style>
    .list-panel[danger] {
        border-color: var(--issue-color-5);
        background: var(--issue-color-3);
        color: var(--issue-color-3-fg);
        --input-element-active: var(--issue-color-5);
    }
</style>