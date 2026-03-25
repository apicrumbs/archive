<?php

namespace ApiCrumbs\Crumbs\Business;

use ApiCrumbs\Core\Contracts\BaseCrumb;

/**
 * CompaniesHouseCrumb - Official UK Company Archive Access.
 * Requires a Companies House API Key (Username only, password blank).
 */
class CompaniesHouseCrumb extends BaseCrumb 
{
    private string $apiKey;

    public function __construct(array $guzzleConfig = [])
    {
        $this->apiKey = '';
        if (isset($guzzleConfig['COMPANIES_HOUSE_API_KEY'])) {
            $this->apiKey = $guzzleConfig['COMPANIES_HOUSE_API_KEY'];
            unset($guzzleConfig['COMPANIES_HOUSE_API_KEY']);
        }

        parent::__construct();
    }

    public function getName(): string { return 'business/companieshouse'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDependencies(): array { return ['']; }

    /**
     * Fetches real-time company profiles.
     * Endpoint: https://api.company-information.service.gov.uk/company/{number}
     */
    public function fetchData(string $id, array $context = []): array 
    {
        // 1. Get key from context or .env
        if (!$this->apiKey) {
            $this->apiKey = $context['COMPANIES_HOUSE_API_KEY'] ?? getenv('COMPANIES_HOUSE_API_KEY');
        }
                
        // 2. Define the correct API Root
        $url = "https://api.company-information.service.gov.uk/company/{$id}";
      
       // Companies House uses Basic Auth: API Key as username, empty password
        $options = [
            'auth' => [$this->apiKey, '']
        ];

        // 3. safeFetch with Headers
        return $this->safeFetch($url, $options);
    }

    /**
     * Default Batch: The "Safe Loop"
     */
    public function fetchBatch(array $ids, array $context = []): array 
    {
        $results = [];
        foreach ($ids as $id) {
            $results[$id] = $this->fetchData($id, $context[$id] ?? []);
        }
        return $results;
    }

    public function transform(array $data): string 
    {
        // The "Meat" of the context
        $points = [
            'Status'        => strtoupper($data['company_status'] ?? 'unknown'),
            'Type'          => $data['type'],
            'Incorporated'  => $data['date_of_creation'],
            'Address'       => $data['registered_office_address'] ?? [],
            'SIC Codes'     => implode(', ', $data['sic_codes'] ?? []),
        ];

        return $this->autoTransform($points, [
            'id'     => $this->id,
            'source' => 'Companies House API'
        ]);

        /*if (empty($data)) return "### 🏢 COMPANY_DATA: Not Found\n";

        $status = strtoupper($data['company_status'] ?? 'unknown');
        $address = $data['registered_office_address'] ?? [];
        $sicCodesList = implode(', ', $data['sic_codes'] ?? []);

        return <<<EOD
### COMPANY_PROFILE: {$data['company_name']} ({$data['company_number']})
<!-- Source: Companies House | Real-time company data -->        
- **Status**: {$status}
- **Type**: {$data['type']}
- **Incorporated**: {$data['date_of_creation']}
- **Address**: {$address['address_line_1']}, {$address['postal_code']}
- **SIC Codes**: {$sicCodesList}
> Info: Company data is matched to the provided company number.
EOD;*/
    }
}