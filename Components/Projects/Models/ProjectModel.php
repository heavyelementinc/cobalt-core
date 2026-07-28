<?php

namespace Components\Projects\Models;

use Cobalt\Components\Projects\Models\CoverImageModel;
use Cobalt\DataModel\Directives\ReferenceModel;
use Cobalt\DataModel\Directives\Filters\Filter;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Directives\Filters\Required;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Directives\Images\Thumbnail;
use Cobalt\DataModel\Directives\Label;
use Cobalt\DataModel\Directives\Types\Composite\MarkdownType;
use Cobalt\DataModel\Types\ArrayType;
use Cobalt\DataModel\Types\BlockType;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\DataModel\Types\ColorType;
use cobalt\DataModel\Types\Composite\UrlType;
use Cobalt\DataModel\Types\DateType;
use Cobalt\DataModel\Types\DataModel;
use Cobalt\DataModel\Types\ForeignDocumentType;
use Cobalt\DataModel\Types\GeoPointType;
use Cobalt\DataModel\Types\ImageType;
use Cobalt\DataModel\Types\StringType;
use Cobalt\Model\Types\ImageArrayType;
use Components\ServiceAreas\Models\Town;
use Override;

class ProjectModel extends DataModel {
    const NAP_REMINDER = <<<HTML
    <li>Make sure that if you use your company name that it's spelled and 
        formatted exactly the same way you normally do. Don't abbreviate or 
        shorten your company name. "<strong>%s</strong>"
    </li>
    HTML;
    const IMAGE_SEO = <<<HTML
        <details>
        <summary>SEO Suggestions</summary>
        <ol>
            <li>Make each image unique.</li>
            <li>Right click (or two-finger click, or long-press) to open
                the metadata dialog.
            </li>
            <li>In the metadata dialog make sure that you:
                <ol>
                    <li>Rename each image so it has relevant keywords.<br>
                        <small>e.g. <em>remodeled-kitchen-and-restored-countertop</em></small>
                    </li>
                    <li>Give each image descriptive text. This is absolutely
                        critical for having your project page appear in search
                        results.<br>
                        <small>e.g. <em>Kitchen remodel and countertop 
                            restoration in [town name] by {{app.app_name}}
                        </em></small>
                    </li>
                    %s
                </ol>
            </li>
        </ol>
        </details>
    HTML;

    const PROJECT_NAME_DESCRIPTION = <<<HTML
    <details>
        <summary>SEO Suggestions</summary>
        <ol>
            <li>Avoid using customer names.</li>
            <li>Don't be too poetic. Don't be too robotic.</li>
            <li>Use a min of 18 characters and a max of 60 characters</li>
            <li>Make sure you title this project with the words your 
                customer will be searching for when looking 
                for your services!<br>
                <small style="font-style: italic">e.g. Wallpaper Removal at a Colonial-style Northport Home</small>
            </li>
            <li>The name should be <em>(but doesn't have to be)</em>
                unique to your projects.</li>
            <li>When in doubt, follow this formula:<br>
                <small><code>[Core Service] + [Location Modifier] + [Customer Detail OR Unique Aspect of Project]</code></small>
            </li>
        </ol>
    </details>
    HTML;

    const BODY_COPY_DESCRIPTION = <<<HTML
        <details>
            <summary>SEO Suggestions</summary>
            <ol>
                <li><strong>Tell the story</strong>: Absolute minimum of 40 words
                    <ol>
                        <li>Don't just talk about the functional aspect of your project.
                            Describe the <em>quality of your work!</em></li>
                        <li>Explain the "how" and the "why."</li>
                        <li>Talk about your customers needs &amp; concerns! How you addressed them.</li>
                        <li>Start to finish: address the beginning, middle, and end!</li>
                    </ol>
                </li>
                <li><strong>Locality:</strong> ensure you mention the city, town, or neighborhood (if relevant).</li>
                <li><strong>Backlinks:</strong> Include links to relevant service pages.</li>
                <li><strong>Keep in mind:</strong> this project <strong>may be the first time
                    a potential customer interacts with your brand!</strong>
                    Make a strong first impression!
                </li>
                <li><strong>Bonus:</strong> Include a quote from the customer as a <code>Quote</code>
                    element.
                </li>
                %s
            </ol>
        </details>
    HTML;

    #[Min(10)]
    #[Max(100)]
    #[Required(true)]
    #[Label('Project name', self::PROJECT_NAME_DESCRIPTION)]
    readonly StringType $project_name;

    #[Filter('urlFilter')]
    #[Required(true)]
    readonly UrlType $url;
    function urlFilter(UrlType $url, mixed $toValidate) {
        if($toValidate) $toValidate = $this->project_name->toUrlFragment();
        if(!$toValidate) return $this->filterResult->addIssue($this, "URL cannot be empty.");
        return $toValidate;
    }

    #[Max(300)]
    #[Label('Teaser Text', <<<HTML
        Displayed on the project index and used as the link preview text on 
        social media, the teaser text should offer the visitor some enticing 
        flavor text. Give visitors a reason to want to click on this project! 
        Make sure you use relevant keywords! This should be short and sweet.
    HTML)]
    readonly MarkdownType $teaser;

    #[Label('Body Copy', self::BODY_COPY_DESCRIPTION, self::NAP_REMINDER)]
    readonly BlockType $body;

    readonly ArrayType $tags;

    #[Label('Cover image', self::IMAGE_SEO)]
    readonly CoverImageModel $cover_image;

    // readonly ImageArrayType $images; throw new Exception("Not implemented");

    readonly BooleanType $published;

    readonly DateType $date;

    readonly ColorType $header_color;

    #[Valid([
        '' => 'No darkening',
        'd100' => '10% darkening',
        'd200' => '20% darkening',
        'd300' => '30% darkening',
        'd400' => '40% darkening',
        'w100' => '10% lightening',
        'w200' => '20% lightening',
        'w300' => '30% lightening',
        'w400' => '40% lightening',
    ])]
    readonly StringType $darken_header;

    #[ReferenceModel(new Town)]
    readonly ForeignDocumentType $town;
    
    readonly GeoPointType $geo;

    
    #[Override]
    public function getDefaultField(): StringType {
        return $this->project_name;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "projects";
    }

}
