<?php

namespace Components\StructuredData\Model;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\StringType;
use Components\StructuredData\Controllers\BusinessDetails;
use Components\StructuredData\Traits\LDJson;
use Exception;
use Override;

class BusinessInfo extends Model {
    use LDJson;
    #[Override]
    public function defineSchema(array $schema = []): array {
        return [
            '_ident' => [
                new StringType(),
                'private' => true
            ],
            '@type' => [
                new EnumType,
                'label' => 'Business Type',
                'description' => 'This hints key SEO indicators to search engines.',
                'valid' => [
                    'AnimalShelter' => 'Animal Shelter',
                    'ArchiveOrganization' => 'Archive Organization',
                    'AutomotiveBusiness' => 'Automotive Business',
                    'ChildCare' => 'Child Care',
                    'Dentist' => 'Dentist',
                    'DryCleaningOrLaundry' => 'Dry Cleaning Or Laundry',
                    'EmergencyService' => 'Emergency Service',
                    'EmploymentAgency' => 'Employment Agency',
                    'EntertainmentBusiness' => 'Entertainment Business',
                    'FinancialService' => 'Financial Service',
                    'FoodEstablishment' => 'Food Establishment',
                    'GovernmentOffice' => 'Government Office',
                    'HealthAndBeautyBusiness' => 'Health and Beauty Business',
                    'HomeAndConstructionBusiness' => 'Home and Construction Business',
                    'InternetCafe' => 'Internet Cafe',
                    'LegalService' => 'Legal Service',
                    'Library' => 'Library',
                    'LodgingBusiness' => 'Lodging Business',
                    'MedicalBusiness' => 'Medical Business',
                    'ProfessionalService' => 'Professional Service',
                    'RadioStation' => 'Radio Station',
                    'RealEstateAgent' => 'Real Estate Agent',
                    'RecyclingCenter' => 'Recycling Center',
                    'SelfStorage' => 'Self Storage',
                    'ShoppingCenter' => 'Shopping Center',
                    'SportsActivityLocation' => 'Sports Activity Location',
                    'Store' => 'Store',
                    'TelevisionStation' => 'Television Station',
                    'TouristInformationCenter' => 'Tourist Information Center',
                    'TravelAgency' => 'Travel Agency',
                ]
            ],
            ...include __DIR__ . "/../Schemas/local_business.php"
        ];
    }

    #[Override]
    public function defineController(): ModelController {
        // return new BusinessDetails();
        throw new Exception("Not implemented");
    }

    #[Override]
    public static function __getVersion(): string {
        return "1.0";
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "SchemaOrgItems";
    }

}