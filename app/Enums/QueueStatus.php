<?php

namespace App\Enums;

enum QueueStatus: string
{
    case WAITING = 'waiting';
    case CALLED = 'called';
    case SERVING = 'serving';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';

    public function getLabel(): string
    {
        return match ($this) {
            self::WAITING => __('app.status_waiting'),
            self::CALLED => __('app.status_called'),
            self::SERVING => __('app.status_serving') !== 'app.status_serving' ? __('app.status_serving') : 'Sedang Dilayani',
            self::COMPLETED => __('app.status_completed'),
            self::SKIPPED => __('app.status_skipped'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::WAITING => 'warning',
            self::CALLED => 'info',
            self::SERVING => 'primary',
            self::COMPLETED => 'success',
            self::SKIPPED => 'gray',
        };
    }
}
