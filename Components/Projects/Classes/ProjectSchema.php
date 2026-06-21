<?php

namespace Components\Projects\Classes;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Validation\Exceptions\ValidationIssue;
use Validation\Normalize;

/**
 * @deprecated
 * @package Components\Projects\Classes
 */
class ProjectSchema extends Normalize {

    public function __get_schema(): array {
        return [
            'order' => [], // int
            'name' => [], // string
            'url' => [
                'set' => fn ($val) => ($val) ? $this->url_fragment_sanitize($val) : $this->url_fragment_sanitize($this->name),
            ],
            'blurb' => [],
            'tags' =>  [
                'valid' => [
                    'advocacy' => 'Design Advocacy',
                    'renders'  => '3D Rendering',
                    'room'     => 'One Room',
                    'home'     => 'Whole Home',
                    'shop'     => 'Shop'
                ]
            ],
            'primary' => [], // int
            'order' => [
                'set' => fn($val) => (is_int($val)) ? $val : throw new ValidationIssue("Must be a number")
            ],
            'images' => [
                // 'get' => fn ($val) => $this->each("\\Projects\\ImageSchema", $val),
                // 'set' => fn ($val) => $this->subdocument($val, [
                //     'image' => [],
                //     'thumb' => [],
                //     'meta' => [],
                // ])
            ],
            // 'primary_image' => [
            //     'get' => fn () => $this->images[$this->primary]['image'],
            //     'set' => false
            // ],
            // 'gallery' => [
            //     'get' => 'getGallery',
            //     'set' => false,
            // ],
            'published' => [
                'set' => function ($val) {
                    if(!$this->image_count) throw new ValidationIssue("You need to have uploaded at least one (1) image to this project and set it as the primary image. (Right click the image and choose 'Set as default')");
                    return $this->boolean_helper($val);
                }
            ],
            'shop' => [
                'set' => function ($val) {
                    return $this->boolean_helper($val);
                }
            ],
            'date' => [
                'get' => function ($val) {
                    /** @var UTCDateTime */
                    if($val) return $val->toDateTime();
                    $date = new DateTime();
                    $date->setTimestamp($this->_id?->getTimestamp() ?? time());
                    return $date;
                },
                'set' => function ($val) {
                    $val = new UTCDateTime(strtotime($val) * 1000);
                    return $val;
                },
                'attr' => function ($val) {
                    return $val->format("c");
                },
                'display' => function ($val) {
                    $format = "l, F jS Y";
                    return $val->format($format);
                }
            ],
            'header_color' => [
                'valid' => [
                    '#000' => "Black",
                    '#fff' => "White",
                ]
            ],
            'darken_header' => [
                'valid' => [
                    '' => 'No darkening',
                    'd100' => '10% darkening',
                    'd200' => '20% darkening',
                    'd300' => '30% darkening',
                    'd400' => '40% darkening',
                    'w100' => '10% lightening',
                    'w200' => '20% lightening',
                    'w300' => '30% lightening',
                    'w400' => '40% lightening',
                ]
            ]
        ];
    }

    function getGallery() {
        $string = '';//'<a href="#1"><img src="/res/img/work/Bishop Great Room.jpeg"></a>';
        foreach($this->images as $index => $image){
            $string .= "<a href='#$index'><img src='$image[thumb]' data-main='$image[image]'></a>";
        }

        return $string;
    }
}