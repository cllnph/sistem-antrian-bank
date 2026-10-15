<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ControlCenterStaffWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    public ?string $selectedBranchId = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_control_center') ?? false;
    }

    public function table(Table $table): Table
    {
        $branchId = auth()->user()?->hasRole('admin')
            ? $this->selectedBranchId
            : (auth()->user()?->branch_id ?? 0);

        return $table
            ->heading(__('app.active_staff'))
            ->query(User::withRoleNames(['cs', 'teller'])->with(['roles', 'branch'])
                ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId)))
            ->poll('10s')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->wrap(),
                TextColumn::make('email')->label('Email')->wrap()->toggleable(),
                TextColumn::make('branch.name')->label('Kantor')->wrap(),
                TextColumn::make('roles.display_name')->label('Peran')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                Action::make('staff_mode')->label('Mode Petugas')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (User $record) => route('admin.impersonate', $record))
                    ->visible(fn (User $record) => auth()->user()?->hasRole('admin') && $record->is_active),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada akun petugas.');
    }
}
