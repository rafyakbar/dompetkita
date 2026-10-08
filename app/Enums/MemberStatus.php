<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MemberStatus: string implements HasColor, HasLabel
{
    case Invited = 'invited';
    case Active = 'active';
    case Left = 'left';
    case Revoked = 'revoked';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Invited => 'Diundang',
            self::Active => 'Aktif',
            self::Left => 'Keluar',
            self::Revoked => 'Dicabut',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Invited => 'warning',
            self::Active => 'success',
            self::Left => 'gray',
            self::Revoked => 'danger',
        };
    }
}
