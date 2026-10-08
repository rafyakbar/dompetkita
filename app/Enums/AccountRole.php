<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountRole: string implements HasColor, HasLabel
{
    case Owner = 'owner';
    case Member = 'member';
    case Viewer = 'viewer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Member => 'Anggota',
            self::Viewer => 'Pengamat',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Owner => 'primary',
            self::Member => 'info',
            self::Viewer => 'gray',
        };
    }
}
