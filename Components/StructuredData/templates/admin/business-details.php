<?php

use Components\BusinessSchema\Model\BusinessInfo;
use Components\StructuredData\Controllers\BusinessDetails;

/**
 * @var BusinessInfo $doc
 */
?>
<h1>Business Details</h1>

<form-request method="POST" action="<?= BusinessDetails::get_route_href('__update') ?>" {{autosave}}>
    <tab-nav>
        <nav>
            <a href="#basics"><i name="pencil"></i> Basics</a>
            <a href="#hours"><i name="clock"></i> Hours of Operation</a>
            <a href="#money"><i name="credit-card"></i> Money</a>
            <a href="#org"><i name=""></i> Organization</a>
        </nav>
        <div id="basics">
            <ul class="list-panel">
                <li>
                    <?= $doc->{'@type'}->getLabel() ?>
                    <?= $doc->{'@type'}->field() ?>
                </li>
                <li>
                    {{doc.phone.getLabel()}}
                    {{doc.phone.field()}}
                </li>
                <li>
                    {{doc.address.getLabel()}}
                    {{doc.address.field()}}
                </li>
                <li>
                    {{doc.duns.getLabel()}}
                    {{doc.duns.field()}}
                </li>
                
                <li>
                    {{doc.keywords.getLabel()}}
                    {{doc.keywords.field()}}
                </li>
                <li>
                    {{doc.niacs.getLabel()}}
                    {{doc.niacs.field()}}
                </li>
                <li>
                    {{doc.skills.getLabel()}}
                    {{doc.skills.field()}}
                </li>
                <li>
                    {{doc.hasMap.getLabel()}}
                    {{doc.hasMap.field()}}
                </li>
                <li>
                    {{doc.slogan.getLabel()}}
                    {{doc.slogan.field()}}
                </li>
            </ul>
        </div>
        <div id="hours">
            <ul class="list-panel">
                <li>
                    {{doc.openingHours.getLabel()}}
                    {{doc.openingHours.field()}}
                </li>
                <li>
                    {{doc.openingHoursSpecification.getLabel()}}
                    <flex-table>
                    <?php
                    $schema = $doc->readSchema()['openingHoursSpecification']['schema'];
                    $i = 0;
                    foreach($schema['dayOfWeek']['valid'] as $k => $v) {
                        $opens = $schema['opens'][0]->getLabel() . $schema['opens'][0]->field("", ['name' => "openingHoursSpecification.$i.opens", 'placeholder' => 'Opens']);
                        $closes = $schema['closes'][0]->getLabel() . $schema['closes'][0]->field("", ['name' => "openingHoursSpecification.$i.closes", 'placeholder' => 'Closes']);
                        $validFrom = $schema['validFrom'][0]->getLabel() . $schema['validFrom'][0]->field("", ['name' => "openingHoursSpecification.$i.validFrom"]);
                        $validThrough = $schema['validThrough'][0]->getLabel() . $schema['validThrough'][0]->field("", ['name' => "openingHoursSpecification.$i.validThrough"]);
                        echo <<<HTML
                            <flex-row>
                                <flex-header>$v</flex-header>
                                <flex-cell class="vbox">
                                    <div class="hbox">
                                        <div>$opens</div> &mdash; <div>$closes</div>
                                    </div>
                                    <details>
                                        <summary>Advanced</summary>
                                        <div class="hbox">
                                            <div>$validFrom</div> &mdash; <div>$validThrough</div>
                                        </div>
                                        <input type="hidden" name="openingHoursSpecification.$i.dayOfWeek" value="$k">
                                    </details>
                                </flex-cell>
                            </flex-row>
                        HTML;
                        $i += 1;
                    }
                    ?>
                    </flex-table>
                </li>
            </ul>
        </div>
        <div id="money">
            <ul class="list-panel">
                <li>
                    {{doc.priceRange.getLabel()}}
                    <div class="hbox">$ <?= $doc->priceRange->field("", ['type' => 'range']) ?> $$$$$</div>
                </li>
                <li>
                    {{doc.currenciesAccepted.getLabel()}}
                    {{doc.currenciesAccepted.field()}}
                </li>
                <li>
                    {{doc.paymentAccepted.getLabel()}}
                    {{doc.paymentAccepted.field()}}
                </li>
                <li>
                    {{doc.acceptedPaymentMethod.getLabel()}}
                    {{doc.acceptedPaymentMethod.field()}}
                </li>
                <li>
                    {{doc.priceRange.getLabel()}}
                    <div class="hbox">$ <?= $doc->priceRange->field("", ['type' => 'range']) ?> $$$$$</div>
                </li>
            </ul>
        </div>
        <div id="org">
            <ul class="list-panel">
                <li>
                    {{doc.foundingDate.getLabel()}}
                    {{doc.foundingDate.field()}}
                </li>
                <li>
                    {{doc.founder.getLabel()}}
                    {{doc.founder.field()}}
                </li>
                <li>
                    {{doc.nonProfitStatus.getLabel()}}
                    {{doc.nonProfitStatus.field()}}
                </li>
                <li>
                    {{doc.numberOfEmployees.getLabel()}}
                    {{doc.numberOfEmployees.field()}}
                </li>
            </ul>
        </div>
    </tab-nav>
    <?= $button ?>
</form-request>