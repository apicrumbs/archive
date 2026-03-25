<?php

namespace ApiCrumbs\Crumbs\Geography;

use ApiCrumbs\Framework\Contracts\BaseCrumb;

/**
 * PostcodeIoCrumb - The Geographic Anchor for UK Data
 * Converts string postcodes into lat/lng and administrative context.
 */
class PostcodesIoCrumb extends BaseCrumb
{
    public function getName(): string { return 'geography/postcodesio'; }

    public function getDependencies(): array { return []; }

    public function getVersion(): string { return '1.0.2'; }

    /**
     * Fetches raw postcode data from the open API.
     */
    public function fetchData(string $id, array $context = []): array
    {
        // Canonicalize the ID for the API
        $cleanId = str_replace(' ', '', strtoupper($id));
        $cleanId = str_replace('+', '', strtoupper($cleanId));

        $url = "https://api.postcodes.io/postcodes/" . urlencode($cleanId);
        
        try {
            $response = $this->safeFetch($url);
            return $response['result'] ?? [];
        } catch (\Exception $e) {
            // Silently return empty to prevent breaking the LLM build loop
            return [];
        }
    }

    /**
     * MetadataTransformer: Optimises for LLM spatial reasoning.
     * Injects system hints to help the AI "stitch" subsequent data.
     */
    public function transform(array $data): string
    {
        if (empty($data)) return "<!-- Source: {$this->getName()} | Status: NO_DATA -->\n";

        $output = "### {$this->getName()}: " . ($data['postcode'] ?? 'Unknown') . PHP_EOL;
        $output .= "<!-- Source: Postcodes.io | Refined by: by ApiCrumbs -->" . PHP_EOL;
        
        // Strict Mode: Lowercase keys for LLM consistency
        $output .= "- **COORDINATES**: " . ($data['latitude'] ?? 'N/A') . ", " . ($data['longitude'] ?? 'N/A') . PHP_EOL;
        $output .= "- **ADMIN_DISTRICT**: " . ($data['admin_district'] ?? 'N/A') . PHP_EOL;
        $output .= "- **PARISH**: " . ($data['parish'] ?? 'N/A') . PHP_EOL;
        $output .= "- **REGION**: " . ($data['region'] ?? 'N/A') . PHP_EOL;
        
        // Strategic RAG hint
        $output .= "> Info: Primary spatial reference. Use lat/lon for all distance-based reasoning." . PHP_EOL;

        return $output . "---" . PHP_EOL;
    }
}