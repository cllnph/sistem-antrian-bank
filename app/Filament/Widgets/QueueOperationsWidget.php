<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QueueOperationsWidget extends Widget
{
    protected static ?string $pollingInterval = '3s';

    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.queue-operations';

    protected int|string|array $columnSpan = 'full';
}
