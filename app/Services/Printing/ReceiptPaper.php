<?php

namespace App\Services\Printing;

class ReceiptPaper
{
    public const LABELS = ['a4' => 'A4', 'thermal58' => 'Thermal 58 mm', 'thermal80' => 'Thermal 80 mm'];

    public static function dimensions(string $profile = 'a4'): array
    {
        return match ($profile) {
            'a4' => ['width_mm' => 210, 'height_mm' => 297],
            'thermal58' => ['width_mm' => 58, 'height_mm' => 150],
            'thermal80' => ['width_mm' => 80, 'height_mm' => 150],
            default => throw new \InvalidArgumentException('Profil kertas tidak dikenal.'),
        };
    }
}
