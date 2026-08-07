<?php

namespace App\Filament\Widgets;

use App\Models\Queue;
use App\Models\User;
use Filament\Widgets\Widget;

class QueueOperationsWidget extends Widget
{
    protected static ?string $pollingInterval = '3s';
    protected static ?int $sort = 2;
    protected string $view = 'filament.widgets.queue-operations';
    protected int|string|array $columnSpan = 'full';
}
