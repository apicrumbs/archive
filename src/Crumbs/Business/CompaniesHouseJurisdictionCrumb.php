<?php

namespace ApiCrumbs\Crumbs\Business;

use ApiCrumbs\Framework\Contracts\BaseCrumb;

/**
 * CompaniesHouseJurisdictionCrumb - Official UK Company Archive Access.
 * Requires a Companies House API Key (Username only, password blank).
 */
class CompaniesHouseJurisdictionCrumb extends BaseCrumb 
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

    public function getName(): string { return 'business/companieshousejurisdiction'; }
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
        
        // 🛡️ Fail-safe: Normalize the jurisdiction string
        $jurisdiction = strtolower((string) ($data['jurisdiction'] ?? 'unknown'));

        // 🏛️ Legal Seat Mapping
        $map = [
            'england-wales'    => ['label' => 'England and Wales', 'icon' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿'],
            'scotland'         => ['label' => 'Scotland', 'icon' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿'],
            'northern-ireland' => ['label' => 'Northern Ireland', 'icon' => '🇮🇪'],
            'united-kingdom'   => ['label' => 'United Kingdom (General)', 'icon' => '🇬🇧']
        ];

        $info = $map[$jurisdiction] ?? ['label' => strtoupper($jurisdiction), 'icon' => '🌐'];

        return [
            'company_number' => $id,
            'raw_key'        => $jurisdiction,
            'label'          => $info['label'],
            'icon'           => $info['icon'],
            'is_uk_native'   => in_array($jurisdiction, ['england-wales', 'scotland', 'northern-ireland']),
            'registry_ref'   => "UK_JURISDICTION_{$jurisdiction}"
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
            '### GET /business/profile/jurisdiction' => '',
            '**Legal Seat**' => strtoupper($data['label']),
            '**Registration Authority**' => '[Companies House UK]',
            '**Legal Framework**' => 'Statutory Governance of '. $data['label'],
            '**Registry Authority**' => $data['registry_ref'],
            '**JURISDICTION_ANCHOR**' => "This entity is governed by the laws of {$data['label']}. All legal service and corporate filings are anchored to this jurisdiction.",
        ];
        
        return $this->autoTransform($output, [
            'id'     => $this->id,
            'source' => 'Companies House API',
            'jurisdiction' => $data['raw_key']
        ]);
    }
}