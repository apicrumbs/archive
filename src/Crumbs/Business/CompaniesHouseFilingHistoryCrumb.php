<?php

namespace ApiCrumbs\Crumbs\Pro;

use ApiCrumbs\Core\Contracts\BaseCrumb;

class CompaniesHouseFilingHistoryCrumb extends BaseCrumb 
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

    public function getName(): string { return "pro/companieshousefilinghistory"; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDependencies(): array { return ['']; }

    public function fetchData(string $id, array $context = []): array 
    {
        $this->id = $id;
        // 1. Get key from context or .env
        if (!$this->apiKey) {
            $this->apiKey = $context['COMPANIES_HOUSE_API_KEY'] ?? getenv('COMPANIES_HOUSE_API_KEY');
        }

        // Request filing history filtered by 'accounts' category
        $url = "https://api.company-information.service.gov.uk/company/{$id}/filing-history";

        // Companies House uses Basic Auth: API Key as username, empty password
        $options = [
            'auth' => [$this->apiKey, ''],
            'query' => ['category' => 'accounts', 'items_per_page' => 5]
        ];

        $response = $this->safeFetch($url, $options);

        $items = $response['items'] ?? [];
        $balanceSheet = null;

        // Find the most recent 'accounts' type filing that has document metadata
        foreach ($items as $item) {
            if (isset($item['links']['document_metadata'])) {
                // Construct the direct Content URL for the PDF
                // Pattern: metadata_url + /content
                $balanceSheet = [
                    'date' => $item['date'],
                    'type' => $item['type'],
                    'pdf_url' => $item['links']['document_metadata'] . '/content'
                ];
                break; // Found the most recent
            }
        }

        if ($balanceSheet) {
            $context['latest_balance_sheet_url'] = $balanceSheet['pdf_url'];
        }

        return $balanceSheet ?? [];
    }

    public function transform(array $data): string 
    {
        // The "Meat" of the context
        $points = [
            'Filed Date'     => $data['date'],
            'Filing Type'    => $data['type'],
            'Download URL'   => $data['pdf_url']
        ];

        return $this->autoTransform($points, [
            'id'     => $this->id,
            'source' => 'Companies House API'
        ]);
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
}