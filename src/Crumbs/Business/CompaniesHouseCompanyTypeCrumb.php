<?php

namespace ApiCrumbs\Crumbs\Business;

use ApiCrumbs\Framework\Contracts\BaseCrumb;

/**
 * CompaniesHouseCompanyTypeCrumb - Official UK Company Archive Access.
 * Requires a Companies House API Key (Username only, password blank).
 */
class CompaniesHouseCompanyTypeCrumb extends BaseCrumb 
{
    private string $apiKey;
    private string $id;

    public function __construct(array $guzzleConfig = [])
    {
        $this->apiKey = '';
        if (isset($guzzleConfig['COMPANIES_HOUSE_API_KEY'])) {
            $this->apiKey = $guzzleConfig['COMPANIES_HOUSE_API_KEY'];
            unset($guzzleConfig['COMPANIES_HOUSE_API_KEY']);
        }

        parent::__construct();
    }

    public function getName(): string { return 'business/companieshousecompanytype'; }
    public function getVersion(): string { return '1.0.1'; }
    public function getDependencies(): array { return ['']; }

    /**
     * @param string $id
     * @param array $context Raw data passed from the master Companies House Profile pull
     */
    public function fetchData(string $id, array $context = []): array 
    {
        $this->id = $id;
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
        $data = $this->safeFetch($url, $options);
        
        // 🛡️ Fail-safe: Normalize the type string
        $type = strtolower((string) ($data['type'] ?? 'unknown'));

        // 🏛️ Governance Mapping: Industrial Categorisation
        $map = [
            'ltd'                              => ['label' => 'Private Limited Company', 'cat' => 'PRIVATE'],
            'plc'                              => ['label' => 'Public Limited Company', 'cat' => 'PUBLIC'],
            'llp'                              => ['label' => 'Limited Liability Partnership', 'cat' => 'PARTNERSHIP'],
            'private-limited-guarant-nsc'      => ['label' => 'Private Limited by Guarantee', 'cat' => 'NON_PROFIT'],
            'industrial-and-provident-society' => ['label' => 'Co-operative / IPS', 'cat' => 'COMMUNITY'],
            'uk-establishment'                 => ['label' => 'UK Establishment (Branch)', 'cat' => 'SATELLITE'],
            'royal-charter'                    => ['label' => 'Royal Charter Entity', 'cat' => 'CROWN'],
            'charity-incorporated-organisation'=> ['label' => 'CIO (Charity)', 'cat' => 'NON_PROFIT']
        ];

        $info = $map[$type] ?? ['label' => strtoupper($type), 'cat' => 'OTHER'];

        return [
            'company_number' => $id,
            'raw_type'       => $type,
            'label'          => $info['label'],
            'category'       => $info['cat'],
            'is_high_audit'  => in_array($type, ['plc', 'llp', 'uk-establishment']),
            'registry_ref'   => "UK_TYPE_CODE_{$type}"
        ];     
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
        $output = [
            '### GET /business/profile/structure-audit' => '',
            '**Legal Framework**' => str_replace('_', ' ', $data['category']),
            '**Entity Constitution**' => $data['label'],
            '**Filing Requirements**' =>  ($data['is_high_audit'] ? "ENHANCED_AUDIT_STANDARDS" : "STANDARD_STATUTORY") ,
            '**Registry Authority**' => $data['registry_ref'],          
        ];

        if ($data['category'] === 'PUBLIC') {
            $output['**MARKET_WEIGHT:**'] .= "This is a Public Limited Company. It is subject to higher transparency standards and represents a significant 'Whale' in the economic ecosystem.";
        }
        
        return $this->autoTransform($output, [
            'id'     => $this->id,
            'source' => 'Companies House API',
            'category' => $data['category'],
            'is_plc'   => ($data['raw_type'] === 'plc')
        ]);
    }
}