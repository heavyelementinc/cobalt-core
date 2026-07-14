<?php
use Components\ServiceAreas\Models\County;

/** 
 * @var Town $doc
 **/
?>
<hgroup>
    <h1>{{title}}</h1>
</hgroup>
<form-request method="POST" action="{{action}}" {{autosave}}>
    <tab-nav>
        <nav>
            <a href="#basic"><i name="city"></i> Basic</a>
            <a href="#location"><i name="map-marker-radius"></i> Location</a>
            <a href="#meta"><i name="table"></i> Meta</a>
        </nav>
        <div id="basic">
            <ul class="list-panel">
                <li>
                    {{doc.name.getLabel()}}
                    {{doc.name.field()}}
                </li>
                <li>
                    {{doc.href.getLabel()}}
                    {{doc.href.field()}}
                </li>
                <li>
                    {{doc.state.getLabel()}}
                    {{doc.state.field()}}
                </li>
                <li>
                    {{doc.type.getLabel()}}
                    {{doc.type.field()}}
                </li>
                <li>
                    {{doc.county.getLabel()}}
                    {{doc.county.field()}}
                </li>
                <li>
                    {{doc.inc.getLabel()}}
                    {{doc.inc.field()}}
                </li>
                <li>
                    {{doc.include.getLabel()}}
                    {{doc.include.field()}}
                </li>
            </ul>
        </div>
        <div id="location">
            <ul class="list-panel">
                <li>
                    {{doc.blurb.getLabel()}}
                    {{doc.blurb.field()}}
                </li>
                <li>
                    {{doc.img.getLabel()}}
                    {{doc.img.field()}}
                </li>
                <li>
                    {{doc.credit.getLabel()}}
                    {{doc.credit.field()}}
                </li>
                <li>
                    {{doc.nearby.getLabel()}}
                    {{doc.nearby.field()}}
                </li>
            </ul>
        </div>
        <div id="meta">
            <ul class="list-panel">
                <li>
                    {{doc.seat.getLabel()}}
                    {{doc.seat.field()}}
                </li>
                <li>
                    {{doc.pop.getLabel()}}
                    {{doc.pop.field()}}
                </li>
                <li>
                    {{doc.mi2.getLabel()}}
                    {{doc.mi2.field()}}
                </li>
                <li>
                    {{doc.km2.getLabel()}}
                    {{doc.km2.field()}}
                </li>
                <li>
                    {{doc.geo.getLabel()}}
                </li>
                <li>
                    {{doc.slug.getLabel()}}
                    {{doc.slug.field()}}
                </li>
            </ul>
        </div>
    </tab-nav>
    {{!submit_button}}
</form-request>