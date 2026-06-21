<?php

namespace Components\Projects\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\DBManagement\CobaltCursor;
use Cobalt\Model\Model;
use Components\Projects\Models\Project;
use Exceptions\HTTP\NotFound;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;

class ClientProjects extends ModelController {
    public static function defineModel(): Model {
        return new Project();
    }

    public function index_options():array {
        return ['sort' => ['order' => -1]];
    }

    public function edit($document): string {
        // $href = route(static::class."@publicListing", [(string)$document->url]);
        $url = "/projects/$document->url";
        $val = (string)$document->url;
        set('title', "Edit $document->name");
        register_user_bar_items(['view' => "<a href='$url'><i name='eye'></i> View</a>"]);
        return view("Components/Projects/templates/admin/editor.v2.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete \"$document->title\"?",
            'okay' => 'Okay',
            'post' => $_POST
        ];
    }

    public function publicIndexQuery():null|CobaltCursor {
        return $this->model->find(
            [
                'published' => true,
            ],
            [
                'limit' => 20,
                'sort' => ['order' => -1],
                'projection' => [
                    'images' => 0
                ]
            ]
        );
    }

    private function getIndexListing():array {
        $projects = iterator_to_array($this->publicIndexQuery() ?? []);
        $html = "";
        /** @var Project $model */
        foreach($projects as $model) {
            $html .= $model->getIndexEntry();
        }
        return [$html, $projects];
    }
    
    public function publicIndex() {
        [$html, $projects] = $this->getIndexListing();
        $project_count = count($projects) - 1;
        if($project_count > 3) $project_count = "a few";

        add_vars([
            'title' => "Projects",
            'projects' => $html,
            'project_count' => $project_count,
            'latest' => $projects[0],
            'og_template' => 'Components/Projects/templates/parts/og-index.php',
        ]);
        return view("Components/Projects/templates/index.php");
    }

    public function publicListing($slug) {
        return $this->getPublicListing(['url' => $slug, 'published' => true], true);
    }

    private function getPublicListing(array $query, bool $includeOtherProjects) {
        $project = $this->model->findOne($query);
        if(!$project) throw new NotFound("Not found");
        $href = route(static::class."@__edit", [(string)$project->_id]);
        register_user_bar_items(['edit' => "<a href='$href'><i name='pencil'></i> Edit</a>"]);

        $gallery = "";
        foreach($project->images as $doc) {
            $gallery .= view("Components/Projects/templates/parts/gallery-item.php", ['doc' => $doc]);
        }

        $other_projects = ['',''];
        if($includeOtherProjects) {
            $other_projects = $this->getIndexListing();
        }
        $first = $project->images[0];
        $lets_chat_button = <<<HTML
        <div class="hbox">
            <a href="/intake" class="button">Let's Chat</a>
        </div>
        HTML;
        if($includeOtherProjects == false) $lets_chat_button = "";
        add_vars([
            'title' => $project->name,
            'doc' => $project,
            'first' => $first,
            'gallery' => $gallery,
            'og_template' => 'Components/Projects/templates/parts/og-meta.php',
            'other_projects' => ($includeOtherProjects) ? $other_projects[0] : "",
            'lets_chat_button' => $lets_chat_button,
        ]);
        return view("Components/Projects/templates/project.php");
    }
}