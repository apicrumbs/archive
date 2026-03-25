<?php

namespace ApiCrumbs\Crumbs\Finance;

use ApiCrumbs\Core\Contracts\BaseCrumb;

class CoinGeckoCrumb extends BaseCrumb 
{
    private string $apiKey;

    public function __construct(array $guzzleConfig = [])
    {
        $this->apiKey = '';
        if (isset($guzzleConfig['COINGECKO_API_KEY'])) {
            $this->apiKey = $guzzleConfig['COINGECKO_API_KEY'];
            unset($guzzleConfig['COINGECKO_API_KEY']);
        }

        parent::__construct();
    }

    public function getName(): string { return 'finance/coingecko'; }
    public function getVersion(): string { return '1.0.2'; }
    public function getDependencies(): array { return ['']; }

    /**
     * Fetches market data using the mandatory API Key.
     * $id = 'bitcoin', 'ethereum', etc.
     */
    public function fetchData(string $id, array $context = []): array 
    {
        // 1. Get key from context or .env        
        if (!$this->apiKey) {
            $this->apiKey = $context['COINGECKO_API_KEY'] ?? getenv('COINGECKO_API_KEY');
        }
        
        // 2. Define the correct Demo API Root
        $url = "https://api.coingecko.com/api/v3/coins/{$id}?localization=false&tickers=false";

        // 3. safeFetch with Headers
        return $this->safeFetch($url, [
            'headers' => [
                'accept' => 'application/json',
                'x-cg-demo-api-key' => $this->apiKey // Use 'x-cg-pro-api-key' if on a paid plan
            ]
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

    public function transform(array $data): string 
    {
        if (!isset($data['market_data'])) {
            return "### {$this->getName()}: Data currently unavailable.";
        }

        $mkt = $data['market_data'];
        $price = number_format($mkt['current_price']['usd'], 2);
        $change = number_format($mkt['price_change_percentage_24h'], 2);
        $trend = $mkt['price_change_percentage_24h'] >= 0 ? 'UP' : 'DOWN';

        return "### {$this->getName()}: " . strtoupper($data['name']) . " ({$data['symbol']})\n" .
                "<!-- Source: CoinGecko | Refined by: ApiCrumbs -->\n" .
               "- **Price** \${$price} \n" .
               "- **24h Change** {$trend} {$change}% \n" .
               "- **Market Cap** \${$mkt['market_cap']['usd']} \n".
               "> Info: Real-time cryptocurrency market data.\n";
    
    }
}