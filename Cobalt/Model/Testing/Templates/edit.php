<hgroup>{{title}}</hgroup>

<ul class='list-panel'>
<li>
    <label>{{doc.some_string.getLabel()}}</label>
    {{doc.some_string.field()}}
</li>
<li>
    <label>{{doc.other_string.getLabel()}}</label>
    {{doc.other_string.field()}}
</li>
<li>
    <label>{{doc.array_type.0.field.getLabel()}}</label>
    {{doc.array_type.0.field.field()}}
</li>
<li>
    <label>{{doc.array_type.1.field.getLabel()}}</label>
    {{doc.array_type.1.field.field()}}
</li>

<li>
    <label>{{doc.submodel.data.a_number.getLabel()}}</label>
    {{doc.submodel.data.a_number.field()}}
</li>
</ul>