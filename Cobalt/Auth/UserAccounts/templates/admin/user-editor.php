<?php
/** @var Cobalt\Auth\UserAccounts\UserPersistance $doc */
?>
<hgroup>
    <h1>{{doc.uname}}</h1>
    <small>{{doc._id}}</small>
</hgroup>

<tab-nav>
    <nav>
        <a href="#basics"></a>
    </nav>
    <div id="basics">
        <ul class="list-panel">
            <li>
                <?= $doc->fname->getLabel() ?>
                <?= $doc->fname->field() ?>
            </li>
        </ul>
    </div>
</tab-nav>