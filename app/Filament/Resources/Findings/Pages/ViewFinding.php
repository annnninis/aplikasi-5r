<?php

namespace App\Filament\Resources\Findings\Pages;

use App\Filament\Resources\Findings\FindingActions;
use App\Filament\Resources\Findings\FindingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFinding extends ViewRecord
{
    protected static string $resource = FindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FindingActions::verify(),
            FindingActions::requestRevision(),
            FindingActions::assign(),
            FindingActions::reject(),
        ];
    }
}
