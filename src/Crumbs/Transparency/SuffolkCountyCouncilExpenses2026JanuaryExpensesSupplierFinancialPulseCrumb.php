<?php

namespace ApiCrumbs\Crumbs\Transparency;

use ApiCrumbs\Framework\Contracts\CsvStreamCrumb;

/**
 * SuffolkCountyCouncilExpenses2026JanuaryExpensesSupplierFinancialPulseCrumb - The Accounting Engine
 * Aggregates monthly spend and volume for a specific supplier.
 */
class SuffolkCountyCouncilExpenses2026JanuaryExpensesSupplierFinancialPulseCrumb extends CsvStreamCrumb
{
    protected string $defaultReferenceId = 'TOTAL_PULSE_ENGINE_v1';
    public function getName(): string { return 'transparency/suffolkcountycouncilexpenses2026januarysupplierfinancialpulse'; }
    public function getVersion(): string { return '1.0.2'; }

    public function getSourceUrl(): string 
    {
        return 'https://www.suffolk.gov.uk/asset-library/scc-spend-jan-2025.csv';
    }

    public function getMapping(): array 
    {
        return $this->masterContext['mapping_supplier_total_spend'] ?? [
            'supplier' => 'Supplier Name',
            'amount'   => 'Sub Amount'
        ];
    }

    /**
     * Fetch Logic: Aggregates total spend for a specific Supplier ID
     */
    /**
     * Aggregation Logic: Sums all payments for a specific Supplier ID
     */
    public function fetchData(string $id, array $context = []): array
    {
        $context['referenceId'] = $id;
        $this->masterContext = $context;
        $total = 0.0;
        $count = 0;

        // stream() uses Generator for zero-memory footprint on XAMPP
        foreach ($this->stream($this->getSourceUrl()) as $row) {
            // Case-insensitive match for the supplier name string
            if (stripos($row['supplier'], $id) !== false) {
                // Clean currency: Remove symbols and commas
                $amt = (float) str_replace(['£', ','], '', $row['amount']);
                $total += $amt;
                $count++;
            }
        }

        return [
            'total_spend'   => $total,
            'invoice_count' => $count,
            'average_value' => ($count > 0) ? ($total / $count) : 0
        ];
    }

    /**
     * Transforms raw math into a high-signal "Pulse" block
     */
    public function transform(array $data): string
    {
        if (empty($data) || $data['invoice_count'] === 0) {
            return "❌ NO_FINANCIAL_ACTIVITY_DETECTED";
        }

        $formattedTotal = number_format($data['total_spend'], 2);
        $formattedAvg   = number_format($data['average_value'], 2);

        $output = [];
        $output["### GET /finance/supplier/financial-pulse"] = "";
        $output["**Total Monthly Spend**"] = "£{$formattedTotal}";
        $output["**Invoice Frequency**"] = "{$data['invoice_count']} payments processed";
        $output["**Mean Transaction Value**"] = "£{$formattedAvg}";

        return $this->autoTransform($output, [
            'id' => $this->getReferenceId(),
            'type' => 'Accounting_Signal',
            'source' => $this->getSourceUrl(),
            'original_source_url' => $this->masterContext['original_source_url'],
        ]);
    }
        
    public function getReferenceId(): string
    {
        return $this->masterContext['referenceId'] ?? $this->defaultReferenceId;
    }
}