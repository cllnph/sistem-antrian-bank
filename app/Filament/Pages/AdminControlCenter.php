<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/** Preserve bookmarks; all operational summaries live on the dashboard. */
class AdminControlCenter extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return BranchOperations::canAccess();
    }

    public function mount(): void
    {
        $this->redirect(BranchOperations::getUrl());
    }
}
