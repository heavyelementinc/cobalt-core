<h1>{{!title}}</h1>
<header class="crudable-header--hypermedia-container">
    <div class="crudable-header--section crudable-header--left">
        {{!hypermedia.multidelete_button}}
        {{!hypermedia.filters}}
        Showing {{total_document_count}} of {{hypermedia.count}} document<?= plural($total_document_count); ?>
    </div>
    <div class="crudable-header--section crudable-header--center">
        {{!hypermedia.previous_page}}
        <form id="filter">
            <input name="{{page_param}}" value="{{page_number}}" style="width:6ch;text-align: right;box-sizing: border-box;text-align: center;"> of {{hypermedia.total_pages}}
        </form>
        {{!hypermedia.next_page}}
    </div>
    <div class="crudable-header--section crudable-header--right">
        <form id="search-form">
            <help-span id="search-help" value="@field:value:somevalue,@field2.child:value2,@field3:value"></help-span>
            {{!hypermedia.search}}
        </form>
    </div>
</header>
<flex-table>
    {{!table_header}}
    {{!documents}}
</flex-table>
<a href="{{!href}}" class="floater--new-item"></a>