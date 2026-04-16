<?php

namespace ApiCrumbs\Crumbs\Business;

use ApiCrumbs\Framework\Contracts\BaseCrumb;

/**
 * CompaniesHouseStatusCrumb - Official UK Company Archive Access.
 * Requires a Companies House API Key (Username only, password blank).
 */
class CompaniesHouseStatusCrumb extends BaseCrumb 
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

    public function getName(): string { return 'business/companieshousestatus'; }
    public function getVersion(): string { return '1.0.1'; }
    public function getDependencies(): array { return ['']; }

    /**
     * Fetches real-time company profiles.
     * Endpoint: https://api.company-information.service.gov.uk/company/{number}
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
        $data['raw_status'] = $data['company_status'] ?? 'unknown';
        $data['registry_ref']  = "UK_CO_HOUSE_" . $this->id;
        $data['is_active'] = $data['raw_status'] == 'active' ? true : false;
        $icon = $icons[$data['raw_status']] ?? '📍';
        $label = strtoupper(str_replace('-', '_', $data['raw_status']));
        
        // The "Meat" of the context
        $output = [
            '### GET /business/profile/status' => '',
            '**Operational State**' => $label,
            '**Legal Standing**' => ($data['is_active'] ? "ACTIVE_TRADING" : "NON_ACTIVE"),
            '**Registry Authority**' => $data['registry_ref'],
            
        ];

        return $this->autoTransform($output, [
            'id'     => $this->id,
            'source' => 'Companies House API'
        ]);
    }
}