<?php

use Illuminate\Support\Facades\Schema;

test('accounts table has expected columns and indexes', function () {
    expect(Schema::hasTable('accounts'))->toBeTrue()
        ->and(Schema::hasColumns('accounts', [
            'id', 'owner_id', 'name', 'slug', 'currency_code', 'description', 'created_at', 'updated_at', 'deleted_at',
        ]))->toBeTrue();
});

test('account_member table has expected columns', function () {
    expect(Schema::hasTable('account_member'))->toBeTrue()
        ->and(Schema::hasColumns('account_member', [
            'id', 'account_id', 'user_id', 'email', 'invitation_token', 'role', 'status', 'invited_at', 'confirmed_at', 'left_at', 'revoked_at', 'created_at', 'updated_at',
        ]))->toBeTrue();
});
