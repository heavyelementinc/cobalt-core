<?php

namespace Cobalt\EventListings\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\EventListings\Classes\CalendarEvent;
use Cobalt\EventListings\Models\Event;
use Cobalt\Model\Model;
use CobaltEvents\EventManager;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

class Events extends ModelController {
    public static function defineModel(): Model {
        return new Event();
    }

    public function edit($document): string {
        return view("/Cobalt/EventListings/templates/admin/new-editor.php", ['doc' => $document]);
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete \"$document->event_name\"?",
            'okay' => 'Okay',
            'post' => $_POST
        ];
    }

    public function public_index():string {
        $results = iterator_to_array($this->model->getPublicListing());
        
        $views = "";
        if($results) {
            foreach($results as $doc) {
                $views .= view('Cobalt/EventListings/templates/web/event-item.php', ['doc' => $doc]);
            }
        }
        if(!$views) $views = "<p class=\"event-listing--no-results\">There are currently no events. Please check back later.</p>";
        
        add_vars([
            'title' => 'Events',
            'events' => $views
        ]);

        register_user_bar_items([
            '_page_post_new' => "<a href='/admin/events/new'><i name='plus'></i> New Event</a>",
            '_page_post_edit' => "<a href='/admin/events/'><i name='pencil'></i> Manage Events</a>",
        ]);
        return view("Cobalt/EventListings/templates/web/public-index.php");
    }
    
    public function public_listing($id):string {
        $now = new UTCDateTime();
        $result = $this->model->findOne([
            '_id' => new ObjectId($id),
            '$or' => Event::PUBLIC_LISTING_OR_QUERY
        ]);
        if(!$result) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        add_vars([
            'title' => $result->public_head->value ?? $result->headline,
            'og_description' => strip_tags($result->public_body->firstParagraph() ?? $result->body->md()),
            'og_image_path' => get_image_url($result->public_image) ?? __APP_SETTINGS__['opengraph_image'],
            'og_image_width' => $result->public_image['meta']['width'] ?? __APP_SETTINGS__['opengraph_image_X'],
            'og_image_height' => $result->public_image['meta']['height'] ?? __APP_SETTINGS__['opengraph_image_Y'],
        ]);
        return view("Cobalt/EventListings/templates/web/event-listing.php", ['doc' => $result]);
    }

    public function iCalEvent($id):string {
        $event = $this->model->findOne([
            '_id' => new ObjectId($id)
        ]);
        if($event === null) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        /** @var Event $event */
        $event->getICalEvent()->download();
        exit;
    }
}