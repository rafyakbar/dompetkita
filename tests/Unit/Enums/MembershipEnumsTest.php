<?php

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('account roles have expected values, labels, and colors', function () {
    expect(AccountRole::Owner->value)->toBe('owner')
        ->and(AccountRole::Member->value)->toBe('member')
        ->and(AccountRole::Viewer->value)->toBe('viewer')
        ->and(AccountRole::Owner)->toBeInstanceOf(HasLabel::class)
        ->and(AccountRole::Owner)->toBeInstanceOf(HasColor::class)
        ->and(AccountRole::Owner->getLabel())->toBe('Pemilik')
        ->and(AccountRole::Member->getLabel())->toBe('Anggota')
        ->and(AccountRole::Viewer->getLabel())->toBe('Pengamat');
});

test('member statuses have expected values, labels, and colors', function () {
    expect(MemberStatus::Invited->value)->toBe('invited')
        ->and(MemberStatus::Active->value)->toBe('active')
        ->and(MemberStatus::Left->value)->toBe('left')
        ->and(MemberStatus::Revoked->value)->toBe('revoked')
        ->and(MemberStatus::Active)->toBeInstanceOf(HasLabel::class)
        ->and(MemberStatus::Active)->toBeInstanceOf(HasColor::class)
        ->and(MemberStatus::Active->getLabel())->toBe('Aktif')
        ->and(MemberStatus::Invited->getLabel())->toBe('Diundang');
});
