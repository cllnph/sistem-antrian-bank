<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminControlCenter;
use App\Filament\Pages\BranchOperations;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class QueueOperationsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.queue-operations';

    protected int|string|array $columnSpan = 'full';

    public function content(Schema $schema): Schema
    {
        $code = auth()->user()?->branch?->code ?? '001';

        return $schema->components([
            Section::make('Akses Operasional')
                ->description('Pilih layanan sesuai tugas dan cabang Anda.')
                ->schema([
                    Actions::make([
                        Action::make('branch_operations')->label('Operasional Cabang')
                            ->icon(Heroicon::OutlinedBuildingOffice2)->url(BranchOperations::getUrl()),
                        Action::make('queue_list')->label('Daftar Antrean')->color('gray')
                            ->icon(Heroicon::OutlinedQueueList)->url(url('/admin/queues')),
                        Action::make('kiosk')->label('Buka Kiosk')->color('gray')
                            ->icon(Heroicon::OutlinedTicket)->url(url('/ambil-nomor/'.$code))->openUrlInNewTab(),
                        Action::make('display')->label('Buka Layar')->color('gray')
                            ->icon(Heroicon::OutlinedTv)->url(url('/display/'.$code))->openUrlInNewTab(),
                        Action::make('control_center')->label('Kontrol & Laporan')->color('gray')
                            ->icon(Heroicon::OutlinedChartBar)->url(AdminControlCenter::getUrl())
                            ->visible(fn () => auth()->user()?->can('view_control_center') ?? false),
                    ]),
                ]),
        ]);
    }
}
