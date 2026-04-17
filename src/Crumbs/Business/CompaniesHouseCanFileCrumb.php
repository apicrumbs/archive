<?php

namespace ApiCrumbs\Crumbs\Business;

use ApiCrumbs\Framework\Contracts\BaseCrumb;

/**
 * CompaniesHouseCanFileCrumb - Official UK Company Archive Access.
 * Requires a Companies House API Key (Username only, password blank).
 */
class CompaniesHouseCanFileCrumb extends BaseCrumb 
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

    public function getName(): string { return 'business/companieshousecanfile'; }
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

        // 🛡️ Fail-safe: Ensure we treat the presence of 'can_file' as a strict boolean.
        // Companies House usually returns true, but if missing or false, we flag it.
        $canFile = isset($data['can_file']) ? (bool) $data['can_file'] : true;

        return [
            'company_number' => $id,
            'can_file_electronically' => $canFile,
            'access_status' => ($canFile) ? 'OPEN_GATEWAY' : 'LOCKED_GATEWAY',
            'is_restricted' => !$canFile
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
        $label = $data['can_file_electronically'] ? 'SYSTEMS_ACTIVE' : 'FILING_RESTRICTED';

        // The "Meat" of the context
        $output = [
            '### GET /business/profile/filing-access' => '',
            '**Gateway Access**' => $label,
            '**Online Filing Enabled**' => ($data['can_file_electronically'] ? "YES" : "NO"),
            '**Registry Protocol**' => 'Statutory WebFiling Access',
        ];
        
        return $this->autoTransform($output, [
            'id'     => $this->id,
            'source' => 'Companies House API',
            'enabled' => $data['can_file_electronically']
        ]);
    }
}