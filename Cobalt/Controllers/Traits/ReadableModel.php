<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

trait ReadableModel {
    var $initialized = false;

    public $name;
    public Model $model;
    
    // =========================================================================
    // ================================= READ ==================================
    // =========================================================================

    /**
     * This function is passed the document from the database (if you've done
     * your job right, it should already persist as an object)
     * 
     * The return value of this method is immediately sent to the client.
     */
    function read($document): GenericModel|BSONDocument|null {
        return $document;
    }

    final public function __read(ObjectId|string $id): GenericModel|BSONDocument|null {
        $result = $this->model->findOne(['_id' => new ObjectId($id)]);
        if(!$result) throw new NotFound("No records match that request");
        return $this->read($result);
    }

    function index():string {
        return view("Cobalt/Controllers/Templates/admin/index-view.php");
    }

    // function index_row(GenericMap $document):string {
    //     return $view($schema, $document);
    // }

    final public function __index():string {
        $this->init(new $this->model([]), $_GET);
        $new_doc_href = route("$this->name@__new_document");
        $hypermedia = $this->get_hypermedia();
        $body = $this->get_table_body();
        add_vars([
            'title'        => $this->friendly_name,
            'table_header' => $this->get_table_header(),
            'documents'    => $body,
            'hypermedia'   => $hypermedia,
            'next_page'    => $hypermedia['next'],
            'previous_page'=> $hypermedia['previous'],
            'page_number'  => $hypermedia['page'],
            'filters'      => $hypermedia['filters'],
            'search'       => $hypermedia['search'],
            'multidelete_button' => $hypermedia['multidelete_button'],
            'page_param'   => QUERY_PARAM_PAGE_NUM,
            'search_param' => QUERY_PARAM_SEARCH,
            'href'         => $new_doc_href,
        ]);
        $index = $this->index();
        return $index;
    }
    
    static public function route_details_read():array {
        return [];
    }

    static public function route_details_index():array {
        return [];
    }
}