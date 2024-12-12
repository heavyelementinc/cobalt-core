<table>
    <tr>
        <th>some_string:</th><td>{{#doc.some_string.md()}}</td>
    </tr>
    <tr>
        <th>other_string:</th><td>{{#doc.other_string}}</td>
    </tr>
    <tr>
        <th>array_type:</th><td>{{#doc.array_type.0.field}}, {{#doc.array_type.1.field}}</td>
    </tr>
    <tr>
        <th>number:</th><td>{{#doc.number}}</td>
    </tr>
</table>

<table>
    <tr>
        <th>Model->details</th><td>{{#doc.model.details}}</td>
    </tr>
    <tr>
        <th>Model->string</th><td>{{#doc.model.string}}</td>
    </tr>
    <tr>
        <th>submodel.data.another_model</th><td>{{#doc.submodel.data.another_model}}</td>
    </tr>
</table>

{{#doc.prototype_test()}}