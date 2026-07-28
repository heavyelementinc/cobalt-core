<?php

use Cobalt\Auth\Users\Models\User;
use Cobalt\Auth\Permissions\Permission;
use Cobalt\Auth\Permissions\PermissionManager;
use Cobalt\Auth\Session\Models\Session;
use Cobalt\Model\Types\DateType;

$additionalFields = $doc->additional->__get_additional_user_tabs();

$links = "";
$panels = "";

foreach($additionalFields as $key => $details) {
    $links .= "<a href='#$key'>$details[name]</a>\n";
    $panels .= "<div id='$key'>".view($details['view'], ['doc' => $doc]) . "</div>\n";
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
            <a href="#sessions"><i name="login"></i> Sessions</a>
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
                        <?= $doc->uname->getLabel() ?>
                        <?= $doc->uname->field() ?>
                        <label>First & Last Name</label>
                        <div class="hbox" style="gap: 1ch">
                            <?= $doc->fname->field() ?>
                            <?= $doc->lname->field() ?>
                        </div>
                        <?= $doc->name_format->getLabel() ?>
                        <?= $doc->name_format->field() ?>
                    </div>
                </li>
                <li>
                    <?= $doc->email->getLabel() ?>
                    <?= $doc->email->field() ?>
                </li>
                <li>
                    <?= $doc->email->getLabel() ?>
                    <?= $doc->email->field() ?>
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
        <div id="sessions">
            <?php
                $sessions = new Session();
                $currentSession = (string)session()->_id;
                foreach($sessions->find(['represents' => user()->_id]) as $session) {
                    $browser = $session->details?->browser?->getValue() ?? "help-circle-outline";
                    $platform = $session->details?->platform?->getValue() ?? "help-circle-outline";
                    $expires_date = $session->expires->display();
                    $logged_in_user_count = $session->represents->length();
                    $created_date = date(DateType::FORMAT_SHORTHANDS['verbose'], $session->_id->getTimestamp());
                    $isSession = "";
                    if((string)$session->_id && $currentSession) $isSession = " session--current";
                    echo <<<HTML
                        <div class="sessions$isSession">
                            <div class="platforms">
                                <i class="browser" name="$browser->build"></i>
                                <i class="platform" name="$platform->build"></i>
                                <table>
                                    <tr>
                                        <th>User Count</th>
                                        <td>$logged_in_user_count</td>
                                    </tr>
                                    <tr>
                                        <th>IP Address</th>
                                        <td>$session->ip_address</td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td>$created_date</td>
                                    </tr>
                                    <tr>
                                        <th>Expires</th>
                                        <td>$expires_date</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    HTML;
                }
            ?>
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
    .sessions {
        display: block;
        border: 1.5px solid gray;
    }
    .session--current {
        border: 1.5px solid green;
    }
</style>