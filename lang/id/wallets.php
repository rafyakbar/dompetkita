<?php

use App\Enums\WalletTypeEnum;

return [
    'title' => 'Dompet',
    'title_singular' => 'Dompet',
    'actions' => [
        'refresh_balance' => 'Segarkan Saldo',
    ],
    'notifications' => [
        'balance_refreshed' => 'Saldo Telah Disegarkan',
    ],
    'fields' => [
        'name' => 'Nama',
        'type' => 'Tipe',
        'balance' => 'Saldo',
        'initial_balance' => 'Saldo Awal',
        'credit_limit' => 'Limit Kredit',
        'total_due' => 'Total Tagihan Saat Ini',
        'currency_code' => 'Mata Uang',
        'description' => 'Deskripsi',
        'statement_day_of_month' => 'Tanggal Cetak Tagihan (Billing Cycle)',
        'payment_due_day_of_month' => 'Tanggal Jatuh Tempo',
        'icon' => 'Ikon',
        'color' => 'Warna',
        'exclude' => [
            'title' => 'Kecualikan',
            'help_text' => 'Abaikan saldo dompet ini dari total perhitungan saldo keseluruhan',
        ]
    ],
    'types' => [
        WalletTypeEnum::GENERAL->value => 'Umum',
        WalletTypeEnum::CREDIT_CARD->value => 'Kartu Kredit',
    ]
];
