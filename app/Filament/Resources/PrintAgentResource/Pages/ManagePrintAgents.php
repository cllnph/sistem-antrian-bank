<?php

namespace App\Filament\Resources\PrintAgentResource\Pages;

use App\Filament\Pages\BranchDetail;
use App\Filament\Resources\KioskDeviceResource;
use App\Filament\Resources\PrintAgentResource;
use App\Models\Branch;
use App\Services\Printing\AgentPackageBuilder;
use App\Services\Printing\KioskPrintRouting;
use App\Services\Printing\NewPcInstaller;
use App\Services\Printing\ReceiptPaper;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class ManagePrintAgents extends ManageRecords
{
    protected static string $resource = PrintAgentResource::class;

    #[Locked]
    public int $branchId;

    public function mount(int|string|null $branch = null): void
    {
        abort_unless(PrintAgentResource::canViewAny(), 403);
        $this->branchId = Branch::findOrFail($branch)->id;
        parent::mount();
    }

    public function getBranch(): Branch
    {
        abort_unless(PrintAgentResource::canViewAny(), 403);

        return Branch::findOrFail($this->branchId);
    }

    public function getTitle(): string
    {
        return 'Cetak & Printer — '.$this->getBranch()->name;
    }

    public function getSubheading(): ?string
    {
        $selection = app(KioskPrintRouting::class)->selection($this->getBranch(), request());

        return 'Tujuan browser ini: '.$selection['label'].' · '.(ReceiptPaper::LABELS[$selection['paper_profile']] ?? '80 mm').'. '.($selection['notice'] ?? 'Pengaturan cetak terpisah untuk setiap cabang dan PC.');
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query) {
            $query->where('branch_id', $this->getBranch()->id);
        })->emptyStateHeading('Belum ada PC cetak di cabang ini')
            ->emptyStateDescription('Kiosk tetap dapat mencetak melalui dialog printer PC. Gunakan Pasang PC baru untuk cetak otomatis.');
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);
        $schema->getComponent('branch_id')->options([$this->branchId => $this->getBranch()->name])
            ->default($this->branchId)->disabled()->dehydrated();

        return $schema;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->label('Kembali ke cabang')->color('gray')->url(fn () => BranchDetail::getUrl(['branch' => $this->branchId])),
            Action::make('openKiosk')->label('Buka Kiosk')->url(fn () => route('queue.take-number', ['branch' => $this->getBranch()->code]))->openUrlInNewTab(),
            Action::make('installNewPc')->label('Pasang PC baru')->authorize(fn () => PrintAgentResource::canViewAny())
                ->disabled(fn () => ! $this->getBranch()->is_active)->requiresConfirmation()
                ->modalDescription(fn () => 'Pasang PC untuk '.$this->getBranch()->name.'. Ekstrak dan jalankan installer pada PC printer. Nama PC dan printer diambil dari Windows. Setelah siap, cetak otomatis aktif. Gunakan Buka Kiosk dari installer sekali untuk memverifikasi pasangan browser pada PC tersebut.')
                ->modalSubmitActionLabel('Unduh Installer')
                ->schema([Hidden::make('proof')->required()])
                ->fillForm(fn () => ['proof' => Crypt::encryptString(json_encode(['branch_id' => $this->branchId, 'actor_id' => auth()->id(), 'intent' => (string) Str::uuid()]))])
                ->action(function (array $data) {
                    abort_unless(PrintAgentResource::canViewAny(), 403);
                    $proof = json_decode(Crypt::decryptString($data['proof']), true, 8, JSON_THROW_ON_ERROR);
                    abort_unless((int) $proof['branch_id'] === $this->branchId && (int) $proof['actor_id'] === (int) auth()->id(), 403);
                    try {
                        $path = app(NewPcInstaller::class)->build($this->getBranch(), auth()->user(), $proof['intent'], url('/'));
                    } catch (\RuntimeException $error) {
                        report($error);
                        Notification::make()->title('Installer belum dapat dibuat. Periksa komponen server dan coba lagi.')->danger()->send();

                        return null;
                    }

                    return response()->download($path, 'Penghubung-PC-Baru-v'.app(AgentPackageBuilder::class)->windowsInstallerVersion().'.zip', ['Cache-Control' => 'private, no-store']);
                }),
            Action::make('localPrinting')->label('Cetak lokal PC ini')->color('gray')
                ->disabled(fn () => ! $this->getBranch()->is_active)
                ->schema([
                    Select::make('paper_profile')->label('Kertas')->options(ReceiptPaper::LABELS)->required()
                        ->default(fn () => app(KioskPrintRouting::class)->selection($this->getBranch(), request())['paper_profile']),
                ])->modalDescription('Memakai dialog cetak pada browser ini untuk cabang ini. Jalankan dari PC kiosk yang ingin diatur. Pilihan ini tidak mengubah tujuan browser atau PC lain. Untuk mode otomatis, pasangkan browser melalui Buka Kiosk dari installer pada PC printer.')
                ->modalSubmitActionLabel('Gunakan cetak lokal')
                ->action(function (array $data) {
                    $branch = $this->getBranch();
                    abort_unless($branch->is_active, 422);
                    $routing = app(KioskPrintRouting::class);
                    $routing->browser(request(), $branch);
                    $routing->paper(request(), $branch, $data['paper_profile']);
                    Notification::make()->title('Cetak lokal dan kertas disimpan untuk browser ini.')->success()->send();
                }),
            ActionGroup::make([
                Action::make('deviceHistory')->label('Riwayat Perangkat Kiosk')->color('gray')->url(fn () => KioskDeviceResource::getUrl('index', [
                    'filters' => ['branch_id' => ['value' => $this->branchId]],
                ])),
                Action::make('useServerHere')->label('Lanjutan: printer server')->color('gray')->requiresConfirmation()
                    ->authorize(fn () => PrintAgentResource::canViewAny())
                    ->modalDescription('Khusus PC yang sengaja akan memakai printer pada server. PC baru tetap memakai dialog cetak lokal.')
                    ->action(function () {
                        app(KioskPrintRouting::class)->bindServer(request(), $this->getBranch());
                        Notification::make()->title('Browser ini memakai printer server untuk kantor tersebut.')->success()->send();
                    }),
            ])->label('Lainnya')->button()->color('gray'),
        ];
    }
}
