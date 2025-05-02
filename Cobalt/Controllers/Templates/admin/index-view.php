<h1>{{!title}}</h1>
<header class="crudable-header--hypermedia-container">
    <div class="crudable-header--section crudable-header--left">
        {{!hypermedia.multidelete_button}}
        {{!hypermedia.filters}}
        Showing {{total_document_count}} of {{hypermedia.count}} document<?= plural($total_document_count); ?>
    </div>
    <div class="crudable-header--section crudable-header--center">
        {{!hypermedia.previous_page}}
        <form>
            <input name="{{page_param}}" value="{{page_number}}" style="width:6ch;text-align: right;box-sizing: border-box;text-align: center;"> of {{hypermedia.total_pages}}
        </form>
        {{!hypermedia.next_page}}
    </div>
    <div class="crudable-header--section crudable-header--right">
        <form class="search-form">
            {{!hypermedia.search}}
        </form>
    </div>
</header>
<flex-table>
    {{!table_header}}
    {{!documents}}
</flex-table>
<a href="{{!href}}" class="floater--new-item"></a>