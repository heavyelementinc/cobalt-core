<?php

namespace Cobalt\Components\ServiceAreas\Models;

use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Directives\Label;
use Cobalt\DataModel\Directives\Lookup;
use Cobalt\DataModel\Directives\Types\Composite\MarkdownType;
use Cobalt\DataModel\Types\BooleanType;
use cobalt\DataModel\Types\Composite\UrlType;
use Cobalt\DataModel\Types\DataModel;
use Cobalt\DataModel\Types\EnumType;
use Cobalt\DataModel\Types\GeoPointType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\DataModel\Types\StringType;
use Cobalt\Model\Types\ImageType;
use Components\ServiceAreas\Models\ServiceAreaCounty;
use Override;

class ServiceArea extends DataModel {
    readonly StringType $town_name;
    readonly UrlType $href;
    #[Valid([
        'city' => 'City',
        'town' => 'Town',
        'plantation' => 'Plantation',
        'township' => 'Township'
    ])]
    readonly EnumType $type;
    readonly BooleanType $seat;

    readonly ServiceAreaCounty $county;

    readonly NumberType $pop;
    readonly NumberType $mi2;
    readonly NumberType $km2;
    readonly NumberType $inc;
    readonly MarkdownType $blurb;
    readonly ImageType $img;
    readonly GeoPointType $geo;
    
    #[Label('URL Pathname')]
    readonly StringType $slug;
    
    #[Label('Nearby Projects', 'Set the radius for nearby projects for this town page')]
    readonly NumberType $nearby;
    readonly BooleanType $include;

    #[Valid('stateValid')]
    readonly StringType $state;


    #[Override]
    public function getDefaultField(): StringType {
        return $this->town_name;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "serviceAreas";
    }


    function stateValid() {
        return [
            'AL'=>'Alabama',
            'AK'=>'Alaska',
            'AS'=>'American Samoa',
            'AZ'=>'Arizona',
            'AR'=>'Arkansas',
            'CA'=>'California',
            'CO'=>'Colorado',
            'CT'=>'Connecticut',
            'DE'=>'Delaware',
            'DC'=>'District of Columbia',
            'FM'=>'Federated States of Micronesia',
            'FL'=>'Florida',
            'GA'=>'Georgia',
            'GU'=>'Guam Gu',
            'HI'=>'Hawaii',
            'ID'=>'Idaho',
            'IL'=>'Illinois',
            'IN'=>'Indiana',
            'IA'=>'Iowa',
            'KS'=>'Kansas',
            'KY'=>'Kentucky',
            'LA'=>'Louisiana',
            'ME'=>'Maine',
            'MH'=>'Marshall Islands',
            'MD'=>'Maryland',
            'MA'=>'Massachusetts',
            'MI'=>'Michigan',
            'MN'=>'Minnesota',
            'MS'=>'Mississippi',
            'MO'=>'Missouri',
            'MT'=>'Montana',
            'NE'=>'Nebraska',
            'NV'=>'Nevada',
            'NH'=>'New Hampshire',
            'NJ'=>'New Jersey',
            'NM'=>'New Mexico',
            'NY'=>'New York',
            'NC'=>'North Carolina',
            'ND'=>'North Dakota',
            'MP'=>'Northern Mariana Islands',
            'OH'=>'Ohio',
            'OK'=>'Oklahoma',
            'OR'=>'Oregon',
            'PW'=>'Palau',
            'PA'=>'Pennsylvania',
            'PR'=>'Puerto Rico',
            'RI'=>'Rhode Island',
            'SC'=>'South Carolina',
            'SD'=>'South Dakota',
            'TN'=>'Tennessee',
            'TX'=>'Texas',
            'UT'=>'Utah',
            'VT'=>'Vermont',
            'VI'=>'Virgin islands',
            'VA'=>'Virginia',
            'WA'=>'Washington',
            'WV'=>'West Virginia',
            'WI'=>'Wisconsin',
            'WY'=>'Wyoming',
            'AE'=>'Armed Forces Africa \ Canada \ Europe \ Middle East',
            'AA'=>'Armed Forces America (except Canada)',
            'AP'=>'Armed Forces Pacific'
        ];
    }
}
