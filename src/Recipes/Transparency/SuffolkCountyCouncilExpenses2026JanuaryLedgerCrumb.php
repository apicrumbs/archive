<?php

namespace ApiCrumbs\Recipes\Transparency;

use ApiCrumbs\Framework\Contracts\BaseRecipe;

class SuffolkCountyCouncilExpenses2026JanuaryLedgerCrumb extends BaseRecipe 
{
    public function getName(): string {
        
        return 'transparency/suffolkcountycouncilexpenses2026januaryledger';
    }

    public function getCrumbSchema(): array {
        // Tells the Press which Crumbs to load
        return ['transparency/suffolkcountycouncilexpenses2026januarysupplierfinancialpulse', 'transparency/suffolkcountycouncilexpenses2026januarysuppliertotalspend', 'transparency/suffolkcountycouncilexpenses2026januarysupplierledger',];
    }

    public function getStitchPattern(): string {
        return "Analyze the following expenses data to provide a 'Transparency' overview of the different suppliers for the government authority.";
    }
}