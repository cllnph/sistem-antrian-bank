<?php

namespace App\Filament\Resources\PrintAgentResource\Pages;

use App\Filament\Pages\BranchOperations;
use App\Filament\Resources\PrintAgentResource;
use Filament\Resources\Pages\Page;

class SelectPrintBranch extends Page
{
    protected static string $resource = PrintAgentResource::class;

    public function mount(): void
    {
        abort_unless(PrintAgentResource::canViewAny(), 403);
        $this->redirect(BranchOperations::getUrl());
    }
}
