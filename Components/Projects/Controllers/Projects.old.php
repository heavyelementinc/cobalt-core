<?php
/**
 * Projects.php
 * @author Gardiner Bryant, Heavy Element
 * @copyright 2023 - Perry's Home Furnishings & Design
 */

namespace Components\Projects\Controllers;

use Components\Projects\Classes\ProjectManager;
use Components\Projects\Classes\ProjectSchema;
use Controllers\ClientFSManager;
use Controllers\Controller;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;


class Projects extends Controller {
    use ClientFSManager;
    function index() {
        $manager = new ProjectManager();
        $projects = iterator_to_array($manager->projectIndex());
        $views = view_each("/Components/Projects/templates/index-listing.html", $projects);
        $count = count($projects) - 1;
        if($count > 3) $count = "a few";
        add_vars([
            'title' => "Projects",
            'projects' => $views,
            'project_count' => $count,
            'latest' => $projects[0],
            'og_template' => '/Components/Projects/templates/og-index.html',
        ]);
        return view("/Components/Projects/templates/index.html");
    }

    function project($slug, $includeOtherProjects = true) {
        $manager = new ProjectManager();
        $project = $manager->project($slug);
        $href = route("\\Components\\Projects\\Controllers\\Projects@project", [(string)$project->_id]);
        register_user_bar_items(['edit' => "<a href='$href'><i name='eye'></i> Edit</a>"]);

        $projects = iterator_to_array($manager->projectIndex(false, $slug));
        $other_projects = view_each("/Components/Projects/templates/index-listing.html", $projects);
        
        $first = null;
        if(isset($_GET['i'])) {
            $this->initFS();
            /** @var Document */
            $first = $this->fs->findOne(['_id' => new ObjectId($_GET['i'])]);
        }

        $this->initFS();
        $galleryItems = $this->fs->find(['for' => $project->_id], $manager->sortOrder());
        $gallery = "";
        foreach($galleryItems as $doc) {
            $gallery .= view("/Components/Projects/templates/gallery-item.html", ['doc' => $doc]);
        }

        add_vars(
            [
                'title' => $project->name,
                'doc' => $project,
                'first' => ($first !== null) ? "/res/fs$first->filename" : (string)$project->{'primary'},
                'gallery' => $gallery, //$this->directoryListing("/res/fs","limitedGallery",['filter' => ['for' => $project->_id], 'options' => $manager->sortOrder()]),
                'og_template' => '/Components/Projects/templates/og-meta.html',
                'other_projects' => ($includeOtherProjects) ? $other_projects : "",
        ]
        );
        $rt = route("\\Components\\Projects\\Controllers\\ProjectAdmin@editor", [(string)$project->_id]);
        register_user_bar_items(['project-edit' => "<a href=\"$rt\"><i name=\"pencil\"></i> Edit</a>"]);
        return view("/Components/Projects/templates/project.php");
    }

    function showroom() {
        return $this->project(PROJECT_SHOWROOM_URL, false);
    }

    function studio() {
        return $this->project(PROJECT_STUDIO_URL, false);
    }
    
    function latestType($type) {
        $s = new ProjectSchema();
        $schema = $s->__get_schema();
        if(!key_exists($type, $schema['tags']['valid'])) throw new NotFound("That's not a valid tag");
        $manager = new ProjectManager();
        $project = $manager->find([
            'tags' => $type, 'published' => true
        ], [
            'projection' => ['url' => 1], 'sort' => ['_id' => -1], 'limit' => 1
        ]);
        
        $project = iterator_to_array($project);

        add_vars([
            'doc' => $project[0]
        ]);
        if(empty($project)) {
            header("X-Redirect: /projects/");
            
        }
        header("X-Redirect: /projects/".$project[0]->url);
        exit;
        // return view("/Components/Projects/templates/latest-direct.html");
    }
}