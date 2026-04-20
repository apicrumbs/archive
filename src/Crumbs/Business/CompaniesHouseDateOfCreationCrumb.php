<?php

namespace ApiCrumbs\Crumbs\Business;

use ApiCrumbs\Framework\Contracts\BaseCrumb;

/**
 * CompaniesHouseDateOfCreationCrumb - Official UK Company Archive Access.
 * Requires a Companies House API Key (Username only, password blank).
 */
class CompaniesHouseDateOfCreationCrumb extends BaseCrumb 
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

    public function getName(): string { return 'business/companieshousedateofcreation'; }
    public function getVersion(): string { return '1.0.4'; }
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
        $data = $this->safeFetch($url, $options);

        // Data usually passed from the master Company Profile pull
        $creationDate = $data['date_of_creation'] ?? null;

        if (!$creationDate) {
            return ['status' => 'DATE_MISSING'];
        }

        $date = new \DateTime($creationDate);
        
        return [
            'raw_date' => $creationDate,
            'formatted' => $date->format('d M Y'),
            'year' => $date->format('Y'),
            'month' => $date->format('m'),
            'date_of_creation' => $data['date_of_creation'],
            'company_status' => $data['company_status'],
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

    protected function getPreciseAge(string $date ): string
    {
        $origin = new \DateTimeImmutable( $date );
        $now = new \DateTimeImmutable(date("Y-m-d")); // Current date
        
        $interval = $origin->diff( $now );

        return "{$interval->y} Years, {$interval->m} Months, {$interval->d} Days";
    }

    protected function getLongevityTier(string $date, string $status ): string
    {
        $longevity_tier = '';

        $year = $this->getYear( $date );

        if( $year > 10 )
        {
            $longevity_tier = 'ESTABLISHED';
        }

        if( $year > 3 && $year <= 10 )
        {
            $longevity_tier = 'STABLE';
        }

        if( $year >= 1 && $year <= 3 )
        {
            $longevity_tier = 'EMERGING';
        }

        if( $year < 1 )
        {
            $longevity_tier = 'PHOENIX_RISK';
        }       

        if( strtoupper( $status ) == 'DISSOLVED' )
        {
            $longevity_tier = 'DISSOLVED';
        }

        return $longevity_tier;
    }

    protected function getYear( $date )
    {
        $date = \DateTime::createFromFormat( "Y-m-d", $date );
        return $date->format("Y");
    }


    public function transform(array $data): string 
    {
        if (isset($data['status'])) return "### 🧩 GET /business/profile/creation-date\n⚠️ **DATE_NOT_RECORDED**";

        // The "Meat" of the context
        $output = [
            '### GET /business/profile/creation-date' => '',
            '**Incorporation Date**' => $data['formatted'],
            '**Registration Year**' => $data['year'],
            '**Precise Trading Age**' => $this->getPreciseAge($data['date_of_creation']),
            '**Longevity Tier**' => $this->getLongevityTier($data['date_of_creation'], $data['company_status']),
            '**Fiscal Genesis**' => 'Established in Month '. $data['month'],
        ];
        
        return $this->autoTransform($output, [
            'id'     => $this->id,
            'source' => 'Companies House API',
            'year' => $data['year']
        ]);
    }
}